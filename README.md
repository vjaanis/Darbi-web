# Darbi-web

PHP un JavaScript lietotne darbu ievadei un datu apmaiņai ar Visual FoxPro.

## Saglabātās kopijas statuss

Repozitorija sākums ir 2026. gada 21.–22. oktobrī saglabātā avota kopija bez servera konfigurācijas un datiem. Tā nav pilna pašreizējā kompetence.lv servera rezerves kopija. Pirms izvietošanas salīdzināt ar aktuālajiem servera failiem.

Iekļauti divi labojumi: klientu meklēšana bez `LIMIT 40`, lai tukšs meklēšanas lauks atgrieztu visus klientus, un mērvienību SQL parametra pārveidošana uz `utf8mb4`, kas pārbaudīta iepriekšējā servera pārcelšanā. Klientu labojuma darbība pašreizējā serverī vēl jāpārbauda.

## Saturs

- `app/`, `assets/`, `views/` un saknes PHP faili — lietotne.
- `migrations/` — pieejamās eksporta un klientu importa migrācijas; tās nav pilna datubāzes sākotnējā shēma.
- `app/vfp/eksportet_klientus.prg` — klientu eksportēšana no VFP.
- `tools/vfp/` — darbu importēšanas rīki un to instrukcija.
- `docs/CSV-formats.lv.md` — darbu eksporta CSV formāts.

## Konfigurācija

Nepieciešams PHP 8.2 ar PDO MySQL un lietotnes MariaDB/MySQL datubāze. Lokāli izveidot `config.php` pēc `config.example.php` parauga. Īstā konfigurācija, paroles, datubāzes saturs, CSV eksporti un DBF faili Git netiek glabāti.

Servera `.htaccess` un pārējie hostinga iestatījumi šajā kopijā nav iekļauti; tos saglabāt atsevišķi. Šī kopija nav paredzēta automātiskai pašreizējās vietnes pārrakstīšanai.
