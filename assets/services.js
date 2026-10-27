import { request } from './api.js?v=20261021-csrf';
import { element, showNotice, rigaToday } from './ui.js';

const fields = ['lng', 'tips', 'sdala', 'mervien'];
let rows = [];
let editing = null;
let busy = false;

function setBusy(value) {
    busy = value;
    element('service-fields').disabled = value;
    element('services-filter').disabled = value;
    element('services-refresh').disabled = value;
    for (const button of element('services-list').querySelectorAll('button')) button.disabled = value;
}

function resetForm() {
    editing = null;
    element('service-form').reset();
    element('service-form-title').textContent = 'Pievienot pakalpojumu';
    element('service-save').textContent = 'Pievienot';
    element('service-cancel').textContent = 'Notīrīt formu';
}

function render() {
    const query = element('services-filter').value.trim().toLocaleLowerCase('lv');
    const visible = rows.filter(row => [row.nr, ...fields.map(field => row[field])].some(value =>
        String(value ?? '').toLocaleLowerCase('lv').includes(query)));
    const rendered = visible.map(row => {
        const tr = document.createElement('tr');
        for (const value of [row.nr, row.lng, row.tips, row.sdala, row.mervien]) {
            const td = document.createElement('td');
            td.textContent = value ?? '';
            tr.append(td);
        }
        const actions = document.createElement('td');
        const edit = document.createElement('button');
        edit.type = 'button';
        edit.textContent = 'Labot';
        edit.setAttribute('aria-label', 'Labot pakalpojumu #' + row.nr);
        edit.addEventListener('click', () => {
            if (busy) return;
            editing = row;
            for (const field of fields) element('service-' + field).value = row[field];
            element('service-form-title').textContent = 'Labot pakalpojumu #' + row.nr;
            element('service-save').textContent = 'Saglabāt';
            element('service-cancel').textContent = 'Atcelt labošanu';
            element('service-lng').focus();
        });
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'danger';
        remove.textContent = 'Dzēst';
        remove.setAttribute('aria-label', 'Dzēst pakalpojumu #' + row.nr);
        remove.addEventListener('click', async () => {
            if (busy || !window.confirm('Dzēst pakalpojumu #' + row.nr + '?\n'
                + [row.lng, row.tips, row.sdala, row.mervien].filter(Boolean).join(' · ')
                + '\nTas mainīs darba ievades izvēlnes.')) return;
            setBusy(true);
            try {
                await request('delete_service', {}, { nr: Number(row.nr), version: row.version });
                rows = rows.filter(item => Number(item.nr) !== Number(row.nr));
                if (editing && Number(editing.nr) === Number(row.nr)) resetForm();
                render();
                showNotice('Pakalpojums #' + row.nr + ' izdzēsts.');
            } catch (error) {
                showNotice(error.message, true);
            } finally {
                setBusy(false);
            }
        });
        edit.disabled = busy;
        remove.disabled = busy;
        actions.append(edit, document.createTextNode(' '), remove);
        tr.append(actions);
        return tr;
    });
    if (!rendered.length) {
        const tr = document.createElement('tr');
        const td = document.createElement('td');
        td.colSpan = 6;
        td.textContent = 'Pakalpojumi nav atrasti.';
        tr.append(td);
        rendered.push(tr);
    }
    element('services-list').replaceChildren(...rendered);
    element('services-count').textContent = 'Kopā: ' + rows.length + ' · Atlasē: ' + visible.length;
}

element('service-form').addEventListener('submit', async event => {
    event.preventDefault();
    if (busy) return;
    const body = Object.fromEntries(fields.map(field => [field, element('service-' + field).value]));
    const current = editing;
    if (current) Object.assign(body, { nr: Number(current.nr), version: current.version });
    setBusy(true);
    try {
        const saved = await request(current ? 'update_service' : 'create_service', {}, body);
        if (current) rows = rows.map(row => Number(row.nr) === Number(saved.nr) ? saved : row);
        else rows.push(saved);
        resetForm();
        render();
        showNotice('Pakalpojums #' + saved.nr + (current ? ' saglabāts.' : ' pievienots.'));
    } catch (error) {
        showNotice(error.message, true);
    } finally {
        setBusy(false);
    }
});

element('service-cancel').addEventListener('click', () => { if (!busy) resetForm(); });
element('services-filter').addEventListener('input', render);
element('services-refresh').addEventListener('click', async () => {
    if (busy) return;
    if (editing && !window.confirm('Atjaunot sarakstu un atcelt nesaglabātos labojumus?')) return;
    setBusy(true);
    try {
        rows = await request('admin_services');
        resetForm();
        render();
        showNotice('Pakalpojumu saraksts atjaunots.');
    } catch (error) { showNotice(error.message, true); }
    finally { setBusy(false); }
});
element('today').textContent = rigaToday().split('-').reverse().join('.');
try {
    rows = await request('admin_services');
    render();
    element('services-panel').hidden = false;
} catch (error) { showNotice(error.message, true); }
finally { element('loading').hidden = true; }
