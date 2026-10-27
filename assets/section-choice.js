/** Wrap long section names while retaining the original select for forms/API. */
export function initializeSectionChoice() {
    const select = document.getElementById('darb_tip1');
    const label = document.querySelector('label[for="darb_tip1"]');
    const wrapper = document.createElement('div');
    wrapper.className = 'section-choice';
    const button = document.createElement('button');
    button.type = 'button';
    button.id = 'section-choice-toggle';
    button.className = 'section-choice-toggle';
    button.setAttribute('role', 'combobox');
    button.setAttribute('aria-haspopup', 'listbox');
    button.setAttribute('aria-expanded', 'false');
    button.setAttribute('aria-controls', 'section-choice-list');
    button.setAttribute('aria-required', String(select.required));
    label.id = 'section-choice-label';
    label.htmlFor = button.id;
    button.setAttribute('aria-labelledby', label.id + ' section-choice-value');
    const value = document.createElement('span');
    value.id = 'section-choice-value';
    button.append(value);
    const list = document.createElement('div');
    list.id = 'section-choice-list';
    list.className = 'section-choice-list';
    list.setAttribute('role', 'listbox');
    list.setAttribute('aria-labelledby', label.id);
    list.hidden = true;
    select.before(wrapper);
    wrapper.append(select, button, list);
    select.hidden = true;
    let options = [];
    let active = -1;
    let prefix = '';
    let typedAt = 0;

    function close() {
        list.hidden = true;
        button.setAttribute('aria-expanded', 'false');
        button.removeAttribute('aria-activedescendant');
        wrapper.classList.remove('is-open', 'is-above');
    }
    function highlight(index) {
        active = Math.max(0, Math.min(options.length - 1, index));
        [...list.children].forEach((item, i) => item.classList.toggle('is-active', i === active));
        const item = list.children[active];
        if (item) {
            button.setAttribute('aria-activedescendant', item.id);
            if (item.offsetTop < list.scrollTop) list.scrollTop = item.offsetTop;
            else if (item.offsetTop + item.offsetHeight > list.scrollTop + list.clientHeight) {
                list.scrollTop = item.offsetTop + item.offsetHeight - list.clientHeight;
            }
        }
    }
    function choose(index) {
        if (!options[index] || select.matches(':disabled')) return;
        select.value = options[index].value;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        sync();
        close();
        button.focus();
    }
    function positionList() {
        const rect = button.getBoundingClientRect();
        if (rect.bottom < 0 || rect.top > window.innerHeight) { close(); return; }
        const below = window.innerHeight - rect.bottom - 12;
        const above = rect.top - 12;
        const flip = below < 220 && above > below;
        wrapper.classList.toggle('is-above', flip);
        list.style.maxHeight = Math.max(40, Math.min(360, flip ? above : below)) + 'px';
    }
    function open() {
        if (button.disabled) return;
        options = [...select.options].filter(option => option.value !== '' && !option.disabled);
        list.replaceChildren(...options.map((option, index) => {
            const item = document.createElement('div');
            item.id = 'section-choice-option-' + index;
            item.className = 'section-choice-option';
            item.setAttribute('role', 'option');
            item.setAttribute('aria-selected', String(option.value === select.value));
            item.textContent = option.textContent;
            item.addEventListener('click', () => choose(index));
            return item;
        }));
        if (!options.length) return;
        positionList();
        list.hidden = false;
        wrapper.classList.add('is-open');
        button.setAttribute('aria-expanded', 'true');
        const selected = options.findIndex(option => option.value === select.value);
        highlight(selected < 0 ? 0 : selected);
    }
    function sync() {
        value.textContent = select.selectedOptions[0]?.textContent || 'Izvēlies apakšiedaļu';
        button.disabled = select.matches(':disabled');
        if (select.value) button.removeAttribute('aria-invalid');
        close();
    }
    button.addEventListener('click', () => { if (list.hidden) open(); else close(); });
    list.addEventListener('mousedown', event => {
        if (event.target.closest('.section-choice-option')) event.preventDefault();
    });
    button.addEventListener('keydown', event => {
        if (event.key === 'Tab') { close(); return; }
        if (event.key === 'Escape') { event.preventDefault(); close(); return; }
        if (['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) {
            event.preventDefault();
            const wasClosed = list.hidden;
            if (wasClosed) open();
            if (!options.length) return;
            if (event.key === 'Home') highlight(0);
            else if (event.key === 'End') highlight(options.length - 1);
            else if (!wasClosed) highlight(active + (event.key === 'ArrowDown' ? 1 : -1));
            return;
        }
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            if (list.hidden) open(); else choose(active);
            return;
        }
        if (event.key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey) {
            event.preventDefault();
            const now = Date.now();
            prefix = (now - typedAt > 1000 ? '' : prefix) + event.key.toLocaleLowerCase('lv');
            typedAt = now;
            if (list.hidden) open();
            const index = options.findIndex(option => option.textContent.toLocaleLowerCase('lv').startsWith(prefix));
            if (index >= 0) highlight(index);
        }
    });
    select.addEventListener('change', sync);
    select.addEventListener('invalid', event => {
        event.preventDefault();
        sync();
        button.setAttribute('aria-invalid', 'true');
        button.focus();
        open();
    });
    document.addEventListener('pointerdown', event => { if (!wrapper.contains(event.target)) close(); });
    wrapper.addEventListener('focusout', event => { if (!wrapper.contains(event.relatedTarget)) close(); });
    window.addEventListener('resize', close);
    window.addEventListener('scroll', () => { if (!list.hidden) positionList(); }, { passive: true });
    const observer = new MutationObserver(sync);
    observer.observe(select, {
        childList: true, subtree: true, attributes: true, attributeFilter: ['disabled'],
    });
    for (let parent = wrapper.parentElement; parent; parent = parent.parentElement) {
        if (parent.tagName === 'FIELDSET') observer.observe(parent, { attributes: true, attributeFilter: ['disabled'] });
    }
    document.getElementById('job-form').addEventListener('reset', () => queueMicrotask(sync));
    sync();
    return sync;
}
