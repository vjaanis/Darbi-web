<?php
declare(strict_types=1);

namespace Darbi;

function serviceTypes(string $language): array
{
    $rows = fetchRows(
        "SELECT DISTINCT tips FROM mnu_dati WHERE lng = ? AND tips <> '' ORDER BY tips",
        [$language],
    );

    return array_column($rows, "tips");
}

function serviceSections(string $language, string $type): array
{
    $rows = fetchRows(
        "SELECT DISTINCT sdala FROM mnu_dati
         WHERE lng = ? AND tips = ? AND sdala <> '' ORDER BY sdala",
        [$language, $type],
    );

    return array_column($rows, "sdala");
}

function serviceUnits(string $section): array
{
    // Combo4.Valid aizpilda combo5 pēc apakšiedaļas pirmajām 100 zīmēm.
    // Katalogā mērvienības mēdz būt atsevišķās rindās bez tips/lng.
    $rows = fetchRows(
        "SELECT DISTINCT mervien COLLATE utf8mb4_bin AS mervien FROM mnu_dati
         WHERE TRIM(mervien) <> ''
           AND LEFT(sdala, 100) COLLATE utf8mb4_bin = LEFT(CONVERT(? USING utf8mb4), 100) COLLATE utf8mb4_bin
         ORDER BY mervien",
        [$section],
    );

    $units = array_column($rows, "mervien");

    return $units ?: ["--"];
}

function catalogSelectionExists(string $language, string $type, string $section, string $unit): bool
{
    return in_array($type, serviceTypes($language), true)
        && in_array($section, serviceSections($language, $type), true)
        && in_array($unit, serviceUnits($section), true);
}

function employees(): array
{
    return fetchRows("SELECT id, vards, uzvards FROM darbinieki ORDER BY vards, uzvards");
}

function employeeName(int $id): string
{
    $rows = fetchRows("SELECT vards FROM darbinieki WHERE id = ?", [$id]);

    if (!$rows) {
        throw new \InvalidArgumentException("Izvēlies darbinieku no saraksta.");
    }

    return $rows[0]["vards"];
}
