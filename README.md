# Darbi-web 1.2.1

PHP un JavaScript lietotne darbu ievadei un datu apmaiņai ar Visual FoxPro.

Administratora sadaļā **Klienti** (`?view=clients`) var ar izvēles rūtiņām noteikt, kurus klientus rādīt darba ievades izvēlnē. Lauks `client.act` ir `1` aktīviem un `0` paslēptiem klientiem. Izmaiņas saglabājas uzreiz. Klienti un iepriekšējie darbi netiek dzēsti; esoša darba rediģēšanai klientu joprojām var nolasīt pēc ID. VFP imports esošo `act` saglabā, jauni klienti pēc noklusējuma ir aktīvi.

Pirms šīs funkcijas izvietošanas citā serverī vienreiz izpildīt `migrations/2026-10-27-client-act.sql`, tad atjaunināt `api.php`, `index.php`, `app/clients.php`, `assets/clients.js` un `views/clients.php`. Migrācija pievieno lauku un sākotnēji visus klientus atzīmē kā aktīvus. Raspberry Pi migrācija un izvietošana pabeigta 27.10.2026; atkārtoti tur migrāciju nepalaist.

Pašreizējā versija: **1.2.1**. Versiju nosaka `app/version.php`, tā redzama pieteikšanās, darba ievades un kontu lapās. Izmaiņas aprakstītas `CHANGELOG.md`; Git izlaiduma atzīme ir `v1.2.1`.

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
