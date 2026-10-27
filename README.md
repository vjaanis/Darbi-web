# Darbi-web 1.4.1

PHP un JavaScript lietotne darbu ievadei un datu apmaiņai ar Visual FoxPro.

Administratora sadaļā **Pakalpojumi** (`?view=services`) var apskatīt, meklēt, pievienot, labot un dzēst `mnu_dati` ierakstus: valodu, veidu, apakšiedaļu un mērvienību. `nr` tiek piešķirts automātiski. Atsevišķām mērvienību rindām drīkst atstāt tukšu valodu vai veidu. Labojumi ietekmē turpmāko darba ievades atlasi; iepriekš saglabātos darbus nepārraksta. Dzēšanai ir konkrēta ieraksta apstiprinājums; novecojuša ieraksta labošana vai dzēšana tiek bloķēta.

Pakalpojumu CRUD izvietošanai citā serverī atjaunināt `api.php`, `index.php`, `app/services.php`, `assets/services.js` un `views/services.php`. Tabulas struktūra nav mainīta; tai vajadzīgs esošais primārais lauks `nr` ar `AUTO_INCREMENT` un lietotnes datubāzes kontam jābūt `SELECT`, `INSERT`, `UPDATE`, `DELETE` tiesībām uz `mnu_dati`. Raspberry Pi izvietots un pārbaudīts 27.10.2026. kompetence.lv šī funkcija vēl nav izvietota.

Administratora sadaļā **Klienti** (`?view=clients`) var ar izvēles rūtiņām noteikt, kurus klientus rādīt darba ievades izvēlnē. Lauks `client.act` ir `1` aktīviem un `0` paslēptiem klientiem. Izmaiņas saglabājas uzreiz. Klienti un iepriekšējie darbi netiek dzēsti; esoša darba rediģēšanai klientu joprojām var nolasīt pēc ID. VFP imports esošo `act` saglabā, jauni klienti pēc noklusējuma ir aktīvi.

Pirms šīs funkcijas izvietošanas citā serverī vienreiz izpildīt `migrations/2026-10-27-client-act.sql`, tad atjaunināt `api.php`, `index.php`, `app/clients.php`, `assets/clients.js` un `views/clients.php`. Migrācija pievieno lauku un sākotnēji visus klientus atzīmē kā aktīvus. Raspberry Pi migrācija un izvietošana pabeigta 27.10.2026; atkārtoti tur migrāciju nepalaist.

Pašreizējā versija: **1.4.1**. Versiju nosaka `app/version.php`, tā redzama pieteikšanās, darba ievades un kontu lapās. Izmaiņas aprakstītas `CHANGELOG.md`; Git izlaiduma atzīme ir `v1.4.1`.

Soli pa solim instrukcija kompetence.lv atjaunināšanai: [kompetence-1.3.1.lv.md](docs/kompetence-1.3.1.lv.md).

Pēc 1.3.1 ieviests kompakts pieteikšanās lapas izskats. Lai pārnestu tikai šo vizuālo labojumu uz kompetence.lv, kopēt `login.php` un `assets/login.css`. Datubāzes izmaiņas nav vajadzīgas. Lauku platums ir aptuveni 30 simboli, nevis jauns ievades garuma ierobežojums.

Raspberry Pi pārējām sadaļām burtu izmēri palielināti par 2 px, izņemot esošos 23 px lapu virsrakstus; login nav mainīts. Šim vizuālajam labojumam uz kompetence.lv pārnesami `assets/style.css`, `assets/users.css`, `index.php` un `users.php`.

Pēc 1.4.1 apakšiedaļas izvēlne darba ievadē pārvietota zem pakalpojuma veida un papildināta ar rindās dalītiem gariem nosaukumiem. Izvēle darbojas arī ar bulttaustiņiem, Home/End, Enter, Escape un meklēšanu, rakstot sākumburtus. Šim labojumam pārnesami `assets/style.css`, `assets/app.js`, jaunais `assets/section-choice.js` un `index.php`. Datubāzes izmaiņas nav vajadzīgas.

## Saglabātās kopijas statuss

Repozitorija sākums ir 2026. gada 21.–22. oktobrī saglabātā avota kopija bez servera konfigurācijas un datiem. Tā nav pilna pašreizējā kompetence.lv servera rezerves kopija. Pirms izvietošanas salīdzināt ar aktuālajiem servera failiem.

Klientu meklēšanai nav `LIMIT 40` ierobežojuma: tukšs meklēšanas lauks atgriež visus aktīvos klientus. Mērvienību SQL parametrs tiek pārveidots uz `utf8mb4`. Darbība pārbaudīta Raspberry Pi; šis izlaidums kompetence.lv vēl nav izvietots.

## Saturs

- `app/`, `assets/`, `views/` un saknes PHP faili — lietotne.
- `migrations/` — pieejamās eksporta un klientu importa migrācijas; tās nav pilna datubāzes sākotnējā shēma.
- `app/vfp/eksportet_klientus.prg` — klientu eksportēšana no VFP.
- `tools/vfp/` — darbu importēšanas rīki un to instrukcija.
- `docs/CSV-formats.lv.md` — darbu eksporta CSV formāts.

## Konfigurācija

Nepieciešams PHP 8.2 ar PDO MySQL un lietotnes MariaDB/MySQL datubāze. Lokāli izveidot `config.php` pēc `config.example.php` parauga. Īstā konfigurācija, paroles, datubāzes saturs, CSV eksporti un DBF faili Git netiek glabāti.

Servera `.htaccess` un pārējie hostinga iestatījumi šajā kopijā nav iekļauti; tos saglabāt atsevišķi. Šī kopija nav paredzēta automātiskai pašreizējās vietnes pārrakstīšanai.
