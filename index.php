<?php
require_once __DIR__ . '/app/version.php';
require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/assets.php';
try {
    $user = Darbi\currentUser();
} catch (Throwable $exception) {
    error_log('Darbi index: ' . get_class($exception));
    http_response_code(500);
    exit('Servera kļūda. Pārbaudi servera žurnālu.');
}
if ($user === null) {
    header('Location: login.php', true, 302);
    exit;
}
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
// Vienīgais inline skripts ir import map; to atļauj ar vienreizēju nonce.
$cspNonce = base64_encode(random_bytes(16));
header("Content-Security-Policy: default-src 'none'; script-src 'self' 'nonce-$cspNonce'; style-src 'self'; connect-src 'self'; img-src 'self'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
$csrf = Darbi\csrfToken();
$overview = ($_GET['view'] ?? '') === 'jobs';
$exports = ($_GET['view'] ?? '') === 'exports';
$clientImport = ($_GET['view'] ?? '') === 'client-import';
$clients = ($_GET['view'] ?? '') === 'clients';
$services = ($_GET['view'] ?? '') === 'services';
if ($services && !$user['ir_admin']) {
    http_response_code(403);
    exit('Pakalpojumu pārvaldība pieejama tikai administratoram.');
}
if ($clients && !$user['ir_admin']) {
    http_response_code(403);
    exit('Klientu pārvaldība pieejama tikai administratoram.');
}
if ($clientImport && !$user['ir_admin']) {
    http_response_code(403);
    exit('Klientu imports pieejams tikai administratoram.');
}
if ($exports && !$user['ir_admin']) {
    http_response_code(403);
    exit('Datu eksports pieejams tikai administratoram.');
}
?>
<!doctype html>
<html lang="lv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <title>Darbi · <?= $services ? 'Pakalpojumi' : ($clients ? 'Klienti' : ($clientImport ? 'Klientu imports' : ($exports ? 'Datu eksports' : ($overview ? 'Pārskats' : 'Darba forma')))) ?></title>
    <script type="importmap" nonce="<?= $cspNonce ?>"><?= Darbi\assetImportMap() ?></script>
    <link rel="stylesheet" href="<?= Darbi\assetUrl('style.css') ?>"></head>
<body>
<?php
    $activeView = $services ? 'services' : ($clients ? 'clients' : ($clientImport ? 'client-import' : ($exports ? 'exports' : ($overview ? 'jobs' : ''))));
    $isAdmin = $user['ir_admin'];
    $todayText = null; // datumu ieraksta JS
    require __DIR__ . '/views/topbar.php';
?>

    <main>
        <div class="page-heading">
            <h1 id="page-title"><?= $services ? 'Pakalpojumi' : ($clients ? 'Klienti' : ($clientImport ? 'Klientu imports' : ($exports ? 'Datu eksports' : ($overview ? 'Mani darbi' : 'Jauns darbs')))) ?></h1>
            <span class="muted"><?= $services ? 'Pakalpojumu veidi, apakšiedaļas un mērvienības' : ($clients ? 'Redzamība darba ievades izvēlnē' : ($clientImport ? 'Klientu reģistrs no VFP' : ($exports ? 'Visu autoru darbi' : ($overview ? 'Tikai mani darbi' : 'Darba ievade un labošana')))) ?> · Darbinieks: <?= htmlspecialchars(trim($user['vards'] . ' ' . $user['uzvards']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
        </div>
        <div id="notice" role="status" aria-live="polite" hidden></div>
        <p id="loading" role="status">Ielādē datus…</p>
        <noscript>Darba ievadei ieslēdz JavaScript.</noscript>

        <?php if ($services): ?>
        <?php require __DIR__ . '/views/services.php'; ?>
        <?php elseif ($clients): ?>
        <?php require __DIR__ . '/views/clients.php'; ?>
        <?php elseif ($clientImport): ?>
        <?php require __DIR__ . '/views/client-import.php'; ?>
        <?php elseif ($exports): ?>
        <?php require __DIR__ . '/views/exports.php'; ?>
        <?php elseif ($overview): ?>
        <?php require __DIR__ . '/views/jobs.php'; ?>
        <?php else: ?>
        <div class="workspace" id="workspace" hidden>
            <?php require __DIR__ . "/views/job-form.php"; ?>
            <?php require __DIR__ . "/views/summary.php"; ?>
        </div>
        <?php endif; ?>
    </main>

    <script type="module" src="<?= Darbi\assetUrl(($services ? 'services' : ($clients ? 'clients' : ($clientImport ? 'client-import' : ($exports ? 'exports' : ($overview ? 'jobs' : 'app'))))) . '.js') ?>"></script>
</body>
</html>
