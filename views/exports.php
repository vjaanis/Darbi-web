<section id="exports-panel" class="card" hidden aria-label="Darbu atlase eksportam">
    <form id="exports-filter" class="export-filters">
        <label>Darba autors<select id="export-author"><option value="0">Visi autori</option><option value="-1">Bez piesaistīta autora</option></select></label>
        <label class="export-search">Meklēt<input type="search" id="export-query" maxlength="100" placeholder="Numurs, klients vai darba apraksts"></label>
        <button class="secondary" type="submit">Atlasīt</button>
    </form>
    <p class="muted">Atzīmē vajadzīgos darbus. Atlase saglabājas, pāršķirot lapas, un tiek notīrīta, mainot filtru. Vienā eksportā līdz 200 darbiem.</p>
    <div class="report-toolbar">
        <button id="export-create" class="primary" type="button" disabled>Eksportēt atzīmētos</button>
        <span id="export-selected" role="status">Atzīmēti: 0</span>
        <button id="export-clear" class="secondary" type="button" disabled>Notīrīt atlasi</button>
        <a id="export-download" class="secondary" hidden>Lejupielādēt CSV</a>
    </div>
    <p id="export-count" role="status"></p>
    <div class="report-scroll">
        <table class="jobs-table exports-table">
            <thead><tr><th><input id="export-all" type="checkbox" aria-label="Atzīmēt eksportējamos darbus šajā lapā"></th><th>Nr.</th><th>Sākums</th><th>Autors</th><th>Klients</th><th>Darba apraksts</th><th>Summa</th><th>Darba statuss</th></tr></thead>
            <tbody id="export-rows"></tbody>
        </table>
    </div>
    <div class="report-toolbar"><button id="export-prev" class="secondary" type="button">Iepriekšējā</button><span id="export-page"></span><button id="export-next" class="secondary" type="button">Nākamā</button></div>
    <p class="muted">CSV: UTF-8, bez galvenes, tukšs pirmais lauks numuram, atdalītājs semikols, datumi DD.MM.GGGG. Darba apraksts eksportā līdz 254 rakstzīmēm. Eksportētie darbi darbu sarakstos vairs netiek rādīti un nav labojami. Failu var atkārtoti lejupielādēt eksportu vēsturē. Eksporta atzīme neapstiprina importu VFP sistēmā.</p>
</section>
<section id="export-history-panel" class="card export-history" hidden aria-label="Eksportu vēsture">
    <h2>Eksportu vēsture</h2>
    <form id="export-history-search" class="report-toolbar"><label for="history-query">Meklēt eksportu</label><input id="history-query" type="search" maxlength="100" placeholder="Eksporta numurs, datums DD.MM.GGGG vai izpildītājs"><button class="secondary" type="submit">Meklēt</button></form>
    <p id="history-count" role="status"></p>
    <div class="report-scroll"><table class="jobs-table"><thead><tr><th>Eksporta nr.</th><th>Izveidots</th><th>Izpildītājs</th><th>Darbu skaits</th><th>Darbu numuri</th><th>Fails</th></tr></thead><tbody id="history-rows"></tbody></table></div>
    <div class="report-toolbar"><button id="history-prev" class="secondary" type="button">Iepriekšējā</button><span id="history-page"></span><button id="history-next" class="secondary" type="button">Nākamā</button></div>
</section>
