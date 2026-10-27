<?php
declare(strict_types=1);

namespace Darbi;

final class ExportConflict extends \RuntimeException {}

function requireExportAdmin(array $user): void
{
    if (empty($user['ir_admin'])) {
        sendJson(['error' => 'Datu eksports pieejams tikai administratoram.'], 403);
    }
}

/** Iekļauj arī neaktīvu kontu autorus un nesasaistītos vēsturiskos darbus. */
function exportAuthors(): array
{
    return fetchRows("SELECT DISTINCT d.id, TRIM(CONCAT(d.vards, ' ', d.uzvards)) AS name
        FROM darbi j JOIN darbinieki d ON d.id = j.darbinieks_id ORDER BY name, d.id");
}

function listExportJobs(array $input): array
{
    $author = readInteger($input, 'author', -1, 2147483647, 'Autors', 0);
    $search = readText($input, 'q', 100, 'Meklēšana');
    $page = readInteger($input, 'page', 1, 1000000, 'Lapa', 1);
    $where = ['j.eksportets = 0'];
    $args = [];
    if ($author === -1) $where[] = 'j.darbinieks_id IS NULL';
    if ($author > 0) { $where[] = 'j.darbinieks_id = ?'; $args[] = $author; }
    if ($search !== '') {
        $where[] = '(CAST(j.nr AS CHAR) = ? OR j.klients LIKE ? OR j.darbnosauk LIKE ?)';
        array_push($args, $search, '%' . $search . '%', '%' . $search . '%');
    }
    $sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $total = (int) fetchRows('SELECT COUNT(*) AS total FROM darbi j' . $sqlWhere, $args)[0]['total'];
    $pages = max(1, (int) ceil($total / 25));
    $page = min($page, $pages);
    $rows = fetchRows('SELECT j.*, TRIM(CONCAT(d.vards, \' \', d.uzvards)) AS autora_vards
        FROM darbi j LEFT JOIN darbinieki d ON d.id = j.darbinieks_id' . $sqlWhere
        . ' ORDER BY j.nr DESC LIMIT 25 OFFSET ' . (($page - 1) * 25), $args);
    $result = [];
    foreach ($rows as $job) {
        $authorName = $job['autora_vards'];
        unset($job['autora_vards']);
        $result[] = array_intersect_key($job, array_flip([
            'nr', 'sak_dat', 'klients', 'darbnosauk', 'darbn', 'darbinieks_id', 'summa',
            'gat', 'eksportets', 'eksporta_nr', 'eksporta_datums',
        ])) + ['author' => $authorName ?: $job['darbn'], 'version' => jobVersion($job)];
    }
    return ['rows' => $result, 'total' => $total, 'page' => $page, 'pages' => $pages];
}

function exportCsv(array $jobs): string
{
    // VFP lauku secība bez galvenes; NR vietā tukšs lauks. VFP piešķir savu numuru.
    $fields = ['sak_dat', 'darb_veids', 'darb_tip1', 'darbnosauk', 'g_dat',
        'cena', 'mervien', 'klients', 'klient_id', 'darbn', 'darb_id', 'gat', 'rekins',
        'rek_dat', 'activ', 'daudz', 'summa', 'atlaide', 'lng', 'parstv', 'diviz',
        'termins', 'koment'];
    $stream = fopen('php://temp', 'w+b');
    if ($stream === false) throw new \RuntimeException('CSV plūsmu nevar izveidot.');
    try {
        fwrite($stream, "\xEF\xBB\xBF");
        foreach ($jobs as $job) {
            $values = array_map(static function (string $field) use ($job): string {
                $value = (string) ($job[$field] ?? '');
                if ($field === 'darbnosauk') {
                    // DBF DARBNOSAUK ir C(254). Saglabā pilnus UTF-8 simbolus, arī rindu pārnesumus.
                    if (preg_match('/^.{0,254}/us', $value, $match) !== 1) {
                        throw new \RuntimeException('Darba nosaukumam nederīgs UTF-8 teksts.');
                    }
                    return $match[0];
                }
                if (in_array($field, ['sak_dat', 'g_dat', 'rek_dat', 'termins'], true) && $value !== '') {
                    if ($value === '0000-00-00') return '';
                    return substr($value, 8, 2) . '.' . substr($value, 5, 2) . '.' . substr($value, 0, 4);
                }
                return $value;
            }, $fields);
            array_unshift($values, '');
            if (fputcsv($stream, $values, ';', '"', '', "\r\n") === false) throw new \RuntimeException('CSV kļūda.');
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        if ($csv === false) throw new \RuntimeException('CSV nevar nolasīt.');
        return $csv;
    } finally {
        fclose($stream);
    }
}

function createExport(array $input, array $user): array
{
    requireExportAdmin($user);
    $requestId = readText($input, 'request_id', 32, 'Pieprasījuma numurs');
    if (!preg_match('/^[a-f0-9]{32}$/D', $requestId)) throw new \InvalidArgumentException('Nederīgs pieprasījuma numurs.');
    $selection = $input['jobs'] ?? null;
    if (!is_array($selection) || !array_is_list($selection) || count($selection) < 1 || count($selection) > 200) {
        throw new \InvalidArgumentException('Atzīmē no 1 līdz 200 darbiem.');
    }
    $versions = [];
    foreach ($selection as $item) {
        if (!is_array($item)) throw new \InvalidArgumentException('Nederīga darbu atlase.');
        $nr = readInteger($item, 'nr', 1, 2147483647, 'Darba numurs');
        $version = readText($item, 'version', 64, 'Ieraksta versija');
        if (!preg_match('/^[a-f0-9]{64}$/D', $version) || isset($versions[$nr])) {
            throw new \InvalidArgumentException('Nederīga vai atkārtota darba atlase.');
        }
        $versions[$nr] = $version;
    }
    ksort($versions, SORT_NUMERIC);
    $selectionHash = hash('sha256', json_encode($versions, JSON_THROW_ON_ERROR));
    $db = database();
    $db->beginTransaction();
    try {
        // Unikāls pieprasījums novērš atkārtotu eksportu pēc tīkla kļūdas/dubultklikšķa.
        $prior = fetchRows('SELECT nr, faila_nosaukums, darbinieks_id, atlases_hash FROM eksporti WHERE pieprasijums = ?', [$requestId]);
        if ($prior) {
            if ((int) $prior[0]['darbinieks_id'] !== $user['darbinieks_id'] || !hash_equals($prior[0]['atlases_hash'], $selectionHash)) {
                throw new ExportConflict('Šis pieprasījuma numurs jau izmantots citai atlasei.');
            }
            $db->commit();
            return ['ok' => true, 'nr' => (int) $prior[0]['nr'], 'filename' => $prior[0]['faila_nosaukums']];
        }
        $date = (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Riga')))->format('Y-m-d H:i:s');
        $statement = $db->prepare('INSERT INTO eksporti (izveidots, darbinieks_id, izpilditajs, ierakstu_skaits, faila_nosaukums, csv_saturs, sha256, darbu_numuri, pieprasijums, atlases_hash)
            VALUES (?, ?, ?, 0, \'\', \'\', \'\', \'[]\', ?, ?)');
        $statement->execute([$date, $user['darbinieks_id'], trim($user['vards'] . ' ' . $user['uzvards']), $requestId, $selectionHash]);
        $number = (int) $db->lastInsertId();
        $ids = array_keys($versions);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $jobs = fetchRows('SELECT * FROM darbi WHERE nr IN (' . $placeholders . ') ORDER BY nr FOR UPDATE', $ids);
        if (count($jobs) !== count($ids)) throw new ExportConflict('Daļa darbu vairs nav atrodama. Atjauno sarakstu.');
        foreach ($jobs as $job) {
            if ((int) $job['eksportets'] === 1) throw new ExportConflict('Darbs #' . $job['nr'] . ' jau eksportēts. Failu meklē vēsturē.');
            if (!hash_equals(jobVersion($job), $versions[(int) $job['nr']])) {
                throw new ExportConflict('Darbs #' . $job['nr'] . ' pa šo laiku mainīts. Atjauno sarakstu un atlasi.');
            }
        }
        $csv = exportCsv($jobs);
        $fileDate = substr($date, 8, 2) . '.' . substr($date, 5, 2) . '.' . substr($date, 0, 4);
        $filename = 'darbi_' . $fileDate . '_eksports_' . str_pad((string) $number, 6, '0', STR_PAD_LEFT) . '.csv';
        $statement = $db->prepare('UPDATE eksporti SET ierakstu_skaits = ?, faila_nosaukums = ?, csv_saturs = ?, sha256 = ?, darbu_numuri = ? WHERE nr = ?');
        $statement->execute([count($jobs), $filename, $csv, hash('sha256', $csv), json_encode($ids, JSON_THROW_ON_ERROR), $number]);
        $statement = $db->prepare('UPDATE darbi SET eksportets = 1, eksporta_nr = ?, eksporta_datums = ? WHERE nr IN (' . $placeholders . ')');
        $statement->execute([$number, $date, ...$ids]);
        $db->commit();
        return ['ok' => true, 'nr' => $number, 'filename' => $filename];
    } catch (\Throwable $error) {
        if ($db->inTransaction()) $db->rollBack();
        // Vienlaicīgs viena un tā paša pieprasījuma atkārtojums.
        if ($error instanceof \PDOException && ($error->errorInfo[1] ?? 0) === 1062) {
            $prior = fetchRows('SELECT nr, faila_nosaukums, darbinieks_id, atlases_hash FROM eksporti WHERE pieprasijums = ?', [$requestId]);
            if ($prior && (int) $prior[0]['darbinieks_id'] === $user['darbinieks_id'] && hash_equals($prior[0]['atlases_hash'], $selectionHash)) {
                return ['ok' => true, 'nr' => (int) $prior[0]['nr'], 'filename' => $prior[0]['faila_nosaukums']];
            }
        }
        throw $error;
    }
}

function listExports(array $input): array
{
    $page = readInteger($input, 'page', 1, 1000000, 'Lapa', 1);
    $search = readText($input, 'q', 100, 'Eksporta meklēšana');
    if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/D', $search, $parts)) {
        $search = $parts[3] . '-' . $parts[2] . '-' . $parts[1];
    }
    $where = '';
    $args = [];
    if ($search !== '') {
        $where = ' WHERE CAST(nr AS CHAR) = ? OR faila_nosaukums LIKE ? OR CAST(izveidots AS CHAR) LIKE ? OR izpilditajs LIKE ?';
        $args = [$search, '%' . $search . '%', '%' . $search . '%', '%' . $search . '%'];
    }
    $total = (int) fetchRows('SELECT COUNT(*) AS total FROM eksporti' . $where, $args)[0]['total'];
    $pages = max(1, (int) ceil($total / 20));
    $page = min($page, $pages);
    $rows = fetchRows('SELECT nr, izveidots, izpilditajs, ierakstu_skaits, faila_nosaukums, darbu_numuri, sha256 FROM eksporti'
        . $where . ' ORDER BY nr DESC LIMIT 20 OFFSET ' . (($page - 1) * 20), $args);
    return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages];
}

function downloadExport(int $number): never
{
    $rows = fetchRows('SELECT faila_nosaukums, csv_saturs, sha256 FROM eksporti WHERE nr = ?', [$number]);
    if (!$rows) sendJson(['error' => 'Eksports nav atrasts.'], 404);
    $row = $rows[0];
    if (!hash_equals($row['sha256'], hash('sha256', $row['csv_saturs']))) throw new \RuntimeException('Eksporta kontrolsumma neatbilst.');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $row['faila_nosaukums'] . '"');
    header('Content-Length: ' . strlen($row['csv_saturs']));
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    echo $row['csv_saturs'];
    exit;
}
