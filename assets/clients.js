import { request } from './api.js?v=20261021-csrf';
import { element, showNotice, rigaToday } from './ui.js';

let clients = [];

function renderClients() {
    const query = element('clients-filter').value.trim().toLocaleLowerCase('lv');
    const visible = clients.filter(client =>
        [client.name, client.c_code, String(client.id)].some(value =>
            String(value ?? '').toLocaleLowerCase('lv').includes(query)));
    const rows = visible.map(client => {
        const row = document.createElement('tr');
        const choice = document.createElement('td');
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.id = 'client-act-' + client.id;
        checkbox.checked = Number(client.act) === 1;
        checkbox.setAttribute('aria-label', 'Rādīt ievadē: ' + client.name + ' (ID ' + client.id + ')');
        checkbox.addEventListener('change', async () => {
            const previous = Number(client.act);
            for (const input of element('clients-list').querySelectorAll('input')) input.disabled = true;
            // Aptur saraksta pārzīmēšanu, līdz saglabāšanas rezultāts ir zināms.
            element('clients-filter').disabled = true;
            try {
                const result = await request('set_client_active', {}, {
                    id: Number(client.id), act: checkbox.checked ? 1 : 0,
                });
                client.act = result.act;
                showNotice('Saglabāts: ' + client.name + (Number(result.act) ? ' — rādīt ievadē.' : ' — nerādīt ievadē.'));
            } catch (error) {
                client.act = previous;
                showNotice(error.message, true);
            } finally {
                element('clients-filter').disabled = false;
                renderClients();
                document.getElementById('client-act-' + client.id)?.focus();
            }
        });
        choice.append(checkbox);
        row.append(choice);
        for (const value of [client.id, client.name, client.c_code, client.lng]) {
            const cell = document.createElement('td');
            cell.textContent = value ?? '';
            row.append(cell);
        }
        return row;
    });
    if (!rows.length) {
        const row = document.createElement('tr');
        const cell = document.createElement('td');
        cell.colSpan = 5;
        cell.textContent = 'Klienti nav atrasti.';
        row.append(cell);
        rows.push(row);
    }
    element('clients-list').replaceChildren(...rows);
    element('clients-count').textContent = 'Kopā: ' + clients.length
        + ' · Aktīvi: ' + clients.filter(client => Number(client.act) === 1).length
        + ' · Atlasē: ' + visible.length;
}

element('clients-filter').addEventListener('input', renderClients);
element('today').textContent = rigaToday().split('-').reverse().join('.');

try {
    clients = await request('admin_clients');
    renderClients();
    element('clients-panel').hidden = false;
} catch (error) {
    showNotice(error.message, true);
} finally {
    element('loading').hidden = true;
}
