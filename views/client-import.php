<section class="card" id="client-import-panel" hidden aria-label="Klientu imports no VFP">
    <h2>1. Izvēlies VFP klientu failu</h2>
    <p>Klientus veido un labo VFP sistēmā. Imports pārņem VFP klientu ID, pievieno jaunus klientus un atjaunina esošo klientu rekvizītus. Failā neesošos klientus neizdzēš.</p>
    <p class="import-links"><a class="text-button" href="api.php?action=client_export_tool">Lejupielādēt VFP eksporta procedūru</a><a class="text-button" href="api.php?action=client_import_template">Lejupielādēt CSV galvenes paraugu</a></p>
    <form id="client-import-form" class="report-toolbar">
        <label for="client-import-file">Klientu CSV fails</label>
        <input id="client-import-file" name="file" type="file" accept=".csv,text/csv" required>
        <button class="primary" id="client-import-check" type="submit">Pārbaudīt failu</button>
    </form>
    <p class="muted">UTF-8 CSV ar galveni un semikolu atdalītāju. Līdz 2 MB un 10 000 klientiem. Pārbaude datus nesaglabā.</p>
    <section id="client-import-preview" hidden aria-label="Importa pārbaudes rezultāts">
        <h2>2. Pārbaudi izmaiņas un apstiprini importu</h2>
        <p id="client-import-filename"></p>
        <div class="import-counts" id="client-import-counts" role="status"></div>
        <div class="report-scroll"><table class="jobs-table"><thead><tr><th>VFP ID</th><th>Klients</th><th>Darbība</th></tr></thead><tbody id="client-import-sample"></tbody></table></div>
        <p class="muted">Parādīti pirmie 20 klienti. Pārbaude derīga 15 minūtes. Importa laikā tiek pārbaudīts, vai dati pa šo laiku nav mainījušies.</p>
        <button id="client-import-commit" class="primary" type="button">Importēt klientus</button>
    </section>
</section>
<section class="card import-history" id="client-import-history-panel" hidden aria-label="Klientu importu vēsture">
    <h2>Pēdējie klientu importi</h2>
    <p id="client-import-history-count" role="status"></p>
    <div class="report-scroll"><table class="jobs-table"><thead><tr><th>Nr.</th><th>Datums un laiks</th><th>Izpildītājs</th><th>Fails</th><th>Kopā</th><th>Pievienoti</th><th>Atjaunināti</th><th>Bez izmaiņām</th></tr></thead><tbody id="client-import-history"></tbody></table></div>
</section>
