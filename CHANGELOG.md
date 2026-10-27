# Izmaiņu vēsture

## Pēc 1.1.1 — 27.10.2026

- Pievienots `client.act` ar sākotnējo un noklusēto vērtību `1`.
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
