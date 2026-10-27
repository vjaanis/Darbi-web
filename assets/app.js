import { initializeHistoryChoice } from "./history-choice.js";
import { formatDate, parseDate, initializeDateFields } from './dates.js?v=20261021-calendar';
import { request } from "./api.js?v=20261021-csrf";
import { calculateAmount, unitDivisor } from "./calculation.js?v=20261021-quantity";
import {
    element,
    showNotice,
    replaceOptions,
    replaceSuggestions,
    formatMoney,
    rigaToday,
} from "./ui.js";

// Skaitītāji neļauj vecākai HTTP atbildei pārrakstīt jaunākās izvēles.
const state = {
    client: null,
    clientReady: false,
    searchRequest: 0,
    clientRequest: 0,
    typeRequest: 0,
    sectionRequest: 0,
    calculationRequest: 0,
    searchResults: new Map(),
};

let searchTimer;
let calculationTimer;
let editing = null;
let dirty = false;
let loadingEdit = false;

function amountInput() {
    return {
        daudz: element("daudz").value,
        cena: element("cena").value,
        atlaide: element("atlaide").value,
        mervien: element("mervien").value,
    };
}

function showAmount(amount) {
    element("summary-total").textContent = formatMoney(amount.summa);
    element("calculation-note").textContent =
        amount.divisor === 1
            ? "Pēc atlaides piemērošanas"
            : "Pēc atlaides, dalīts ar " + amount.divisor;
}

function updateAmountPreview() {
    element("daudz").max = unitDivisor(element("mervien").value) === 1800 ? "100000" : "9999.99";
    clearTimeout(calculationTimer);
    const version = ++state.calculationRequest;
    const input = amountInput();

    try {
        showAmount(calculateAmount(input));
    } catch {
        element("summary-total").textContent = "—";
        element("calculation-note").textContent = "Pārbaudi daudzumu, cenu un atlaidi.";
        return;
    }

    // Pārlūks rāda tūlītēju priekšskatījumu; to apstiprina PHP aprēķins.
    calculationTimer = setTimeout(async () => {
        try {
            const amount = await request("calculate", {}, input);

            if (version === state.calculationRequest) {
                showAmount(amount);
            }
        } catch {
            if (version === state.calculationRequest) {
                element("calculation-note").textContent =
                    "Priekšskatījums. PHP pārrēķinās summu saglabājot.";
            }
        }
    }, 250);
}

/** Klients ir pirmais aktīvais ievades solis. */
function setClientReady(isReady) {
    state.clientReady = isReady;
    element("client-followup-fields").disabled = !isReady;
    element("parstv").disabled = !isReady;
    element("diviz").disabled = !isReady;
    element("parstv-toggle").disabled = !isReady;
    element("diviz-toggle").disabled = !isReady;
    element("save-job").disabled = !isReady;
}

function clearDependentChoices() {
    setClientReady(false);
    ++state.typeRequest;
    ++state.sectionRequest;

    replaceOptions("darb_veids", [], "Vispirms izvēlies klientu");
    replaceOptions("darb_tip1", [], "Izvēlies pakalpojuma veidu");
    replaceOptions("mervien", [], "Izvēlies apakšiedaļu");
    replaceSuggestions("representatives", []);
    replaceSuggestions("divisions", []);
    element("parstv").value = "";
    element("diviz").value = "";
}

function clearClient() {
    state.client = null;
    ++state.clientRequest;
    clearDependentChoices();

    element("client-details").textContent = "Izvēlies klientu no meklēšanas rezultātiem.";
    updateAmountPreview();
}

function showClientDetails(client) {
    const title = document.createElement("strong");
    title.textContent = client.name;

    const details = document.createElement("span");
    details.textContent = [client.c_code, client.address, client.b_acc].filter(Boolean).join(" · ");

    const language = document.createElement("span");
    language.className = "language";
    language.textContent = client.lng || "—";

    element("client-details").replaceChildren(title, details, language);
}

async function selectClient(client) {
    clearTimeout(searchTimer);
    ++state.searchRequest;
    const version = ++state.clientRequest;

    state.client = client;
    clearDependentChoices();
    element("client-search").value = client.name;
    element("client-results").hidden = true;
    showClientDetails(client);
    replaceOptions("darb_veids", [], "Ielādē pakalpojumus…");
    updateAmountPreview();

    const [types, history] = await Promise.all([
        request("catalog", { kind: "types", lng: client.lng }),
        request("history", { id: client.id }),
    ]);

    if (version !== state.clientRequest) {
        return;
    }

    replaceOptions("darb_veids", types, types.length ? "-IZVĒLAMIES-" : "--");
    // VFP tukšam vēstures masīvam izmanto "--"; savu vērtību drīkst ievadīt.
    replaceSuggestions("representatives", history.parstv.length ? history.parstv : ["--"]);
    replaceSuggestions("divisions", history.diviz.length ? history.diviz : ["--"]);
    setClientReady(true);
}

async function searchClients(version) {
    try {
        const clients = await request("clients", { q: element("client-search").value });

        if (version !== state.searchRequest) {
            return;
        }

        state.searchResults = new Map(clients.map((client) => [String(client.id), client]));
        const results = element("client-results");
        const placeholder = new Option(
            clients.length ? "Izvēlies klientu" : "Klients nav atrasts — sazinies ar administratoru.",
            "",
        );
        placeholder.disabled = true;
        results.replaceChildren(placeholder);

        for (const client of clients) {
            const label = [client.name, client.c_code, client.lng].filter(Boolean).join(" · ");
            results.add(new Option(label, client.id));
        }

        results.value = "";
        results.hidden = false;
    } catch (error) {
        if (version === state.searchRequest) {
            showNotice(error.message, true);
        }
    }
}

function initializeClientSearch() {
    const search = element("client-search");
    const results = element("client-results");

    search.addEventListener("input", () => {
        clearTimeout(searchTimer);
        clearClient();
        results.hidden = true;

        const version = ++state.searchRequest;
        searchTimer = setTimeout(() => searchClients(version), 200);
    });

    search.addEventListener("focus", () => {
        if (!state.client) {
            searchClients(++state.searchRequest);
        }
    });

    search.addEventListener("keydown", (event) => {
        if (event.key === "ArrowDown" && !results.hidden) {
            event.preventDefault();
            results.focus();
        }

        if (event.key === "Escape") {
            results.hidden = true;
        }
    });

    results.addEventListener("change", async () => {
        const client = state.searchResults.get(results.value);

        if (!client) {
            return;
        }

        try {
            await selectClient(client);
            element("parstv").focus();
        } catch (error) {
            showNotice(error.message, true);
        }
    });

    results.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            results.hidden = true;
            search.focus();
        }
    });

    document.addEventListener("click", (event) => {
        if (!element("client-search-field").contains(event.target)) {
            results.hidden = true;
        }
    });
}

async function loadSections() {
    const version = ++state.typeRequest;
    ++state.sectionRequest;

    replaceOptions("darb_tip1", [], "Izvēlies pakalpojuma veidu");
    replaceOptions("mervien", [], "Izvēlies apakšiedaļu");
    updateAmountPreview();

    if (!state.client || !element("darb_veids").value) {
        return;
    }

    try {
        const sections = await request("catalog", {
            kind: "sections",
            lng: state.client.lng,
            tips: element("darb_veids").value,
        });

        if (version === state.typeRequest) {
            replaceOptions("darb_tip1", sections, "Izvēlies apakšiedaļu");
        }
    } catch (error) {
        if (version === state.typeRequest) {
            showNotice(error.message, true);
        }
    }
}

async function loadUnits() {
    const version = ++state.sectionRequest;
    replaceOptions("mervien", [], "Izvēlies apakšiedaļu");
    updateAmountPreview();

    if (!state.client || !element("darb_tip1").value) {
        return;
    }

    try {
        const units = await request("catalog", {
            kind: "units",
            sdala: element("darb_tip1").value,
        });

        if (version !== state.sectionRequest) {
            return;
        }

        replaceOptions("mervien", units, "Izvēlies mērvienību");

        if (units.length === 1) {
            element("mervien").value = units[0];
        }

        updateAmountPreview();
    } catch (error) {
        if (version === state.sectionRequest) {
            showNotice(error.message, true);
        }
    }
}

function setSaving(isSaving) {
    element("job-fields").disabled = isSaving;
    element("save-job").disabled = isSaving || !state.clientReady;
    element("reset-job").disabled = isSaving;
    element("save-job").textContent = isSaving ? "Saglabā…" : editing ? "Saglabāt izmaiņas" : "Izveidot darba ierakstu ↗";
}

function resetJobForm() {
    editing = null;
    dirty = false;
    element('page-title').textContent = 'Jauns darbs';
    element('reset-job').textContent = 'Notīrīt formu';
    history.replaceState(null, '', location.pathname);
    element("job-form").reset();
    element("sak_dat").value = formatDate(rigaToday());
    for (const id of ['sak_dat', 'termins', 'g_dat']) element(id).setCustomValidity('');
    element("g_dat").hidden = true;
    element("g_dat").required = false;
    element('g_dat').disabled = true;
    element("client-search").value = "";
    element("client-results").hidden = true;

    clearTimeout(searchTimer);
    ++state.searchRequest;
    clearClient();
}

async function saveJob(event) {
    event.preventDefault();

    if (!state.client || !state.clientReady) {
        showNotice("Vispirms izvēlies klientu un sagaidi saistīto sarakstu ielādi.", true);
        element("client-search").focus();
        return;
    }

    if (!window.confirm(editing ? 'Saglabāt izmaiņas darbam #' + editing.nr + '?' : "Izveidot jaunu darba ierakstu?")) {
        return;
    }

    const form = element("job-form");
    const input = Object.fromEntries(new FormData(form));
    input.klient_id = state.client.id;
    input.gat = element("gat").checked;
    try {
        for (const id of ['sak_dat', 'termins', 'g_dat']) {
            input[id] = id === 'g_dat' && !input.gat ? '' : parseDate(element(id).value);
        }
    } catch (error) {
        showNotice(error.message, true);
        return;
    }
    if (editing) {
        input.nr = editing.nr;
        input.version = editing.version;
    }

    setSaving(true);

    try {
        const wasEditing = Boolean(editing);
        const result = await request(wasEditing ? 'update_job' : 'create_job', {}, input);
        dirty = false;
        if (wasEditing) {
            location.assign('?view=jobs');
            return;
        }
        resetJobForm();
        showNotice("Darbs #" + result.nr + " saglabāts. Summa: " + formatMoney(result.summa));
    } catch (error) {
        showNotice(error.message, true);
    } finally {
        setSaving(false);
    }
}

function initializeEvents() {
    initializeDateFields();
    initializeClientSearch();
    initializeHistoryChoice("parstv", "representatives");
    initializeHistoryChoice("diviz", "divisions");

    element("darb_veids").addEventListener("change", loadSections);
    element("darb_tip1").addEventListener("change", loadUnits);

    for (const id of ["daudz", "cena", "atlaide", "mervien"]) {
        element(id).addEventListener("input", updateAmountPreview);
    }

    element("gat").addEventListener("change", () => {
        const completed = element("gat").checked;
        element("g_dat").hidden = !completed;
        element("g_dat").required = completed;
        element('g_dat').disabled = !completed;
        if (!completed) element('g_dat').setCustomValidity('');

        if (completed && !element("g_dat").value) {
            element("g_dat").value = formatDate(rigaToday());
        }
    });

    element("job-form").addEventListener("submit", saveJob);
    element("reset-job").addEventListener("click", () => {
        if (dirty && !window.confirm('Atmest nesaglabātās izmaiņas?')) return;
        if (editing) { dirty = false; location.assign('?view=jobs'); }
        else resetJobForm();
    });
    element('job-form').addEventListener('input', () => { if (!loadingEdit) dirty = true; });
    element('job-form').addEventListener('change', () => { if (!loadingEdit) dirty = true; });
    window.addEventListener('beforeunload', (event) => {
        if (dirty) { event.preventDefault(); event.returnValue = ''; }
    });
}

async function loadEditingJob(number) {
    loadingEdit = true;
    const detail = await request('job', { nr: number });
    const job = detail.job;
    await selectClient(detail.client);
    const choose = (id, value) => {
        const select = element(id);
        value = value || '';
        if (value && !Array.from(select.options).some(option => option.value === value)) {
            select.add(new Option(value + ' (iepriekš saglabāts)', value));
        }
        select.disabled = false;
        select.value = value;
    };
    choose('darb_veids', job.darb_veids);
    await loadSections();
    choose('darb_tip1', job.darb_tip1);
    await loadUnits();
    choose('mervien', job.mervien);
    for (const id of ['parstv', 'diviz', 'darbnosauk', 'daudz', 'cena', 'atlaide', 'sak_dat', 'termins', 'g_dat', 'koment']) {
        element(id).value = ['sak_dat', 'termins', 'g_dat'].includes(id)
            ? formatDate(job[id]) : (job[id] ?? '');
    }
    element('gat').checked = Boolean(Number(job.gat));
    element('g_dat').hidden = !element('gat').checked;
    element('g_dat').required = element('gat').checked;
    element('g_dat').disabled = !element('gat').checked;
    editing = { nr: job.nr, version: detail.version };
    element('page-title').textContent = 'Labot darbu #' + job.nr;
    element('reset-job').textContent = 'Atcelt labošanu';
    setSaving(false);
    updateAmountPreview();
    dirty = false;
    loadingEdit = false;
}

async function initializePage() {
    element("today").textContent = formatDate(rigaToday());

    element("sak_dat").value = formatDate(rigaToday());
    const status = await request("status");

    if (!status.ready) {
        throw new Error("Datubāzes savienojums nav pieejams.");
    }

    initializeEvents();
    element('g_dat').disabled = true;
    setClientReady(false);
    const parameters = new URLSearchParams(location.search);
    if (parameters.has('job')) {
        element('job-fields').disabled = true;
        await loadEditingJob(parameters.get('job'));
    }
    element("loading").hidden = true;
    element("workspace").hidden = false;
    element("client-search").focus();
    updateAmountPreview();
}

initializePage().catch((error) => {
    element("loading").hidden = true;
    showNotice(error.message, true);
});
