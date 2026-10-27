# Izmaiņu vēsture

## Pēc 1.3.1 — 27.10.2026

- Pārējās lietotnes sadaļās burtu izmēri palielināti par 2 px, saglabājot 23 px lapu virsrakstus un login izskatu. Pamatteksts 16 px, ievades lauki/izvēlne 15 px, kontu sadaļas pamatteksts 18 px.
- Atjauninātas stilu kešatmiņas versijas `index.php` un `users.php`.

- Pieteikšanās ievades lauki samazināti līdz aptuveni 30 simbolu vizuālajam platumam, saglabājot garāku vērtību ievadi.
- Pieteikšanās lapa pārveidota par kompaktu centrētu formu ar atsevišķu `assets/login.css`, pielāgojumu šauriem ekrāniem un redzamu versijas numuru.
- Pieteikšanās PHP loģika un lietotāju kontu lapas stili saglabāti nemainīti.
- Izvietots Raspberry Pi; kompetence.lv pārnešanai vajadzīgi `login.php` un `assets/login.css`.

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
