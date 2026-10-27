<?php
declare(strict_types=1);
namespace Darbi;

final class ClientImportConflict extends \RuntimeException {}

function requireClientImportAdmin(array $user): void
{
    if (empty($user['ir_admin'])) sendJson(['error'=>'Klientu imports pieejams tikai administratoram.'],403);
}

function clientImportColumns(): array
{
    return ['id', ...array_keys(clientFieldDefinitions()), 'pvn_maks', 'dalitais_r'];
}

/** Strict semicolon CSV; newlines inside quoted fields are permitted. */
function checkClientCsvSyntax(string $raw): void
{
    $state = 0;
    for ($i=0, $length=strlen($raw); $i<$length; ++$i) {
        $c = $raw[$i];
        if ($state === 2) {
            if ($c === '"') {
                if (($raw[$i+1] ?? '') === '"') ++$i;
                else $state = 3;
            }
        } elseif ($c === ';' || $c === "\r" || $c === "\n") {
            $state = 0;
        } elseif ($state === 3) {
            throw new \InvalidArgumentException('CSV: pēc aizverošās pēdiņas jābūt atdalītājam vai rindas beigām.');
        } elseif ($c === '"') {
            if ($state !== 0) throw new \InvalidArgumentException('CSV: pēdiņa neatļauta necitētā laukā.');
            $state = 2;
        } else $state = 1;
    }
    if ($state === 2) throw new \InvalidArgumentException('CSV: neaizvērtas pēdiņas.');
}

function parseClientImport(string $raw): array
{
    if (strlen($raw)>2097152 || $raw==='') throw new \InvalidArgumentException('CSV jābūt netukšam, līdz 2 MB.');
    if (str_contains($raw,"\0") || preg_match('//u',$raw)!==1) throw new \InvalidArgumentException('CSV jābūt UTF-8 kodējumā.');
    if (str_starts_with($raw,"\xEF\xBB\xBF")) $raw=substr($raw,3);
    checkClientCsvSyntax($raw);
    $stream=fopen('php://temp','w+b');
    if ($stream===false) throw new \RuntimeException('Nevar nolasīt CSV.');
    try {
        fwrite($stream,$raw); rewind($stream);
        $header=fgetcsv($stream,null,';','"','');
        if ($header===false) throw new \InvalidArgumentException('CSV nav galvenes.');
        $header=array_map(static fn($v): string=>strtolower(trim((string)$v)),$header);
        $columns=clientImportColumns();
        if (count($header)!==count($columns) || count(array_unique($header))!==count($columns)
            || array_diff($header,$columns) || array_diff($columns,$header)) {
            throw new \InvalidArgumentException('CSV galvenē vajadzīgi visi noteiktie klienta lauki, ieskaitot ID. Izmanto VFP eksporta procedūru vai CSV galvenes paraugu.');
        }
        $rows=[]; $record=1;
        while (($values=fgetcsv($stream,null,';','"',''))!==false) {
            ++$record;
            if ($values===[null]) continue;
            try {
                if (count($values)!==count($header)) throw new \InvalidArgumentException('Nepareizs lauku skaits.');
                $input=array_combine($header,$values);
                $client=['id'=>readInteger($input,'id',1,2147483647,'VFP klienta ID')];
                if (isset($rows[$client['id']])) throw new \InvalidArgumentException('Atkārtots klienta ID '.$client['id'].'.');
                foreach (clientFieldDefinitions() as $name=>$definition) {
                    if (($definition['type'] ?? '')==='number') {
                        $input[$name]=$input[$name]==='' ? '0' : $input[$name];
                        $value=readInteger($input,$name,$definition['minimum'],$definition['maximum'],$definition['label']);
                    } else {
                        $value=readText($input,$name,$definition['length'],$definition['label']);
                        if (!empty($definition['required']) && $value==='') throw new \InvalidArgumentException('Trūkst vērtības: '.$definition['label'].'.');
                    }
                    $client[$name]=$name==='lng' ? strtoupper((string)$value) : $value;
                }
                foreach (['pvn_maks','dalitais_r'] as $name) {
                    if (!in_array($input[$name],['0','1'],true)) throw new \InvalidArgumentException($name.': atļauts 0 vai 1.');
                    $client[$name]=(int)$input[$name];
                }
                $rows[$client['id']]=$client;
                if (count($rows)>10000) throw new \InvalidArgumentException('Vienā importā atļauti līdz 10 000 klientiem.');
            } catch (\InvalidArgumentException $error) {
                throw new \InvalidArgumentException('CSV ieraksts '.$record.': '.$error->getMessage());
            }
        }
        if (!$rows) throw new \InvalidArgumentException('CSV nav klientu ierakstu.');
        ksort($rows,SORT_NUMERIC);
        return array_values($rows);
    } finally { fclose($stream); }
}

function canonicalClient(array $row): array
{
    $result=[];
    foreach (clientImportColumns() as $column) $result[$column]=(string)($row[$column] ?? '');
    return $result;
}

function currentImportClients(array $rows, bool $lock=false): array
{
    $ids=array_column($rows,'id'); sort($ids,SORT_NUMERIC);
    $current=[];
    $columns='`'.implode('`,`',clientImportColumns()).'`';
    foreach (array_chunk($ids,500) as $chunk) {
        $sql='SELECT '.$columns.' FROM client WHERE id IN ('.implode(',',array_fill(0,count($chunk),'?')).') ORDER BY id'.($lock?' FOR UPDATE':'');
        foreach (fetchRows($sql,$chunk) as $row) $current[(int)$row['id']]=canonicalClient($row);
    }
    ksort($current,SORT_NUMERIC);
    return $current;
}

function clientImportPlan(array $rows, array $current): array
{
    $counts=['total'=>count($rows),'inserted'=>0,'updated'=>0,'unchanged'=>0];
    $sample=[];
    foreach ($rows as $row) {
        $id=$row['id'];
        $operation=!isset($current[$id])?'inserted':(canonicalClient($row)===$current[$id]?'unchanged':'updated');
        ++$counts[$operation];
        if (count($sample)<20) $sample[]=['id'=>$id,'name'=>$row['name'],'operation'=>$operation];
    }
    return $counts+['sample'=>$sample];
}

function clientImportStateHash(array $current): string
{
    return hash('sha256',json_encode($current,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
}

function previewClientImport(string $raw, string $filename, array $user): array
{
    requireClientImportAdmin($user);
    $filename=readText(['filename'=>basename(str_replace('\\','/',$filename))],'filename',255,'Faila nosaukums');
    if (strtolower(pathinfo($filename,PATHINFO_EXTENSION))!=='csv') throw new \InvalidArgumentException('Izvēlies CSV failu.');
    $rows=parseClientImport($raw);
    $current=currentImportClients($rows);
    $plan=clientImportPlan($rows,$current);
    $token=bin2hex(random_bytes(32));
    $_SESSION['client_import']=['token'=>$token,'owner'=>$user['darbinieks_id'],'expires'=>time()+900,
        'rows'=>$rows,'state_hash'=>clientImportStateHash($current),'file_hash'=>hash('sha256',$raw),'filename'=>$filename];
    return ['token'=>$token,'filename'=>$filename,'expires_in'=>900]+$plan;
}

function clientImportReceipt(array $row): array
{
    return ['ok'=>true,'nr'=>(int)$row['nr'],'total'=>(int)$row['kopskaits'],
        'inserted'=>(int)$row['pievienoti'],'updated'=>(int)$row['atjauninati'],'unchanged'=>(int)$row['nemainiti']];
}

function commitClientImport(array $input, array $user): array
{
    requireClientImportAdmin($user);
    $token=readText($input,'token',64,'Importa pārbaudes numurs');
    if (!preg_match('/^[a-f0-9]{64}$/D',$token)) throw new \InvalidArgumentException('Vispirms pārbaudi importa failu.');
    $db=database(); $locked=false;
    try {
        $locked=(int)$db->query("SELECT GET_LOCK(CONCAT(DATABASE(),':client-import'),5)")->fetchColumn()===1;
        if (!$locked) throw new ClientImportConflict('Pašlaik notiek cits klientu imports. Mēģini vēlreiz.');
        $prior=fetchRows('SELECT * FROM klientu_importi WHERE pieprasijums = ? AND darbinieks_id = ?',[$token,$user['darbinieks_id']]);
        if ($prior) return clientImportReceipt($prior[0]);
        $pending=$_SESSION['client_import'] ?? null;
        if (!is_array($pending) || !hash_equals($pending['token'],$token) || $pending['owner']!==$user['darbinieks_id'] || $pending['expires']<time()) {
            throw new ClientImportConflict('Faila pārbaude beigusies vai aizstāta. Pārbaudi failu vēlreiz.');
        }
        $db->beginTransaction();
        $rows=$pending['rows'];
        $current=currentImportClients($rows,true);
        if (!hash_equals($pending['state_hash'],clientImportStateHash($current))) {
            throw new ClientImportConflict('Klientu dati pēc pārbaudes mainījušies. Pārbaudi failu vēlreiz.');
        }
        $plan=clientImportPlan($rows,$current);
        $columns=clientImportColumns();
        $quoted='`'.implode('`,`',$columns).'`';
        $insert=$db->prepare('INSERT INTO client ('.$quoted.') VALUES ('.implode(',',array_fill(0,count($columns),'?')).')');
        $update=$db->prepare('UPDATE client SET '.implode(',',array_map(static fn($c)=>'`'.$c.'` = ?',array_slice($columns,1))).' WHERE id = ?');
        foreach ($rows as $row) {
            if (!isset($current[$row['id']])) $insert->execute(array_values($row));
            elseif (canonicalClient($row)!==$current[$row['id']]) $update->execute([...array_values(array_slice($row,1)),$row['id']]);
        }
        $date=(new \DateTimeImmutable('now',new \DateTimeZone('Europe/Riga')))->format('Y-m-d H:i:s');
        $log=$db->prepare('INSERT INTO klientu_importi (izveidots,darbinieks_id,izpilditajs,faila_nosaukums,faila_sha256,kopskaits,pievienoti,atjauninati,nemainiti,pieprasijums) VALUES (?,?,?,?,?,?,?,?,?,?)');
        $log->execute([$date,$user['darbinieks_id'],trim($user['vards'].' '.$user['uzvards']),$pending['filename'],$pending['file_hash'],
            $plan['total'],$plan['inserted'],$plan['updated'],$plan['unchanged'],$token]);
        $nr=(int)$db->lastInsertId();
        $db->commit();
        unset($_SESSION['client_import']);
        return ['ok'=>true,'nr'=>$nr]+array_intersect_key($plan,array_flip(['total','inserted','updated','unchanged']));
    } catch (\Throwable $error) {
        if ($db->inTransaction()) $db->rollBack();
        throw $error;
    } finally {
        if ($locked) $db->query("SELECT RELEASE_LOCK(CONCAT(DATABASE(),':client-import'))");
    }
}

function clientImportHistory(): array
{
    return fetchRows('SELECT nr,izveidots,izpilditajs,faila_nosaukums,kopskaits,pievienoti,atjauninati,nemainiti FROM klientu_importi ORDER BY nr DESC LIMIT 20');
}

function downloadClientImportTemplate(): never
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="klientu_importa_galvene.csv"');
    header('Cache-Control: no-store');
    $stream=fopen('php://output','wb');
    fwrite($stream,"\xEF\xBB\xBF");
    fputcsv($stream,array_map('strtoupper',clientImportColumns()),';','"','',"\r\n");
    fclose($stream); exit;
}
