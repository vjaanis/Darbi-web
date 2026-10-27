<section class="card" id="clients-panel" hidden aria-label="Klientu redzamības pārvaldība">
    <h2>Klienti darba ievades izvēlnē</h2>
    <p>Atzīmē klientus, kurus rādīt darba ievades izvēlnē. Izmaiņas saglabājas uzreiz. Noņemot atzīmi, klienta dati un iepriekšējie darbi saglabājas.</p>
    <label for="clients-filter">Meklēt pēc nosaukuma, reģistrācijas koda vai ID</label>
    <input id="clients-filter" type="search" autocomplete="off" placeholder="Meklēt klientu…">
    <p id="clients-count" class="muted" role="status"></p>
    <div class="report-scroll">
        <table class="jobs-table">
            <thead><tr><th scope="col">Rādīt ievadē</th><th scope="col">ID</th><th scope="col">Klients</th><th scope="col">Reģistrācijas kods</th><th scope="col">Valoda</th></tr></thead>
            <tbody id="clients-list"></tbody>
        </table>
    </div>
</section>
