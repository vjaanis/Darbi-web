import { request } from './api.js?v=20261021-csrf';
import { element, formatMoney, showNotice, rigaToday } from './ui.js';
import { formatDate } from './dates.js?v=20261021-calendar';

let page = 1;
let query = '';
let version = 0;
let busy = false;

function date(value) {
    return formatDate(value) || '—';
}

async function loadJobs() {
    const current = ++version;
    element('jobs-prev').disabled = true;
    element('jobs-next').disabled = true;
    element('jobs-count').textContent = 'Ielādē…';
    element('jobs-rows').replaceChildren();
    try {
        const result = await request('jobs', { q: query, page });
        if (current !== version) return;
        page = result.page;
        for (const job of result.rows) {
            const row = document.createElement('tr');
            for (const value of [job.nr, date(job.sak_dat), date(job.termins), job.klients,
                job.darbnosauk, formatMoney(job.summa), Number(job.gat) ? 'Pabeigts' : 'Darbā']) {
                const cell = document.createElement('td');
                cell.textContent = value;
                row.append(cell);
            }
            const actions = document.createElement('td');
            const edit = document.createElement('a');
            edit.href = '?job=' + encodeURIComponent(job.nr);
            edit.textContent = 'Labot';
            edit.className = 'text-button';
            edit.setAttribute('aria-label', 'Labot darbu #' + job.nr);
            const copy = document.createElement('a');
            copy.href = '?copy=' + encodeURIComponent(job.nr);
            copy.textContent = 'Kopēt';
            copy.className = 'text-button';
            copy.setAttribute('aria-label', 'Kopēt darbu #' + job.nr);
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'text-button danger';
            remove.textContent = 'Dzēst';
            remove.setAttribute('aria-label', 'Dzēst darbu #' + job.nr);
            remove.addEventListener('click', () => deleteJob(job, remove));
            actions.append(edit, document.createTextNode(' '), copy, document.createTextNode(' '), remove);
            row.append(actions);
            element('jobs-rows').append(row);
        }
        element('jobs-count').textContent = result.total ? 'Atrasti darbi: ' + result.total : 'Darbi nav atrasti.';
        element('jobs-page').textContent = page + ' / ' + result.pages;
        element('jobs-prev').disabled = page <= 1;
        element('jobs-next').disabled = page >= result.pages;
    } catch (error) {
        if (current === version) {
            element('jobs-count').textContent = 'Neizdevās ielādēt darbus. Mēģini meklēt vēlreiz.';
            showNotice(error.message, true);
        }
    }
}

async function deleteJob(job, button) {
    if (busy) return;
    busy = true;
    button.disabled = true;
    try {
        const detail = await request('job', { nr: job.nr });
        const selected = detail.job;
        if (!window.confirm('Neatgriezeniski dzēst darbu #' + selected.nr + '?\n' + selected.klients + '\n' + selected.darbnosauk)) return;
        await request('delete_job', {}, { nr: selected.nr, version: detail.version });
        showNotice('Darbs #' + selected.nr + ' dzēsts.');
        await loadJobs();
    } catch (error) {
        showNotice(error.message, true);
    } finally {
        busy = false;
        button.disabled = false;
    }
}

element('today').textContent = formatDate(rigaToday());
element('jobs-search').addEventListener('submit', (event) => {
    event.preventDefault();
    query = element('jobs-query').value.trim();
    page = 1;
    loadJobs();
});
element('jobs-prev').addEventListener('click', () => { --page; loadJobs(); });
element('jobs-next').addEventListener('click', () => { ++page; loadJobs(); });
element('loading').hidden = true;
element('jobs-panel').hidden = false;
loadJobs();
