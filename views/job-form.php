<form id="job-form">
    <fieldset id="job-fields" class="job-grid">
        <section class="card client-card" aria-labelledby="client-heading">
            <div class="section-heading">
                <h2 id="client-heading">1. Klients</h2>
            </div>

            <div class="field" id="client-search-field">
                <label for="client-search">Klienta nosaukums</label>
                <input id="client-search" type="search" placeholder="Meklēt klientu…"
                       autocomplete="off" aria-controls="client-results">
                <select id="client-results" class="client-results" size="6"
                        aria-label="Atrasto klientu saraksts" hidden></select>
            </div>

            <div id="client-details" class="client-details">
                <span>Klients vēl nav izvēlēts</span>
            </div>

            <div class="grid two">
                <div class="field">
                    <label for="parstv">Pārstāvis</label>
                    <div class="history-choice" id="parstv-choice">
    <input name="parstv" id="parstv" maxlength="30" autocomplete="off"
           role="combobox" aria-expanded="false" aria-haspopup="listbox"
           aria-controls="parstv-options" disabled>
    <button type="button" id="parstv-toggle" class="history-toggle"
            aria-label="Izvēlēties pārstāvi" aria-controls="parstv-options" disabled>▾</button>
    <select id="parstv-options" class="history-options" size="6"
            aria-label="Iepriekš izmantotie: pārstāvi" hidden></select>
</div>
                    <datalist id="representatives"></datalist>
                </div>
                <div class="field">
                    <label for="diviz">Divīzija / struktūrvienība</label>
                    <div class="history-choice" id="diviz-choice">
    <input name="diviz" id="diviz" maxlength="30" autocomplete="off"
           role="combobox" aria-expanded="false" aria-haspopup="listbox"
           aria-controls="diviz-options" disabled>
    <button type="button" id="diviz-toggle" class="history-toggle"
            aria-label="Izvēlēties divīziju" aria-controls="diviz-options" disabled>▾</button>
    <select id="diviz-options" class="history-options" size="6"
            aria-label="Iepriekš izmantotie: divīziju" hidden></select>
</div>
                    <datalist id="divisions"></datalist>
                </div>
            </div>
        </section>

        <fieldset id="client-followup-fields" class="dependent-fields" disabled>
        <section class="card service-card" aria-labelledby="service-heading">
            <div class="section-heading">
                <h2 id="service-heading">2. Pakalpojums</h2>
            </div>

            <div class="grid service-choices">
                <div class="field">
                    <label for="darb_veids">Pakalpojuma veids</label>
                    <select id="darb_veids" name="darb_veids" required disabled>
                        <option value="">Vispirms izvēlies klientu</option>
                    </select>
                </div>
                <div class="field">
                    <label for="darb_tip1">Apakšiedaļa</label>
                    <select id="darb_tip1" name="darb_tip1" required disabled>
                        <option value="">Izvēlies pakalpojuma veidu</option>
                    </select>
                </div>
            </div>

            <div class="field">
                <label for="darbnosauk">Darba apraksts</label>
                <textarea id="darbnosauk" name="darbnosauk" rows="2"
                          maxlength="508" placeholder="Kas jāpaveic?" required></textarea>
            </div>

            <div class="field">
                <label for="mervien">Mērvienība</label>
                <select id="mervien" name="mervien" required disabled>
                    <option value="">Izvēlies apakšiedaļu</option>
                </select>
            </div>
        </section>

        <section class="card amount-card" aria-labelledby="amount-heading">
            <div class="section-heading">
                <h2 id="amount-heading">3. Apjoms un cena</h2>
            </div>

            <div class="amount-fields">
                <div class="field">
                    <label for="daudz">Daudzums</label>
                    <input id="daudz" name="daudz" type="number" min="0.01"
                           max="9999.99" step="0.01" value="1" required>
                </div>
                <div class="field">
                    <label for="cena">Cena, €</label>
                    <input id="cena" name="cena" type="number" min="0"
                           max="999999.99" step="0.0001" value="0" required>
                </div>
                <div class="field">
                    <label for="atlaide">Atlaide, %</label>
                    <input id="atlaide" name="atlaide" type="number" min="0"
                           max="100" step="1" value="0" required>
                </div>
                <div class="field amount-output">
                    <label for="summary-total">Summa</label>
                    <output id="summary-total" for="daudz cena atlaide mervien">0,00 €</output>
                </div>
            </div>
            <p id="calculation-note" class="calculation-note">Pēc atlaides piemērošanas</p>
        </section>

        <section class="card dates-card" aria-labelledby="dates-heading">
            <div class="section-heading">
                <h2 id="dates-heading">4. Datumi un piezīmes</h2>
            </div>

            <div class="date-fields">
                <div class="field">
                    <label for="sak_dat">Sākuma datums</label>
                    <div class="date-control">
                    <input id="sak_dat" name="sak_dat" type="text" class="date-input" inputmode="numeric"
                           placeholder="DD.MM.GGGG" maxlength="10" pattern="[0-9]{2}\.[0-9]{2}\.[0-9]{4}" required>
                    <span class="calendar-icon" aria-hidden="true">▦</span>
                    <input id="sak_dat-calendar" type="date" class="calendar-picker" min="1000-01-01" max="9999-12-31"
                           aria-label="Izvēlēties sākuma datumu kalendārā" title="Atvērt sākuma datuma kalendāru">
                    </div>
                </div>
                <div class="field">
                    <label for="termins">Termiņš</label>
                    <div class="date-control">
                    <input id="termins" name="termins" type="text" class="date-input" inputmode="numeric"
                           placeholder="DD.MM.GGGG" maxlength="10" pattern="[0-9]{2}\.[0-9]{2}\.[0-9]{4}">
                    <span class="calendar-icon" aria-hidden="true">▦</span>
                    <input id="termins-calendar" type="date" class="calendar-picker" min="1000-01-01" max="9999-12-31"
                           aria-label="Izvēlēties termiņu kalendārā" title="Atvērt termiņa kalendāru">
                    </div>
                </div>
                <div class="completion-field">
                    <label class="check">
                        <input type="checkbox" id="gat" name="gat"> Pabeigts
                    </label>
                    <div class="date-control">
                    <input type="text" id="g_dat" name="g_dat" class="date-input" inputmode="numeric"
                           placeholder="DD.MM.GGGG" maxlength="10" pattern="[0-9]{2}\.[0-9]{2}\.[0-9]{4}"
                           aria-label="Pabeigšanas datums" hidden>
                    <span class="calendar-icon" aria-hidden="true">▦</span>
                    <input id="g_dat-calendar" type="date" class="calendar-picker" min="1000-01-01" max="9999-12-31"
                           aria-label="Izvēlēties pabeigšanas datumu kalendārā" title="Atvērt pabeigšanas datuma kalendāru">
                    </div>
                </div>
            </div>

            <div class="field comment-field">
                <label for="koment">Komentārs</label>
                <textarea id="koment" name="koment" maxlength="250" rows="2"></textarea>
            </div>
        </section>
        </fieldset>
    </fieldset>
</form>
