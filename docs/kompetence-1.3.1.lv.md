# kompetence.lv atjaunināšana uz Darbi-web 1.3.1

Mērķis: https://kompetence.lv/darba-web/

Izlaidums: https://github.com/vjaanis/Darbi-web/tree/v1.3.1

Avota mape Raspberry Pi: `\\raspberrypi\web\Darba-web`.

Šī instrukcija ietver klientu redzamības pārvaldību (`client.act`), pakalpojumu CRUD (`mnu_dati`) un versiju 1.3.1. Ja klientu aktivitāte jau ieviesta, tās iestatījumi jāsaglabā. Darbus ieteicams veikt laikā, kad lietotāji neko neievada.

## 1. Rezerves kopija

1. WinSCP lejupielādē esošos zemāk uzskaitītos maināmos failus no servera atsevišķā rezerves mapē. Jaunajiem failiem vecās kopijas nebūs.
2. phpMyAdmin izvēlies tieši Darbi-web lietotnes datubāzi un eksportē vismaz tabulas `client` un `mnu_dati` ar struktūru un datiem. Vēlama arī pilna datubāzes rezerves kopija.
3. Servera `config.php` un `.htaccess` saglabā esošos. Nekopē Raspberry Pi konfigurāciju uz kompetence.lv.

## 2. Pārbaudi datubāzes struktūru

phpMyAdmin cilnē SQL izpildi:

```sql
SHOW COLUMNS FROM client LIKE 'act';
SHOW COLUMNS FROM mnu_dati;
```

Ja pirmais vaicājums neatgriež nevienu rindu, vienreiz izpildi:

```sql
ALTER TABLE client
ADD COLUMN act TINYINT(1) NOT NULL DEFAULT 1;
```

Tas esošos klientus sākotnēji padara aktīvus. Ja `act` jau ir, šo ALTER nepildi un visas esošās 0/1 vērtības saglabā. Nepalaid `UPDATE client SET act = 1`, jo tas atkal ieslēgtu iepriekš paslēptos klientus.

Tabulai `mnu_dati` jābūt šādiem esošajiem laukiem:

| Lauks | Struktūra |
|---|---|
| `nr` | Vesels skaitlis, primārā atslēga, AUTO_INCREMENT |
| `tips` | varchar(50) |
| `sdala` | varchar(220) |
| `mervien` | varchar(50) |
| `lng` | varchar(3) |

Šai tabulai jauna migrācija nav vajadzīga. Ja struktūra atšķiras, vispirms noskaidro atšķirību; esošo tabulu neaizstāj. Lietotnes datubāzes kontam vajadzīgas SELECT, INSERT, UPDATE un DELETE tiesības uz `mnu_dati`. Ja trūkst tiesību, piešķir tās hostinga datubāzes pārvaldībā tikai vajadzīgajā apjomā.

## 3. Augšupielādē 9 failus

WinSCP atver kompetence.lv lietotnes mapi `darba-web`, kur atrodas `index.php` un servera `config.php`. Pārnes šos failus no Raspberry Pi tādās pašās relatīvajās vietās. ZIP pakotnē tie ir mapē `darba-web/` — kopē šīs mapes SATURU esošajā servera mapē, neveido vēl vienu `darba-web` apakšmapi.

Ieteicamā kopēšanas secība:

| Nr. | Fails | Nozīme |
|---|---|---|
| 1 | `app/services.php` | Pakalpojumu CRUD loģika un pārbaudes |
| 2 | `assets/services.js` | Pakalpojumu formas darbība |
| 3 | `views/services.php` | Pakalpojumu administrēšanas lapa |
| 4 | `assets/clients.js` | Klientu redzamības rūtiņas |
| 5 | `views/clients.php` | Klientu administrēšanas lapa |
| 6 | `app/clients.php` | Aktīvo klientu atlase un saglabāšana |
| 7 | `api.php` | Administratora API maršruti |
| 8 | `index.php` | Izvēlnes un lapu pieslēgšana |
| 9 | `app/version.php` | Versija 1.3.1 |

Ja klientu sadaļa jau darbojas, pilnais deviņu failu komplekts joprojām nodrošina vienotu izlaidumu. Visus failus kopē no vienas versijas. Neaugšupielādē rezerves kopijas, datubāzes eksportus vai pārbaudes skriptus publiskajā vietnes mapē.

Esošie `login.php` un `users.php` jau izmanto kopīgo `app/version.php` pēc iepriekšējā versijas attēlojuma labojuma; šajā atjauninājumā tie nav mainīti.

## 4. Pārbaude pēc atjaunināšanas

1. Atver pieteikšanās lapu ar Ctrl+F5. Jāparādās **Darbi v1.3.1**.
2. Piesakies kā administrators. Jādarbojas izvēlnēm **Klienti** un **Pakalpojumi**.
3. Klientu sadaļā pārbaudi, ka iepriekšējās atzīmes saglabātas un ievadē rāda tikai aktīvos klientus. Kontrolēti noņem vienam klientam atzīmi un pēc pārbaudes atjauno to.
4. Pakalpojumu sadaļā jārādās visiem esošajiem `mnu_dati` ierakstiem. Pārbaudi meklēšanu.
5. CRUD pārbaudei izveido skaidri nosauktu pagaidu pakalpojumu, piemēram, `TESTS 1.3.1`, ar valodu LV, apakšiedaļu `TESTS 1.3.1` un mērvienību `gab.`. Pieraksti automātiski piešķirto Nr., izlabo pagaidu ieraksta mērvienību, pārbaudi saglabāšanu un izdzēs tikai šo paša izveidoto testa ierakstu, apstiprinot tā Nr. Īstos ierakstus testēšanai nedzēs.
6. Darba ievades formā pārbaudi pakalpojuma veida, apakšiedaļas un mērvienības izvēli. Darbs nav jāsaglabā.
7. Parastajam lietotājam administratora izvēlnes nav redzamas; tieša `?view=clients` un `?view=services` atvēršana nedrīkst dot pārvaldības piekļuvi.

## 5. Ja rodas kļūda

- 500: pārbaudi PHP kļūdu žurnālu; neieslēdz publisku paroļu vai pilnu kļūdu izdruku.
- Kļūda par nezināmu `act`: pārbaudi, vai migrācija izpildīta tajā pašā datubāzē, kuru izmanto servera `config.php`.
- Kļūda par neesošu `services.php`: pārbaudi failu nosaukumus un apakšmapes.
- Dzēšanas tiesību kļūda: pārbaudi lietotnes DB konta DELETE tiesības uz `mnu_dati`.
- Vajadzības gadījumā atjauno mainītos failus no savas rezerves kopijas. Ja `act` ir tikko pievienots, vecā lietotne šo papildu lauku neizmanto; tā dzēšana nav nepieciešama. Datubāzes kopiju atjauno tikai apzināti, izvērtējot pēc kopijas izveidošanas ievadītos datus.

Raspberry Pi kods un versijas attēlojums ir pārbaudīts. Šīs instrukcijas sagatavošana pati par sevi nemaina kompetence.lv serveri.
