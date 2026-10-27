/**
 * Rediģējams izvēles lauks: bulta rāda visu vēsturi, rakstīšana ļauj ievadīt jaunu vērtību.
 * Vēstures avots ir datalist, ko pēc klienta izvēles aizpilda app.js.
 */
export function initializeHistoryChoice(inputId, sourceId) {
    const input = document.getElementById(inputId);
    const source = document.getElementById(sourceId);
    const container = document.getElementById(inputId + "-choice");
    const toggle = document.getElementById(inputId + "-toggle");
    const options = document.getElementById(inputId + "-options");

    function close() {
        options.hidden = true;
        input.setAttribute("aria-expanded", "false");
        container.classList.remove("is-open");
    }

    function open(filter = "") {
        if (input.matches(":disabled")) {
            return;
        }

        const values = [...source.options]
            .map((option) => option.value)
            .filter((value) =>
                value.toLocaleLowerCase("lv").includes(filter.toLocaleLowerCase("lv")),
            );

        options.replaceChildren(...values.map((value) => new Option(value, value)));
        options.selectedIndex = -1;

        if (values.length === 0) {
            const empty = new Option("Nav sakritību — vari ievadīt jaunu vērtību", "");
            empty.disabled = true;
            options.add(empty);
        }

        options.hidden = false;
        input.setAttribute("aria-expanded", "true");
        container.classList.add("is-open");
    }

    function choose() {
        if (options.selectedIndex < 0 || options.selectedOptions[0].disabled) {
            return;
        }

        input.value = options.value;
        close();
        input.focus();
        input.dispatchEvent(new window.Event("change", { bubbles: true }));
    }

    toggle.addEventListener("click", () => {
        if (options.hidden) {
            open();
        } else {
            close();
        }
    });

    input.addEventListener("click", () => open());
    input.addEventListener("input", () => open(input.value));
    input.addEventListener("keydown", (event) => {
        if (event.key === "ArrowDown") {
            event.preventDefault();
            open();
            options.focus();
        }
        if (event.key === "Escape") {
            close();
        }
    });

    options.addEventListener("change", choose);
    options.addEventListener("keydown", (event) => {
        if (event.key === "Enter") {
            event.preventDefault();
            choose();
        }
        if (event.key === "Escape") {
            event.preventDefault();
            close();
            input.focus();
        }
    });

    document.addEventListener("click", (event) => {
        if (!container.contains(event.target)) {
            close();
        }
    });

    // Mainot klientu, iepriekš atvērtais saraksts nekavējoties jāaizver.
    new window.MutationObserver(close).observe(source, { childList: true });
}
