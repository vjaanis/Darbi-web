# Darba-web → VFP CSV, versija 2 (22.10.2026)

CSV satur tikai datu ierakstus, bez kolonnu nosaukumu galvenes.
Katrā ierakstā ir 24 lauki VFP darbi.dbf fiziskajā lauku secībā.
Pirmais NR lauks ir tukšs: ieraksts sākas ar semikolu. Tīmekļa numurs netiek eksportēts.
VFP imports piešķir savu numuru. Tālāk minētie nosaukumi paskaidro secību;
pašā failā šīs nosaukumu rindas nav:

```text
NR(tukšs);SAK_DAT;DARB_VEIDS;DARB_TIP1;DARBNOSAUK;G_DAT;CENA;MERVIEN;KLIENTS;KLIENT_ID;DARBN;DARB_ID;GAT;REKINS;REK_DAT;ACTIV;DAUDZ;SUMMA;ATLAIDE;LNG;PARSTV;DIVIZ;TERMINS;KOMENT
```

- Kodējums: UTF-8 ar BOM. Atdalītājs: semikols. Rindu beigas: CRLF.
- Teksta citēšana: dubultpēdiņas; iekšējās pēdiņas dubultotas. Atļauti teksta rindu pārnesumi.
- Datumi: DD.MM.GGGG. Nezināmi datumi: tukšs lauks.
- Decimālskaitļiem punkts; CENA/SUMMA četras decimāldaļas, DAUDZ divas.
- GAT/ACTIV: 0 vai 1. Nullable skaitļi: tukša vērtība.
- DARBNOSAUK CSV izvadē tiek saīsināts līdz pirmajām 254 Unicode rakstzīmēm.
  Tā atbilst C(254) ietilpībai vienbaita Baltijas kodējumā, ja burti ir šajā kodējumā attēlojami.
  UTF-8 simboli netiek pārgriezti baitu vidū. Pilnais nosaukums datubāzē paliek saglabāts.
- Pārējie teksta lauki netiek saīsināti. VFP imports pārbauda to ietilpību un kodējumu.
- CSV nav darbinieks_id, lielais, eksporta numura vai eksporta datuma kolonnu.
- Faila nosaukums: darbi_DD.MM.GGGG_eksports_000001.csv.

Jaunais formāts attiecas uz jauniem eksportiem. Jau sagatavotie vēstures faili paliek
precīzi tādi, kādi tika izveidoti. Vecais formāts saturēja 23 kolonnu galveni un 23 datu laukus bez NR.
Atjauninātais import_darbi.prg automātiski atpazīst gan veco, gan jauno formātu;
jaunajā formātā pieprasa tukšu pirmo NR lauku. Formātus vienā failā nejauc.

Eksporta atzīmes, transakcija un failu vēsture darbojas kā iepriekš.
Eksportētie darbi vairs nav darbu sarakstos un nav labojami.
Faila sagatavošana neapstiprina tā importēšanu VFP sistēmā.
VFP importu un tā lietošanu sk. importa pakotnes README.lv.md.
