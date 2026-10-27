import { request } from './api.js?v=20261021-csrf';
import { element, showNotice, rigaToday } from './ui.js';
import { formatDate } from './dates.js?v=20261021-calendar';

let preview = null;
let busy = false;
const labels = { inserted: 'Pievienot', updated: 'Atjaunināt', unchanged: 'Bez izmaiņām' };

function setBusy(value) {
    busy = value;
    element('client-import-check').disabled = value;
    element('client-import-file').disabled = value;
    element('client-import-commit').disabled = value || !preview;
}

function clearPreview() {
    preview = null;
    element('client-import-preview').hidden = true;
    element('client-import-commit').disabled = true;
}

function appendCell(row, value) {
    const cell = document.createElement('td');
    cell.textContent = value;
    row.append(cell);
}

async function loadHistory() {
    try {
        const entries = await request('client_import_history');
        element('client-import-history').replaceChildren();
        for (const entry of entries) {
            const row = document.createElement('tr');
            for (const value of [entry.nr, formatDate(entry.izveidots.slice(0, 10)) + entry.izveidots.slice(10),
                entry.izpilditajs, entry.faila_nosaukums, entry.kopskaits, entry.pievienoti, entry.atjauninati, entry.nemainiti]) appendCell(row, value);
            element('client-import-history').append(row);
        }
        element('client-import-history-count').textContent = entries.length ? 'Pēdējie importi: ' + entries.length : 'Klientu importu vēl nav.';
    } catch (error) {
        element('client-import-history-count').textContent = 'Neizdevās ielādēt importu vēsturi.';
        showNotice(error.message, true);
    }
}

element('today').textContent = formatDate(rigaToday());
element('client-import-file').addEventListener('change', clearPreview);
element('client-import-form').addEventListener('submit', async event => {
    event.preventDefault();
    if (busy) return;
    const file = element('client-import-file').files[0];
    clearPreview();
    if (!file || file.size > 2097152) { showNotice('Izvēlies CSV failu līdz 2 MB.', true); return; }
    setBusy(true);
    try {
        const form = new FormData();
        form.append('file', file);
        const response = await fetch('api.php?action=preview_client_import', { method: 'POST', body: form,
            headers: { Accept: 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '' } });
        if (response.status === 401) { location.assign('login.php'); return; }
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Faila pārbaude neizdevās.');
        preview = result;
        element('client-import-filename').textContent = result.filename;
        element('client-import-counts').replaceChildren();
        for (const [key, label] of [['total', 'Kopā'], ['inserted', 'Pievienot'], ['updated', 'Atjaunināt'], ['unchanged', 'Bez izmaiņām']]) {
            const count = document.createElement('span'); count.textContent = label + ': ' + result[key];
            element('client-import-counts').append(count);
        }
        element('client-import-sample').replaceChildren();
        for (const client of result.sample) {
            const row = document.createElement('tr');
            for (const value of [client.id, client.name, labels[client.operation]]) appendCell(row, value);
            element('client-import-sample').append(row);
        }
        element('client-import-preview').hidden = false;
        showNotice('Fails pārbaudīts. Izmaiņas vēl nav saglabātas.');
    } catch (error) { showNotice(error.message, true); }
    finally { setBusy(false); }
});

element('client-import-commit').addEventListener('click', async () => {
    if (busy || !preview) return;
    if (!window.confirm('Importēt klientus no faila “' + preview.filename + '”?\nPievienot: ' + preview.inserted + '\nAtjaunināt: ' + preview.updated + '\nBez izmaiņām: ' + preview.unchanged)) return;
    setBusy(true);
    try {
        const result = await request('commit_client_import', {}, { token: preview.token });
        clearPreview();
        showNotice('Imports #' + result.nr + ' pabeigts. Pievienoti: ' + result.inserted + ', atjaunināti: ' + result.updated + ', bez izmaiņām: ' + result.unchanged + '.');
        await loadHistory();
    } catch (error) {
        showNotice(error.message + ' Ja savienojums pārtrūka, vari atkārtot importu ar šo pašu pārbaudi; saglabātais imports netiks izpildīts divreiz.', true);
    } finally { setBusy(false); }
});

element('loading').hidden = true;
element('client-import-panel').hidden = false;
element('client-import-history-panel').hidden = false;
loadHistory();
