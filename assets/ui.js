export function element(id) {
    const result = document.getElementById(id);

    if (!result) {
        throw new Error("Lapā trūkst elementa: " + id);
    }

    return result;
}

export function showNotice(message, isError = false) {
    const notice = element("notice");
    notice.textContent = message;
    notice.className = isError ? "error" : "";
    notice.hidden = false;
    notice.setAttribute("role", isError ? "alert" : "status");

    if (isError) {
        notice.scrollIntoView({ behavior: "smooth", block: "center" });
    }
}

export function replaceOptions(id, values, placeholder) {
    const select = element(id);
    select.replaceChildren(new Option(placeholder, ""));

    for (const value of values) {
        select.add(new Option(value, value));
    }

    select.disabled = values.length === 0;
}

export function replaceSuggestions(id, values) {
    element(id).replaceChildren(...values.map((value) => new Option(value, value)));
}

export function formatMoney(value) {
    return new Intl.NumberFormat("lv-LV", {
        style: "currency",
        currency: "EUR",
        minimumFractionDigits: 2,
        maximumFractionDigits: 4,
    }).format(Number(value));
}

export function rigaToday() {
    return new Intl.DateTimeFormat("sv-SE", {
        timeZone: "Europe/Riga",
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
    }).format(new Date());
}
