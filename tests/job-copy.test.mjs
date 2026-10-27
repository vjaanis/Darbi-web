import test from 'node:test';
import assert from 'node:assert/strict';
import { copyJobValues } from '../assets/job-copy.js';

test('copies business fields into a new incomplete job with today and no deadline', () => {
    const source = {
        nr: 9, version: 'old-version', darbinieks_id: 55, eksportets: 1, eksporta_nr: 7,
        klient_id: 88, klients: 'Source client', lng: 'LV', darbn: 'Source owner',
        sak_dat: '2025-01-01', termins: '2025-01-10', gat: 1, g_dat: '2025-01-07',
        darb_veids: 'Type', darb_tip1: 'Section', mervien: 'Unit', darbnosauk: 'Description',
        parstv: 'Contact', diviz: 'Division', koment: 'Note', daudz: '3.00', cena: '2.5000', atlaide: 0,
        summa: '999.0000',
    };
    const original = structuredClone(source);
    const copy = copyJobValues(source, '2026-10-27');
    for (const field of ['darb_veids', 'darb_tip1', 'mervien', 'darbnosauk', 'parstv', 'diviz', 'koment', 'daudz', 'cena', 'atlaide']) {
        assert.equal(copy[field], source[field]);
    }
    assert.equal(copy.sak_dat, '2026-10-27');
    assert.equal(copy.termins, '');
    assert.equal(copy.gat, 0);
    assert.equal(copy.g_dat, '');
    for (const field of ['nr', 'version', 'darbinieks_id', 'eksportets', 'eksporta_nr', 'darbn', 'summa']) assert.equal(Object.hasOwn(copy, field), false);
    assert.deepEqual(source, original);
});

test('rejects invalid new dates and clears old dates regardless of source state', () => {
    assert.throws(() => copyJobValues({}, '2026-02-30'));
    assert.throws(() => copyJobValues({}, '27.10.2026'));
    const copy = copyJobValues({ sak_dat: null, termins: '2025-01-01', gat: 0 }, '2028-02-29');
    assert.equal(copy.sak_dat, '2028-02-29');
    assert.equal(copy.termins, '');
    assert.equal(copy.g_dat, '');
});
