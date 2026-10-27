<?php
declare(strict_types=1);
namespace Darbi;

final class ServiceConflict extends \RuntimeException {}

function requireServicesAdmin(array $user): void
{
    if (empty($user['ir_admin'])) sendJson(['error' => 'Pakalpojumu pārvaldība pieejama tikai administratoram.'], 403);
}

function serviceVersion(array $row): string
{
    return hash_hmac('sha256', json_encode([$row['nr'], $row['tips'], $row['sdala'], $row['mervien'], $row['lng']], JSON_THROW_ON_ERROR), csrfToken());
}

function serviceRecord(array $row): array
{
    return $row + ['version' => serviceVersion($row)];
}

function listAdminServices(array $user): array
{
    requireServicesAdmin($user);
    return array_map(__NAMESPACE__ . '\\serviceRecord', fetchRows('SELECT nr,tips,sdala,mervien,lng FROM mnu_dati ORDER BY lng,tips,sdala,mervien,nr'));
}

function serviceInput(array $input): array
{
    $row = [
        'tips' => readText($input, 'tips', 50, 'Pakalpojuma veids'),
        'sdala' => readText($input, 'sdala', 220, 'Apakšiedaļa'),
        'mervien' => readText($input, 'mervien', 50, 'Mērvienība'),
        'lng' => readText($input, 'lng', 3, 'Valoda'),
    ];
    if ($row['tips'] === '' && $row['sdala'] === '' && $row['mervien'] === '') {
        throw new \InvalidArgumentException('Aizpildi vismaz pakalpojuma veidu, apakšiedaļu vai mērvienību.');
    }
    return $row;
}

function createService(array $input, array $user): array
{
    requireServicesAdmin($user);
    $row = serviceInput($input);
    $db = database();
    $statement = $db->prepare('INSERT INTO mnu_dati (tips,sdala,mervien,lng) VALUES (?,?,?,?)');
    $statement->execute(array_values($row));
    $rows = fetchRows('SELECT nr,tips,sdala,mervien,lng FROM mnu_dati WHERE nr = ?', [(int) $db->lastInsertId()]);
    return serviceRecord($rows[0]);
}

function changeService(array $input, array $user, bool $delete): array
{
    requireServicesAdmin($user);
    $nr = readInteger($input, 'nr', 1, 2147483647, 'Pakalpojuma numurs');
    $version = readText($input, 'version', 64, 'Ieraksta versija');
    if (!preg_match('/^[a-f0-9]{64}$/D', $version)) throw new \InvalidArgumentException('Atver ierakstu no saraksta vēlreiz.');
    $values = $delete ? [] : serviceInput($input);
    $db = database();
    $db->beginTransaction();
    try {
        $rows = fetchRows('SELECT nr,tips,sdala,mervien,lng FROM mnu_dati WHERE nr = ? FOR UPDATE', [$nr]);
        if (!$rows || !hash_equals(serviceVersion($rows[0]), $version)) {
            throw new ServiceConflict('Ieraksts pa šo laiku mainīts vai dzēsts. Atjauno sarakstu un atver ierakstu vēlreiz.');
        }
        if ($delete) {
            $statement = $db->prepare('DELETE FROM mnu_dati WHERE nr = ?');
            $statement->execute([$nr]);
            $result = ['ok' => true, 'nr' => $nr];
        } else {
            $statement = $db->prepare('UPDATE mnu_dati SET tips = ?, sdala = ?, mervien = ?, lng = ? WHERE nr = ?');
            $statement->execute([...array_values($values), $nr]);
            $result = serviceRecord(fetchRows('SELECT nr,tips,sdala,mervien,lng FROM mnu_dati WHERE nr = ?', [$nr])[0]);
        }
        $db->commit();
        return $result;
    } catch (\Throwable $error) {
        if ($db->inTransaction()) $db->rollBack();
        throw $error;
    }
}
