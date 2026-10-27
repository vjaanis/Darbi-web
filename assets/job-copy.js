const copiedFields = [
    'darb_veids', 'darb_tip1', 'mervien', 'parstv', 'diviz',
    'darbnosauk', 'daudz', 'cena', 'atlaide', 'koment',
];

function isoDate(value) {
    if (typeof value !== 'string' || !/^\d{4}-\d{2}-\d{2}$/.test(value)) return null;
    const date = new Date(value + 'T00:00:00Z');
    return Number.isFinite(date.getTime()) && date.toISOString().slice(0, 10) === value ? date : null;
}

/** New work has fresh dates and no source identifier, owner or export metadata. */
export function copyJobValues(source, today) {
    const start = isoDate(today);
    if (!start) throw new Error('Nederīgs jaunā darba sākuma datums.');
    const result = Object.fromEntries(copiedFields.map(field => [field, source[field] ?? '']));
    result.sak_dat = today;
    result.termins = '';
    result.gat = 0;
    result.g_dat = '';
    return result;
}
