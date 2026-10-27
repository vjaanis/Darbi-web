<section id="jobs-panel" class="card" hidden aria-label="Mani darbi">
    <form id="jobs-search" class="report-toolbar">
        <label for="jobs-query">Meklēt savos darbos</label>
        <input type="search" id="jobs-query" maxlength="100" placeholder="Numurs, klients vai darba apraksts">
        <button class="primary" type="submit">Meklēt</button>
        <a href="./" class="secondary">＋ Jauns darbs</a>
    </form>
    <p id="jobs-count" role="status"></p>
    <div class="report-scroll">
        <table class="jobs-table">
            <thead><tr><th>Nr.</th><th>Sākums</th><th>Termiņš</th><th>Klients</th><th>Darba apraksts</th><th>Summa</th><th>Statuss</th><th>Darbības</th></tr></thead>
            <tbody id="jobs-rows"></tbody>
        </table>
    </div>
    <div class="report-toolbar">
        <button type="button" id="jobs-prev" class="secondary">Iepriekšējā</button>
        <span id="jobs-page"></span>
        <button type="button" id="jobs-next" class="secondary">Nākamā</button>
    </div>
</section>
