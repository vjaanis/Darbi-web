<?php
declare(strict_types=1);

namespace Darbi;

function recentJobs(int $employeeId): array
{
    return fetchRows(
        "SELECT nr, sak_dat, klients, darbnosauk, summa FROM darbi WHERE darbinieks_id = ? AND eksportets = 0 ORDER BY nr DESC LIMIT 12", [$employeeId],
    );
}

/** Atbilst VFP objektam "viens"; visas vērtības sagatavo pirms INSERT. */
function prepareJob(array $input, int $employeeId, ?array $existing = null): array
{
    $clientId = readInteger($input, "klient_id", 1, 2147483647, "Klients");
    $client = findClient($clientId);

    $job = [
        "klient_id" => $clientId,
        "klients" => $client["name"],
        "lng" => $client["lng"],
        "darb_veids" => readText($input, "darb_veids", 100, "Pakalpojuma veids"),
        "darb_tip1" => readText($input, "darb_tip1", 220, "Apakšiedaļa"),
        "mervien" => readText($input, "mervien", 50, "Mērvienība"),
        "darbnosauk" => readText($input, "darbnosauk", 508, "Darba apraksts"),
        "parstv" => readText($input, "parstv", 30, "Pārstāvis"),
        "diviz" => readText($input, "diviz", 30, "Divīzija"),
        "koment" => readText($input, "koment", 250, "Komentārs"),
    ];

    if ($job["darbnosauk"] === "") {
        throw new \InvalidArgumentException("Ievadi darba aprakstu.");
    }

    $sameSelection = $existing !== null;
    foreach (['klient_id', 'lng', 'darb_veids', 'darb_tip1', 'mervien'] as $field) {
        $sameSelection = $sameSelection && (string) $job[$field] === (string) ($existing[$field] ?? '');
    }
    if (!$sameSelection && !catalogSelectionExists($job["lng"], $job["darb_veids"], $job["darb_tip1"], $job["mervien"])) {
        throw new \InvalidArgumentException(
            "Izvēlies klienta valodai atbilstošu pakalpojumu, apakšiedaļu un mērvienību.",
        );
    }

    $job["darbn"] = employeeName($employeeId);
    $job['darbinieks_id'] = $employeeId;

    // darb_id biznesa nozīme vēl nav apstiprināta; to nepārdefinējam.
    $amount = calculateAmount($input);

    if ($amount["daudz"] === "0.00") {
        throw new \InvalidArgumentException("Daudzumam jābūt lielākam par nulli.");
    }

    foreach (["daudz", "cena", "atlaide", "summa"] as $column) {
        $job[$column] = $amount[$column];
    }

    $job["sak_dat"] = readDate($input, "sak_dat", "Sākuma datums");
    $job["termins"] = readDate($input, "termins", "Termiņš");

    if ($job["sak_dat"] === null) {
        throw new \InvalidArgumentException("Norādi sākuma datumu.");
    }

    $isCompleted = readBoolean($input, "gat");
    $job["gat"] = $isCompleted ? 1 : 0;
    $job["g_dat"] = $isCompleted ? readDate($input, "g_dat", "Pabeigšanas datums") : null;

    if ($isCompleted && $job["g_dat"] === null) {
        throw new \InvalidArgumentException("Norādi pabeigšanas datumu.");
    }

    return $job;
}

function createJob(array $input, int $employeeId): array
{
    $job = prepareJob($input, $employeeId);

    // Viens INSERT ir atomārs: nevar palikt VFP APPEND BLANK tipa tukšs ieraksts.
    $number = insertRecord("darbi", $job);

    return ["ok" => true, "nr" => $number, "summa" => $job["summa"]];
}

function jobVersion(array $job): string
{
    return hash_hmac('sha256', json_encode($job, JSON_THROW_ON_ERROR), csrfToken());
}

function findJob(int $number, int $employeeId, bool $lock = false): array
{
    $rows = fetchRows('SELECT * FROM darbi WHERE nr = ? AND darbinieks_id = ? AND eksportets = 0' . ($lock ? ' FOR UPDATE' : ''), [$number, $employeeId]);
    if (!$rows) {
        sendJson(['error' => 'Darbs vairs nav atrodams.'], 404);
    }
    return $rows[0];
}

function jobDetails(int $number, int $employeeId): array
{
    $job = findJob($number, $employeeId);
    return ['job' => $job, 'version' => jobVersion($job), 'client' => findClient((int) $job['klient_id'])];
}

function listJobs(array $input, int $employeeId): array
{
    $search = readText($input, 'q', 100, 'Meklēšana');
    $page = readInteger($input + ['page' => 1], 'page', 1, 1000000, 'Lapa');
    $where = ' WHERE darbinieks_id = ? AND eksportets = 0';
    $parameters = [$employeeId];
    if ($search !== '') {
        $where .= ' AND (CAST(nr AS CHAR) = ? OR klients LIKE ? OR darbnosauk LIKE ? OR darbn LIKE ?)';
        $parameters = [$employeeId, $search, '%' . $search . '%', '%' . $search . '%', '%' . $search . '%'];
    }
    $total = (int) fetchRows('SELECT COUNT(*) AS total FROM darbi' . $where, $parameters)[0]['total'];
    $pages = max(1, (int) ceil($total / 25));
    $page = min($page, $pages);
    $offset = ($page - 1) * 25;
    $rows = fetchRows('SELECT nr, sak_dat, termins, klients, darbnosauk, darbn, summa, gat FROM darbi'
        . $where . ' ORDER BY nr DESC LIMIT 25 OFFSET ' . $offset, $parameters);
    return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages];
}

/** Slēdz ierakstu un nepieļauj cita lietotāja izmaiņu pārrakstīšanu. */
function changeJob(array $input, array $user, bool $delete = false): array
{
    $number = readInteger($input, 'nr', 1, 2147483647, 'Darba numurs');
    $version = readText($input, 'version', 64, 'Ieraksta versija');
    $db = database();
    $db->beginTransaction();
    try {
        $existing = findJob($number, $user['darbinieks_id'], true);
        if (!hash_equals(jobVersion($existing), $version)) {
            $db->rollBack();
            sendJson(['error' => 'Darbs pa šo laiku mainīts. Atver to no pārskata vēlreiz.'], 409);
        }
        if ($delete) {
            $statement = $db->prepare('DELETE FROM darbi WHERE nr = ?');
            $statement->execute([$number]);
            $result = ['ok' => true, 'nr' => $number];
        } else {
            $job = prepareJob($input, $user['darbinieks_id'], $existing);
            // Labotājs nekļūst par darba sākotnējo darbinieku.
            unset($job['darbn']);
            unset($job['darbinieks_id']);
            $columns = array_map(static fn(string $column): string => '`' . $column . '` = ?', array_keys($job));
            $statement = $db->prepare('UPDATE darbi SET ' . implode(', ', $columns) . ' WHERE nr = ?');
            $statement->execute([...array_values($job), $number]);
            $result = ['ok' => true, 'nr' => $number, 'summa' => $job['summa']];
        }
        $db->commit();
        return $result;
    } catch (\Throwable $error) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $error;
    }
}
