/**
 * Vienota PHP API izsaukšana. GET lasa datus, POST nosūta JSON objektu.
 */
export async function request(action, parameters = {}, body) {
    const query = new URLSearchParams({ action, ...parameters });
    const options = { headers: { Accept: "application/json" } };

    if (body !== undefined) {
        options.method = "POST";
        options.headers["Content-Type"] = "application/json";
        options.headers["X-CSRF-Token"] = document.querySelector('meta[name="csrf-token"]')?.content || "";
        options.body = JSON.stringify(body);
    }

    const response = await fetch("api.php?" + query, options);
    let result;

    try {
        result = await response.json();
    } catch {
        throw new Error("Serveris neatgrieza derīgu atbildi. Pārbaudi PHP kļūdu žurnālu.");
    }

    if (!response.ok) {
        if (response.status === 401) {
            window.location.assign("login.php");
        }
        throw new Error(result.error || "Darbību neizdevās pabeigt.");
    }

    return result;
}
