import { request } from './api.js?v=20261021-csrf';
import { element, formatMoney, showNotice, rigaToday } from './ui.js';
import { formatDate } from './dates.js?v=20261021-calendar';

const selected = new Map();
let page = 1;
let pages = 1;
let historyPage = 1;
let historyPages = 1;
let rows = [];
let busy = false;
let loading = false;
let historyLoading = false;
let requestId = null;
let filters = { author: '0', q: '' };
let historyQuery = '';
let revision = 0;
let historyRevision = 0;

function cell(row, value, className = '') {
    const td = document.createElement('td');
    td.textContent = value;
    td.className = className;
    row.append(td);
    return td;
}

function dateTime(value) {
    return value ? (formatDate(value.slice(0, 10)) + value.slice(10)) : '—';
}

function downloadUrl(nr) {
    return 'api.php?' + new URLSearchParams({ action: 'download_export', nr });
}

function controls() {
    element('export-selected').textContent = 'Atzīmēti: ' + selected.size;
    element('export-create').disabled = busy || loading || selected.size === 0;
    element('export-clear').disabled = busy || selected.size === 0;
    element('export-prev').disabled = busy || loading || page <= 1;
    element('export-next').disabled = busy || loading || page >= pages;
    element('history-prev').disabled = historyLoading || historyPage <= 1;
    element('history-next').disabled = historyLoading || historyPage >= historyPages;
    for (const field of element('exports-filter').elements) field.disabled = busy;
    const eligible = rows.filter(job => !Number(job.eksportets));
    const count = eligible.filter(job => selected.has(String(job.nr))).length;
    element('export-all').disabled = busy || loading || !eligible.length;
    element('export-all').checked = eligible.length > 0 && count === eligible.length;
    element('export-all').indeterminate = count > 0 && count < eligible.length;
    for (const checkbox of document.querySelectorAll('[data-export-nr]')) {
        checkbox.disabled = busy || loading;
        checkbox.checked = selected.has(checkbox.dataset.exportNr);
    }
}

function selectJob(job, checked) {
    const nr = String(job.nr);
    if (checked && !selected.has(nr) && selected.size >= 200) {
        showNotice('Vienā eksportā var atzīmēt ne vairāk kā 200 darbus.', true);
        return;
    }
    if (checked) selected.set(nr, { nr: Number(job.nr), version: job.version });
    else selected.delete(nr);
    requestId = null;
}

async function loadJobs() {
    const current = ++revision;
    loading = true;
    rows = [];
    element('export-rows').replaceChildren();
    element('export-count').textContent = 'Ielādē…';
    controls();
    try {
        const result = await request('export_jobs', { ...filters, page });
        if (current !== revision) return;
        page = result.page;
        pages = result.pages;
        rows = result.rows;
        // Atgriežoties lapā, mainīts ieraksts jāatzīmē apzināti no jauna.
        let removed = false;
        for (const job of rows) {
            const nr = String(job.nr);
            if (selected.has(nr) && (Number(job.eksportets) || selected.get(nr).version !== job.version)) {
                selected.delete(nr);
                requestId = null;
                removed = true;
            }
            const row = document.createElement('tr');
            const choice = cell(row, '');
            if (!Number(job.eksportets)) {
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.dataset.exportNr = nr;
                checkbox.setAttribute('aria-label', 'Eksportēt darbu #' + nr);
                checkbox.addEventListener('change', () => { selectJob(job, checkbox.checked); controls(); });
                choice.append(checkbox);
            }
            cell(row, job.nr);
            cell(row, formatDate(job.sak_dat) || '—');
            cell(row, job.author + (job.darbinieks_id == null ? ' (bez piesaistes)' : ''));
            cell(row, job.klients);
            cell(row, job.darbnosauk);
            cell(row, formatMoney(job.summa));
            cell(row, Number(job.gat) ? 'Pabeigts' : 'Darbā');
            element('export-rows').append(row);
        }
        if (removed) showNotice('Mainīti vai jau eksportēti darbi no atlases izņemti. Pārbaudi atlasi.', true);
        element('export-count').textContent = result.total ? 'Atrasti darbi: ' + result.total : 'Darbi nav atrasti.';
        element('export-page').textContent = page + ' / ' + pages;
    } catch (error) {
        if (current === revision) {
            element('export-count').textContent = 'Neizdevās ielādēt darbus. Spied “Atlasīt”, lai mēģinātu vēlreiz.';
            showNotice(error.message, true);
        }
    } finally {
        if (current === revision) { loading = false; controls(); }
    }
}

async function loadHistory() {
    const current = ++historyRevision;
    historyLoading = true;
    element('history-count').textContent = 'Ielādē…';
    element('history-rows').replaceChildren();
    controls();
    try {
        const result = await request('exports', { page: historyPage, q: historyQuery });
        if (current !== historyRevision) return;
        historyPage = result.page;
        historyPages = result.pages;
        for (const entry of result.rows) {
            const row = document.createElement('tr');
            cell(row, '#' + entry.nr);
            cell(row, dateTime(entry.izveidots));
            cell(row, entry.izpilditajs);
            cell(row, entry.ierakstu_skaits);
            const numbers = cell(row, '', 'export-job-numbers');
            const details = document.createElement('details');
            const summary = document.createElement('summary');
            summary.textContent = 'Skatīt numurus';
            details.append(summary, document.createTextNode(JSON.parse(entry.darbu_numuri).join(', ')));
            numbers.append(details);
            const link = document.createElement('a');
            link.className = 'text-button';
            link.href = downloadUrl(entry.nr);
            link.textContent = entry.faila_nosaukums;
            cell(row, '').append(link);
            element('history-rows').append(row);
        }
        element('history-count').textContent = result.total ? 'Eksportu skaits: ' + result.total : 'Eksportu vēl nav.';
        element('history-page').textContent = historyPage + ' / ' + historyPages;
    } catch (error) {
        if (current === historyRevision) {
            element('history-count').textContent = 'Neizdevās ielādēt eksportu vēsturi.';
            showNotice(error.message, true);
        }
    } finally {
        if (current === historyRevision) { historyLoading = false; controls(); }
    }
}

async function createExport() {
    if (busy || loading || !selected.size) return;
    busy = true;
    controls();
    try {
        if (!requestId) {
            requestId = Array.from(crypto.getRandomValues(new Uint8Array(16)), byte => byte.toString(16).padStart(2, '0')).join('');
        }
        const result = await request('create_export', {}, { jobs: [...selected.values()], request_id: requestId });
        const link = element('export-download');
        link.href = downloadUrl(result.nr);
        link.textContent = 'Lejupielādēt eksportu #' + result.nr;
        link.hidden = false;
        link.click();
        selected.clear();
        requestId = null;
        showNotice('Eksports #' + result.nr + ' sagatavots. Ja lejupielāde nesākās, izmanto CSV saiti vai eksportu vēsturi.');
        historyPage = 1;
        historyQuery = '';
        element('history-query').value = '';
        await Promise.all([loadJobs(), loadHistory()]);
    } catch (error) {
        showNotice(error.message + ' Ja savienojums pārtrūka, vari atkārtot šo pašu pieprasījumu vai pārbaudīt eksportu vēsturi.', true);
    } finally {
        busy = false;
        controls();
    }
}

element('today').textContent = formatDate(rigaToday());
element('export-create').addEventListener('click', createExport);
element('export-clear').addEventListener('click', () => { selected.clear(); requestId = null; controls(); });
element('export-all').addEventListener('change', event => {
    for (const job of rows.filter(job => !Number(job.eksportets))) selectJob(job, event.target.checked);
    controls();
});
element('exports-filter').addEventListener('submit', event => {
    event.preventDefault();
    if (busy) return;
    const next = { author: element('export-author').value, q: element('export-query').value.trim() };
    if (JSON.stringify(next) !== JSON.stringify(filters)) { selected.clear(); requestId = null; }
    filters = next;
    page = 1;
    loadJobs();
});
element('export-author').addEventListener('change', () => element('exports-filter').requestSubmit());
element('export-prev').addEventListener('click', () => { --page; loadJobs(); });
element('export-next').addEventListener('click', () => { ++page; loadJobs(); });
element('history-prev').addEventListener('click', () => { --historyPage; loadHistory(); });
element('history-next').addEventListener('click', () => { ++historyPage; loadHistory(); });
element('export-history-search').addEventListener('submit', event => {
    event.preventDefault();
    historyQuery = element('history-query').value.trim();
    historyPage = 1;
    loadHistory();
});

async function start() {
    try {
        const authors = await request('export_authors');
        for (const author of authors) {
            const option = document.createElement('option');
            option.value = author.id;
            option.textContent = author.name + ' (#' + author.id + ')';
            element('export-author').append(option);
        }
        element('loading').hidden = true;
        element('exports-panel').hidden = false;
        element('export-history-panel').hidden = false;
        await Promise.all([loadJobs(), loadHistory()]);
    } catch (error) {
        element('loading').textContent = 'Eksporta sadaļu neizdevās ielādēt. Atjauno lapu, lai mēģinātu vēlreiz.';
        showNotice(error.message, true);
    }
}
start();
