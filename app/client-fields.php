<?php
declare(strict_types=1);

namespace Darbi;

/** VFP klientu importa lauku nosaukumi un Web tabulas ietilpības. */
function clientFieldDefinitions(): array
{
    return [
        "name" => ["label" => "Klienta nosaukums", "length" => 60, "required" => true],
        "c_code" => ["label" => "Reģistrācijas kods", "length" => 20],
        "vat" => ["label" => "PVN numurs", "length" => 20],
        "lng" => ["label" => "Valoda", "length" => 3, "default" => "LV", "required" => true],
        "address" => ["label" => "Adrese", "length" => 90],
        "lig_nr" => ["label" => "Līguma numurs", "length" => 254],
        "bank" => ["label" => "Banka", "length" => 20],
        "swift" => ["label" => "SWIFT", "length" => 10],
        "b_acc" => ["label" => "Bankas konts", "length" => 30],
        "maks_laiks" => [
            "label" => "Apmaksas termiņš, dienas",
            "type" => "number",
            "minimum" => 0,
            "maximum" => 3650,
            "default" => "0",
        ],
        "email" => ["label" => "E-pasts", "length" => 31, "type" => "email"],
        "mob" => ["label" => "Mobilais tālrunis", "length" => 20],
        "pfone" => ["label" => "Tālrunis", "length" => 20],
        "nickname" => ["label" => "Iesauka", "length" => 20],
        "person_1" => ["label" => "Kontaktpersona 1", "length" => 20],
        "person_2" => ["label" => "Kontaktpersona 2", "length" => 20],
        "person_3" => ["label" => "Kontaktpersona 3", "length" => 20],
        "person_4" => ["label" => "Kontaktpersona 4", "length" => 20],
    ];
}
