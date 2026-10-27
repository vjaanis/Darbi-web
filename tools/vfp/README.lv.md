# Darba-web CSV imports Visual FoxPro 9 — formāts v2

Imports pievieno Darba-web eksportētos darbus VFP tabulai `darbi.dbf`.
Esošos darbus nepārraksta. CSV tīmekļa numuru nav; VFP piešķir savus `NR`.

## Palaišana

1. Nokopē abus PRG failus vienā mapē VFP datorā.
2. Lejupielādē eksportu no Darba-web. Saglabā sākotnējo nosaukumu,
   piemēram, `darbi_22.10.2026_eksports_000001.csv`. Pieļaujams arī pārlūka pielikums ` (1)`.
3. VFP komandu logā izpildi:

   ```foxpro
   DO "C:\Importa-riks\palaist_importu.prg"
   ```

4. Izvēlies CSV un **īsto VFP darbu tabulu** `darbi.dbf`.
5. Rīks vispirms pārbauda failu. Ja pārbaude sekmīga, tas parāda darbu skaitu,
   mērķa failu un piedāvā veikt importu. Tikai pēc apstiprināšanas ieraksta datus.

Faktiskajam importam vajadzīga ekskluzīva piekļuve `darbi.dbf` un žurnālam.
Pirms importa aizver šo tabulu citos VFP logos, datu sesijās un datoros.
Pārbaudes režīmam pietiek ar lasīšanas piekļuvi; tas neko neieraksta.
Lietotājam vajadzīgas rakstīšanas tiesības tabulas mapē, lai izveidotu žurnālu un rezerves kopiju.

Nepieciešams **Visual FoxPro 9 SP2**. Pārbaudīts instalētajā VFP
`09.00.0000.5815`, ar aktīvo kodu lapu 1257 (Baltijas Windows).
Ja VFP kodu lapa neatbalsta CSV burtus, imports apstājas ar kļūdu.
Ja nepieciešams, VFP startēšanas konfigurācijai `config.fpw` pievieno `CODEPAGE=1257`
un pārstartē VFP. Esošo konfigurāciju pilnībā neaizstāj.
Tabulas kodu lapu imports nolasa ar `CPDBF()` un pārbauda arī konvertēšanu uz to.

## Programmiska izsaukšana

```foxpro
SET PATH TO "C:\Importa-riks" ADDITIVE

* Tikai pārbaudīt; nav ierakstu, žurnāla vai rezerves kopijas izveides.
loRez = import_darbi("C:\Eksporti\darbi_22.10.2026_eksports_000001.csv", ;
                    "C:\VFP-dati\darbi.dbf", .T.)
? loRez.Ok, loRez.Count, loRez.Message

* Importēt. Pirms rakstīšanas pārbaude tiek atkārtota zem ekskluzīva slēguma.
loRez = import_darbi("C:\Eksporti\darbi_22.10.2026_eksports_000001.csv", ;
                    "C:\VFP-dati\darbi.dbf", .F.)
? loRez.Ok, loRez.FirstNr, loRez.LastNr, loRez.BackupPath, loRez.Message
```

Ja trešo parametru izlaiž, tiek veikta tikai pārbaude.
Rezultāts ir objekts ar `Ok`, `Preview`, `Count`, `FirstNr`, `LastNr`,
`ExportNr`, `BackupPath`, `Message`. Kļūdas gadījumā `Ok = .F.`.
Jaunajā CSV kļūdā norādītais ieraksta numurs sākas ar pirmo darbu kā 1. ierakstu.
Vecajā formātā tas skaita galveni kā 1. ierakstu;
daudzrindu teksta dēļ tas var nesakrist ar fizisko teksta rindas numuru.

## Datu apstrāde

Jaunais CSV: UTF-8 ar vai bez BOM, semikols, dubultpēdiņu citēšana, 24 lauki bez galvenes.
Pirmais lauks (NR vieta) ir tukšs. Imports to izlaiž un piešķir VFP numuru.
Vecais 23 kolonnu formāts ar galveni arī tiek automātiski atpazīts.
Datumi tikai `DD.MM.GGGG`; tukšs datums kļūst par VFP tukšo datumu.
Loģiskie lauki `0`/`1` kļūst par `.F.`/`.T.`.
Skaitļiem vajadzīgs decimālpunkts. Tukšs skaitlis kļūst par NULL, ja lauks to pieļauj,
citādi par 0. `CENA` un `SUMMA` saglabā Currency precizitāti līdz četrām decimāldaļām;
konvertēšana nenotiek caur peldošā komata naudas summu.

Jaunā formāta lauku secība (nosaukumu rinda failā netiek rakstīta; NR ir tukšs):

```text
NR(tukšs);SAK_DAT;DARB_VEIDS;DARB_TIP1;DARBNOSAUK;G_DAT;CENA;MERVIEN;KLIENTS;KLIENT_ID;DARBN;DARB_ID;GAT;REKINS;REK_DAT;ACTIV;DAUDZ;SUMMA;ATLAIDE;LNG;PARSTV;DIVIZ;TERMINS;KOMENT
```

Ja `NR` ir AUTOINC, tiek izmantota VFP tabulas secība. Ja tas ir parasts Integer,
jaunie numuri ir `MAX(NR)+1` un tālāk pēc kārtas zem ekskluzīva slēguma;
maksimumā ņem vērā arī dzēšanai atzīmētos ierakstus.
Ja esošā programma numurus piešķir ar savu atsevišķu skaitītāju,
šī vieta pirms pieslēgšanas tās izvēlnei jāpielāgo šīs programmas numurēšanai.
Sākotnējās VFP programmas PRG un tās skaitītāju loģika šim uzdevumam nebija pieejama.

Garš teksts, ietilpībā neiederīgs skaitlis, nederīgs datums vai nepārveidojams burts
noraida **visu CSV**. Lauki netiek apgriezti un skaitļi netiek klusi noapaļoti.
Jaunais PHP eksports jau saīsina DARBNOSAUK līdz 254 rakstzīmēm, datubāzē atstājot pilno tekstu.
Pārbaude joprojām noraida garāku aprakstu vecā formāta failos,
KLIENTS ar vairāk nekā 50 baitiem un DAUDZ ārpus N(6,2) ietilpības.
Mērķa teksta lauki drīkst būt C, V vai M; pārējiem jāsaglabā paredzētie D/L/I/Y/N tipi.
Vienā importā pieņem 1–200 darbus un failu līdz 5 MB.

## Žurnāls un rezerves kopijas

Mērķa tabulas mapē tiek izveidoti `web_importi.dbf` un `web_importi.fpt`:
eksporta numurs, sākotnējais faila nosaukums, importa laiks, darbu skaits,
pirmais/pēdējais VFP numurs un precīzs sākotnējā CSV baitu saturs.
Vienā datu mapē paredzēta viena darbu mērķa tabula.

Atkārtoti tiek noraidīts gan jau importēts eksporta numurs, gan identisks CSV saturs
ar citu nosaukumu. Tas attiecas arī uz dzēšanai atzīmētām žurnāla rindām.
Žurnālu saglabā un kopē kopā ar darbu datiem; tā izdzēšana atņem atkārtojumu aizsardzību.
Eksporta numuri pieņemti no viena Darba-web eksporta avota.

Pirms importa izveido `web_backup_<laiks>_<unikāls ID>` mapi ar abu tabulu
konsekventām VFP DBF/FPT/CDX kopijām, saglabājot arī dzēšanas atzīmes.
Šīs ir tabulu datu kopijas, nevis visas DBC datubāzes konfigurācijas rezerves kopija.

Darbu pievienošana un importa žurnāls ir vienā transakcijā. Ja rakstīšana,
indeksa pārbaude vai žurnāla ieraksts neizdodas, visa partija tiek atcelta.
Arī brīvām tabulām transakcijas ieslēdz ar VFP 9 `MAKETRANSACTABLE()`.
Pieņemot kļūdainu importu, var palikt izveidots tukšs žurnāls vai rezerves kopijas mape.
Datubāzes AUTOINC vērtībām pēc neveiksmīga mēģinājuma iespējami numuru iztrūkumi.

Imports darbojas savā privātā datu sesijā. Tas neizmanto tīklu,
neizmaina Darba-web eksporta atzīmes un nesazinās ar MariaDB.
VFP izvēlnē automātiska pieslēgšana nav veikta, jo izvēlnes pirmkods nav pieejams.

## Atsauces DBF īpatnība

Faila `Kompt-Darbi\darbi.dbf` neapstrādātajā galvenē ir 24 lauki,
bet VFP 9 to atver tikai ar 15 laukiem: trūkst VFP galvenes 263 baitu backlink bloka.
Oriģinālais fails netika labots vai papildināts. Testiem izmantota **tukša kopija**
ar korektu VFP galveni un visiem 24 laukiem. Šo atsauces failu nedrīkst sajaukt
ar īsto VFP sistēmas mērķa tabulu. Imports kļūdainu/nepilnīgu struktūru noraidīs.

## Pārbaudes un tehniskās atsauces

Rīks kompilēts un izpildīts īstā VFP 9 SP2. Pārbaudīti pareizi un kļūdaini CSV,
kodējums, lauku robežas, atkārtots imports, abas NR piešķiršanas iespējas,
unikālā indeksa kļūda partijas vidū, žurnāla kļūda un aizņemta mērķa tabula.
Papildus ar neatkarīgu DBF baitu pārbaudi salīdzināts Unicode, datumi,
loģiskās vērtības un precīzas pozitīvās/negatīvās Currency robežvērtības.
Īstajā VFP tabulā nekas nav importēts. Dialogu palaidējs ir kompilēts;
ar lietotāja īsto datu failu tas nav izpildīts.

Izmantota Microsoft VFP 9 dokumentācija VFPX izdevumā:
[UTF-8 STRCONV](https://www.vfphelp.com/help/_5wn12psg4.htm),
[brīvo tabulu transakcijas](https://www.vfphelp.com/vfp9/html/b3b5b00e-5251-4240-b638-49135210915e.htm),
[AFIELDS struktūra un AUTOINC](https://www.vfphelp.com/vfp9/html/c576b477-4cf5-4ad4-a115-6963867dd548.htm),
[CTOBIN Currency](https://vfphelp.com/vfp9/html/b1758fd8-354c-452d-a5f9-ca81292eb71e.htm).

22.10.2026 pārbaudīts arī jaunais PHP CSV bez galvenes: 24 lauki, tukšs NR,
254 Baltijas rakstzīmju apraksts, VFP NR piešķiršana un atkārtota importa aizliegums.
