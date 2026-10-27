<?php
declare(strict_types=1);

namespace Darbi;

function searchClients(string $search): array
{
    return fetchRows("SELECT * FROM client WHERE name LIKE ? ORDER BY name, id", [
        "%" . $search . "%",
    ]);
}

function findClient(int $id): array
{
    $rows = fetchRows("SELECT * FROM client WHERE id = ?", [$id]);

    if (!$rows) {
        throw new \InvalidArgumentException("Izvēlies klientu no saraksta.");
    }

    return $rows[0];
}

function clientHistory(int $clientId, int $employeeId): array
{
    $client = findClient($clientId);
    $history = [];

    // VFP Combo1.Valid: vēsturi atlasa pēc klients.NAME, neatkarīgi no klient_id.
    foreach (["parstv", "diviz"] as $column) {
        $sql = "SELECT DISTINCT $column COLLATE utf8mb4_bin AS $column FROM darbi
                WHERE klients COLLATE utf8mb4_bin = ? AND darbinieks_id = ?
                  AND TRIM($column) <> ''
                ORDER BY $column";

        $rows = fetchRows($sql, [$client["name"], $employeeId]);
        $history[$column] = array_column($rows, $column);
    }

    return $history;
}

