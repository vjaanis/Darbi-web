# Izmaiņu vēsture

## 1.4.7 — 24.11.2026

- Pieteikšanās sesijas tiek glabātas Darbi datubāzē, lai hostinga PHP sesiju glabātuves kļūme nevarētu izraisīt kļūdu “Nederīgs pieprasījums”.

## 1.4.6 — 18.11.2026

- Apvienots 1.4.5 optimizācijas un uzturēšanas izlaidums ar katalogu normalizāciju.
- Pakalpojuma veida, apakšiedaļas un mērvienības vērtības no `mnu_dati` tiek apgrieztas no liekām sākuma/beigu atstarpēm, pirms tās nonāk darba ievades izvēlnēs un validācijā.
- Novērsta kļūda, kad katalogā saglabāta neredzama atstarpe izraisīja paziņojumu par klienta valodai neatbilstošu pakalpojumu.
- Datubāzes shēmas izmaiņas nav vajadzīgas.
- Lietotnes versija atjaunināta uz `1.4.6`.

## 1.4.5 — 18.11.2026

- Darba formā summas priekšskatījumu rēķina tikai pārlūks (`assets/calculation.js` ir identisks `app/calculation.php`); vairs netiek sūtīts `calculate` pieprasījums pēc katra taustiņa. Saglabājot PHP summu pārrēķina kā līdz šim.
- Darba formas ielādē noņemta atsevišķā `status` pārbaude (4 `COUNT(*)` vaicājumi); datubāzes kļūdu parāda pirmais īstais pieprasījums.
- Klientu meklēšana un `findClient` atgriež tikai darba formai vajadzīgos laukus `id, name, c_code, lng, address, b_acc`; bankas, kontaktu un e-pasta dati pārlūkam vairs netiek sūtīti.
- Statisko failu versijas nosaka faila maiņas laiks (`app/assets.php`); JS moduļu savstarpējie importi versiju saņem caur import map. Ar roku rakstītās `?v=` virknes noņemtas.
- `index.php` sūta `Content-Security-Policy` galveni; vienīgo inline skriptu (import map) atļauj vienreizējs nonce.
- Galvenā izvēlne pārnesta uz kopīgu `views/topbar.php`; tā redzama arī kontu lapā `users.php` (administratoram). Atsevišķā kontu lapas poga **Izrakstīties** aizstāta ar izvēlnes pogu.
- CSS apvienots: `assets/exports.css`, `assets/client-import.css` un `assets/users.css` iekļauti `assets/style.css`; izņemts nelietotā jaunā klienta dialoga stils. Kontu lapa izmanto kopīgo stilu (pamatteksts 16 px). `assets/login.css` paliek atsevišķs.
- Pārbaudīts Raspberry Pi: darba forma (klients → veids → apakšiedaļa → mērvienība, summa), labošana, pārskats, datu eksports, klientu imports, klienti, pakalpojumi, konti; CSP pārkāpumu nav. Datubāzes izmaiņas nav vajadzīgas.
- Lietotnes versija atjaunināta uz `1.4.5`.

## 1.4.3 — 27.10.2026

- Darbu pārskatā blakus **Labot** un **Dzēst** pievienota darbība **Kopēt**.
- Kopēšana aizpilda jauna darba formu ar klientu, pakalpojumu, apakšiedaļu, mērvienību, aprakstu, pārstāvi, divīziju, daudzumu, cenu, atlaidi un komentāru.
- Jaunais sākuma datums ir šodiena Rīgas laika joslā; termiņš un pabeigšanas datums tiek notīrīti, **Pabeigts** nav atzīmēts. Summa tiek pārrēķināta.
- Kopija tiek saglabāta kā jauns ieraksts tikai pēc apstiprināšanas; sākotnējais darbs netiek mainīts. Avota lasīšanai saglabājas esošā savu neeksportēto darbu piekļuves pārbaude.
- Pārbaudīta kopijas atvēršana/saglabāšana un parastās labošanas datumu saglabāšana izolētā pārlūka plūsmā; pievienoti kopējamo datu testi.
- Lietotnes versija GitHub un Raspberry Pi atjaunināta uz `1.4.3`.

## 1.4.2 — 27.10.2026

- Darba ievadē apakšiedaļas izvēlne pārvietota zem pakalpojuma veida visā pakalpojumu bloka platumā.
- Gari apakšiedaļu nosaukumi sarakstā dalās vairākās rindās, saraksts ritinās un atveras logā pieejamajā vietā.
- Saglabāts sākotnējais select lauks un obligātās izvēles validācija. Peles un tastatūras izvēle nosūta to pašu apakšiedaļas vērtību un ierosina esošo mērvienību ielādi.
- Pārbaudīts Raspberry Pi ar regulētās jomas garajiem ECHA/REACH nosaukumiem. Datu izmaiņas nav veiktas.
- Lietotnes versija GitHub un Raspberry Pi atjaunināta uz `1.4.2`.

## 1.4.1 — 27.10.2026

- Pārējās lietotnes sadaļās burtu izmēri palielināti par 2 px, saglabājot 23 px lapu virsrakstus un login izskatu. Pamatteksts 16 px, ievades lauki/izvēlne 15 px, kontu sadaļas pamatteksts 18 px.
- Atjauninātas stilu kešatmiņas versijas `index.php` un `users.php`.

- Pieteikšanās ievades lauki samazināti līdz aptuveni 30 simbolu vizuālajam platumam, saglabājot garāku vērtību ievadi.
- Pieteikšanās lapa pārveidota par kompaktu centrētu formu ar atsevišķu `assets/login.css`, pielāgojumu šauriem ekrāniem un redzamu versijas numuru.
- Pieteikšanās PHP loģika saglabāta nemainīta; login izmanto atsevišķu stilu failu.
- Vizuālie labojumi izvietoti Raspberry Pi. Pilnai pārejai no 1.3.1 vajadzīgi `login.php`, `assets/login.css`, `assets/style.css`, `assets/users.css`, `index.php`, `users.php` un `app/version.php`. Jauna datubāzes migrācija nav vajadzīga.
- Git izlaiduma versija atjaunināta uz `1.4.1`; Raspberry Pi versijas numurs šajā Git izlaiduma solī nav mainīts.

## 1.3.1 — 27.10.2026

- Administratoriem aktivizēta izvēlne **Pakalpojumi** ar `mnu_dati` ierakstu apskati, meklēšanu, pievienošanu, labošanu un dzēšanu.
- Git izlaiduma versija atjaunināta uz `1.3.1`.
- Lauku ierobežojumi atbilst tabulai: valoda 3, veids 50, apakšiedaļa 220 un mērvienība 50 rakstzīmes.
- Saglabāta atsevišķu mērvienību rindu darbība ar tukšu valodu/veidu.
- POST, CSRF un administratora pārbaudes aizsargā izmaiņas; ieraksta versija un transakcija novērš pa šo laiku mainītu datu pārrakstīšanu/dzēšanu.
- Izvietots Raspberry Pi un pārbaudīts pagaidu tabulās; visi 86 īstie ieraksti saglabāti nemainīti. Tabulas migrācija nav vajadzīga.

## 1.2.1 — 27.10.2026

- Pievienots `client.act` ar sākotnējo un noklusēto vērtību `1`.
- Lietotnes versijas numurs atjaunināts uz `1.2.1`.
- Administratoriem aktivizēta izvēlne **Klienti**, kur redzamību ievadē pārvalda ar izvēles rūtiņām.
- Darba ievades atlase rāda tikai aktīvos klientus, saglabājot iepriekšējo darbu datus un iespēju nolasīt klientu pēc ID.
- Izmaiņu saglabāšanu aizsargā administratora tiesības, POST un CSRF pārbaudes. VFP klientu imports esošo redzamības iestatījumu nepārraksta.
- Izvietots un pārbaudīts Raspberry Pi; kompetence.lv šīs izmaiņas vēl nav izvietotas.

## 1.1.1 — 27.10.2026

- Klientu izvēlnes vaicājumam noņemts 40 rezultātu ierobežojums. Tukšs meklēšanas lauks atgriež visus klientus.
- Mērvienību vaicājumā parametrs skaidri pārveidots uz `utf8mb4`, novēršot jaunajā serverī konstatēto kodējuma kļūdu.
- Ieviests kopīgs lietotnes versijas numurs un tā attēlojums pieteikšanās un darba ievades lapās.
- GitHub saglabāts lietotnes avots, VFP importa/eksporta rīki un dokumentācija bez servera konfigurācijas un datubāzes datiem.

Šis izlaidums sagatavots no pieejamās avota kopijas; tas vēl nav izvietots vai salīdzināts ar pašreizējo kompetence.lv serveri.
