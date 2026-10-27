/** Datubāzē ISO, lietotājam DD.MM.GGGG; bez pārlūka reģiona iestatījumu ietekmes. */
export function formatDate(value) {
    const match = /^(\d{4})-(\d{2})-(\d{2})(?:[ T].*)?$/.exec(value || '');
    return match && match[1] !== '0000' && match[2] !== '00' && match[3] !== '00'
        ? `${match[3]}.${match[2]}.${match[1]}` : '';
}

export function parseDate(value) {
    value = value.trim();
    if (!value) return '';
    const match = /^(\d{2})\.(\d{2})\.(\d{4})$/.exec(value);
    if (match) {
        const [, day, month, year] = match;
        const date = new Date(`${year}-${month}-${day}T00:00:00Z`);
        if (Number(year) >= 1000 && date.getUTCFullYear() === Number(year)
            && date.getUTCMonth() + 1 === Number(month) && date.getUTCDate() === Number(day)) {
            return `${year}-${month}-${day}`;
        }
    }
    throw new Error('Ievadi derīgu datumu formātā DD.MM.GGGG, piemēram, 21.10.2026.');
}

export function initializeDateFields() {
    for (const id of ['sak_dat', 'termins', 'g_dat']) {
        const input = document.getElementById(id);
        const picker = document.getElementById(id + '-calendar');
        input.addEventListener('input', () => {
            try { parseDate(input.value); input.setCustomValidity(''); }
            catch (error) { input.setCustomValidity(error.message); }
        });
        // Pirms atvēršanas kalendārs saņem arī ar roku ievadīto datumu.
        const syncCalendar = () => {
            try { picker.value = parseDate(input.value); }
            catch { picker.value = ''; }
        };
        picker.addEventListener('focus', syncCalendar);
        picker.addEventListener('pointerdown', syncCalendar);
        picker.addEventListener('click', () => {
            if (typeof picker.showPicker === 'function') {
                try { picker.showPicker(); } catch { /* Saglabā native input darbību. */ }
            }
        });
        picker.addEventListener('change', () => {
            input.value = formatDate(picker.value);
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }
}
