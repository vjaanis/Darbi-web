<?php
require_once __DIR__ . '/app/version.php';
require_once __DIR__ . '/app/auth.php';
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
$report = $overview || $exports || $clientImport || $clients || $services;
?>
<!doctype html>
<html lang="lv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <title>Darbi · <?= $services ? 'Pakalpojumi' : ($clients ? 'Klienti' : ($clientImport ? 'Klientu imports' : ($exports ? 'Datu eksports' : ($overview ? 'Pārskats' : 'Darba forma')))) ?></title>
    <link rel="stylesheet" href="assets/style.css?v=20261027-section-wrap">
    <?php if ($exports): ?><link rel="stylesheet" href="assets/exports.css?v=20261021-export1"><?php endif; ?>
    <?php if ($clientImport): ?><link rel="stylesheet" href="assets/client-import.css?v=20261022-import1"><?php endif; ?>
</head>
<body>
    <header class="topbar">
        <a class="brand" href="./"><span class="brand-icon">D</span>Darbi <small>v<?= Darbi\APP_VERSION ?></small></a>
        <nav class="main-menu" aria-label="Galvenā izvēlne">
            <a class="menu-item <?= !$report ? 'active' : '' ?>" href="./">Jauns darbs</a>
            <a class="menu-item <?= $overview ? 'active' : '' ?>" href="?view=jobs">Pārskats</a>
            <?php if ($user['ir_admin']): ?><a class="menu-item <?= $exports ? 'active' : '' ?>" href="?view=exports">Datu eksports</a><?php endif; ?>
            <?php if ($user['ir_admin']): ?><a class="menu-item <?= $clientImport ? 'active' : '' ?>" href="?view=client-import">Klientu imports</a><?php endif; ?>
            <?php if ($user['ir_admin']): ?><a class="menu-item <?= $clients ? 'active' : '' ?>" href="?view=clients">Klienti</a><?php endif; ?>
            <?php if ($user['ir_admin']): ?><a class="menu-item <?= $services ? 'active' : '' ?>" href="?view=services">Pakalpojumi</a><?php endif; ?>
            <button class="menu-item" type="button" disabled title="Vēl nav pieejams">Iestatījumi</button>
        </nav>
        <?php if ($user['ir_admin']): ?><a class="menu-item" href="users.php">Konti</a><?php endif; ?>
        <form method="post" action="logout.php">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <button class="menu-item" type="submit">Izrakstīties</button>
        </form>
        <span class="date" id="today"></span>
    </header>

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

    <script type="module" src="assets/<?= $services ? 'services' : ($clients ? 'clients' : ($clientImport ? 'client-import' : ($exports ? 'exports' : ($overview ? 'jobs' : 'app')))) ?>.js?v=20261027-job-copy"></script>
</body>
</html>
