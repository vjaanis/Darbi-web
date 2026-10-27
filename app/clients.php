<?php
declare(strict_types=1);

namespace Darbi;

function searchClients(string $search): array
{
    return fetchRows("SELECT * FROM client WHERE act = 1 AND name LIKE ? ORDER BY name, id", [
        "%" . $search . "%",
    ]);
}

function requireClientsAdmin(array $user): void
{
    if (empty($user['ir_admin'])) {
        sendJson(['error' => 'Klientu pārvaldība pieejama tikai administratoram.'], 403);
    }
}

function listAdminClients(array $user): array
{
    requireClientsAdmin($user);
    return fetchRows('SELECT id, name, c_code, lng, act FROM client ORDER BY name, id');
}

function setClientActive(array $input, array $user): array
{
    requireClientsAdmin($user);
    $id = readInteger($input, 'id', 1, 2147483647, 'Klients');
    if (!array_key_exists('act', $input)) {
        throw new \InvalidArgumentException('Norādi klienta aktīvo stāvokli.');
    }
    $active = readInteger($input, 'act', 0, 1, 'Aktīvs');
    findClient($id);
    $statement = database()->prepare('UPDATE client SET act = ? WHERE id = ?');
    $statement->execute([$active, $id]);
    return ['ok' => true, 'id' => $id, 'act' => $active];
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

