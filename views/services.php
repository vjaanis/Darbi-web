<section class="card" id="services-panel" hidden aria-label="Pakalpojumu pārvaldība">
    <h2 id="service-form-title">Pievienot pakalpojumu</h2>
    <p>Šie ieraksti veido darba ievades pakalpojumu veidu, apakšiedaļu un mērvienību izvēlnes. Valoda un veids var būt tukši atsevišķām mērvienību rindām. Izmaiņas neizmaina iepriekš saglabātos darbus.</p>
    <form id="service-form">
        <fieldset id="service-fields">
            <div class="grid two">
                <div class="field"><label for="service-lng">Valoda</label><input id="service-lng" name="lng" maxlength="3" placeholder="LV"></div>
                <div class="field"><label for="service-tips">Pakalpojuma veids</label><input id="service-tips" name="tips" maxlength="50"></div>
                <div class="field"><label for="service-sdala">Apakšiedaļa</label><input id="service-sdala" name="sdala" maxlength="220"></div>
                <div class="field"><label for="service-mervien">Mērvienība</label><input id="service-mervien" name="mervien" maxlength="50"></div>
            </div>
            <div class="report-toolbar">
                <button class="primary" id="service-save" type="submit">Pievienot</button>
                <button id="service-cancel" type="button">Notīrīt formu</button>
            </div>
        </fieldset>
    </form>
    <div class="report-toolbar">
        <label for="services-filter">Meklēt</label>
        <input id="services-filter" type="search" placeholder="Valoda, veids, apakšiedaļa, mērvienība vai Nr." autocomplete="off">
        <button id="services-refresh" type="button">Atjaunot sarakstu</button>
    </div>
    <p id="services-count" class="muted" role="status"></p>
    <div class="report-scroll"><table class="jobs-table">
        <thead><tr><th scope="col">Nr.</th><th scope="col">Valoda</th><th scope="col">Veids</th><th scope="col">Apakšiedaļa</th><th scope="col">Mērvienība</th><th scope="col">Darbības</th></tr></thead>
        <tbody id="services-list"></tbody>
    </table></div>
</section>
