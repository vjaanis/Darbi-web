<?php
declare(strict_types=1);

require_once __DIR__ . '/app/database.php';

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'none'; style-src 'self'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");

ini_set('session.use_strict_mode', '1');
session_name('darbi_users');
$cookiePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . '/';
session_set_cookie_params([
    'lifetime' => 0,
    'path' => $cookiePath,
    'secure' => (($_SERVER['HTTPS'] ?? '') === 'on' || getenv('DARBI_SECURE_COOKIE') === '1'),
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirectWithMessage(string $message): never
{
    $_SESSION['message'] = $message;
    header('Location: users.php', true, 303);
    exit;
}

function validNewPassword(string $password, string $confirmation): bool
{
    return $password === $confirmation && strlen($password) >= 12 && strlen($password) <= 72;
}

try {
    $db = Darbi\database();
    if (!isset($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    $adminId = (int) ($_SESSION['admin_id'] ?? 0);
    if ($adminId !== 0) {
        $expired = time() - (int) ($_SESSION['last_seen'] ?? 0) > 1800;
        $check = $db->prepare('SELECT aktivs, ir_admin, paroles_hash FROM lietotaji WHERE darbinieks_id = ?');
        $check->execute([$adminId]);
        $admin = $check->fetch(PDO::FETCH_ASSOC);
        $fingerprint = $admin && is_string($admin['paroles_hash']) ? hash('sha256', $admin['paroles_hash']) : '';
        if ($expired || !$admin || (int) $admin['aktivs'] !== 1 || (int) $admin['ir_admin'] !== 1
            || !hash_equals($fingerprint, (string) ($_SESSION['password_fingerprint'] ?? ''))) {
            unset($_SESSION['user_id'], $_SESSION['admin_id'], $_SESSION['last_seen'], $_SESSION['password_fingerprint']);
            $adminId = 0;
        } else {
            $_SESSION['last_seen'] = time();
        }
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $csrf = $_POST['csrf'] ?? '';
        if (!is_string($csrf) || !hash_equals($_SESSION['csrf'], $csrf)) {
            http_response_code(403);
            exit('Nederīgs pieprasījums.');
        }
        $action = $_POST['action'] ?? '';
        if (!is_string($action)) {
            http_response_code(400);
            exit('Nederīga darbība.');
        }

        if ($action === 'login') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            if (!is_string($username) || !is_string($password)) {
                redirectWithMessage('Nepareizs lietotājvārds vai parole.');
            }
            $statement = $db->prepare("SELECT darbinieks_id, paroles_hash, aktivs, ir_admin, (blokeets_lidz IS NOT NULL AND blokeets_lidz > NOW()) AS locked, CASE WHEN blokeets_lidz IS NOT NULL AND blokeets_lidz <= NOW() THEN 0 ELSE neveiksmigi_meginajumi END AS attempts FROM lietotaji WHERE lietotajvards = ?");
            $statement->execute([trim($username)]);
            $account = $statement->fetch(PDO::FETCH_ASSOC);
            if (!$account || (int) $account['locked'] === 1) {
                usleep(300000);
                redirectWithMessage('Nepareizs lietotājvārds vai parole.');
            }
            $storedHash = $account['paroles_hash'];
            $modernHash = is_string($storedHash)
                && (password_get_info($storedHash)['algoName'] ?? 'unknown') !== 'unknown';
            $modernMatch = $modernHash && password_verify($password, $storedHash);
            // Vienreizēja pāreja no jau datubāzē esošā 32 zīmju MD5 hash.
            $legacyMatch = !$modernHash && is_string($storedHash)
                && preg_match('/^[0-9a-f]{32}$/iD', $storedHash) === 1
                && hash_equals(strtolower($storedHash), md5($password));
            $valid = (int) $account['aktivs'] === 1
                && (int) $account['ir_admin'] === 1
                && ($modernMatch || $legacyMatch);
            if (!$valid) {
                $attempts = (int) $account['attempts'] + 1;
                $failure = $attempts >= 5
                    ? $db->prepare('UPDATE lietotaji SET neveiksmigi_meginajumi = ?, blokeets_lidz = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE darbinieks_id = ?')
                    : $db->prepare('UPDATE lietotaji SET neveiksmigi_meginajumi = ?, blokeets_lidz = NULL WHERE darbinieks_id = ?');
                $failure->execute([$attempts, $account['darbinieks_id']]);
                usleep(300000);
                redirectWithMessage('Nepareizs lietotājvārds vai parole.');
            }
            $finalHash = $storedHash;
            if ($legacyMatch || password_needs_rehash($storedHash, PASSWORD_DEFAULT)) {
                $upgradedHash = password_hash($password, PASSWORD_DEFAULT);
                $upgrade = $db->prepare('UPDATE lietotaji SET paroles_hash = ? WHERE darbinieks_id = ? AND paroles_hash = ?');
                $upgrade->execute([$upgradedHash, $account['darbinieks_id'], $storedHash]);
                if ($upgrade->rowCount() !== 1) {
                    redirectWithMessage('Konta dati mainījās. Pieslēdzies vēlreiz.');
                }
                $finalHash = $upgradedHash;
            }
            unset($password);
            $db->prepare('UPDATE lietotaji SET neveiksmigi_meginajumi = 0, blokeets_lidz = NULL WHERE darbinieks_id = ?')
                ->execute([$account['darbinieks_id']]);
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $account['darbinieks_id'];
            $_SESSION['user_id'] = (int) $account['darbinieks_id'];
            $_SESSION['password_fingerprint'] = hash('sha256', $finalHash);
            $_SESSION['last_seen'] = time();
            redirectWithMessage('Pieteikšanās izdevās.');
        }

        if ($action === 'logout') {
            unset($_SESSION['user_id'], $_SESSION['admin_id'], $_SESSION['last_seen'], $_SESSION['password_fingerprint']);
            session_regenerate_id(true);
            redirectWithMessage('Jūs esat izrakstījies.');
        }

        if ($action === 'save_user' && $adminId !== 0) {
            $targetId = filter_var($_POST['darbinieks_id'] ?? null, FILTER_VALIDATE_INT);
            $password = $_POST['password'] ?? '';
            $confirmation = $_POST['confirmation'] ?? '';
            $current = $_POST['current_password'] ?? '';
            if ($targetId === false || $targetId < 1 || !is_string($password)
                || !is_string($confirmation) || !is_string($current)) {
                redirectWithMessage('Nederīgi konta dati.');
            }
            $statement = $db->prepare('SELECT paroles_hash, ir_admin FROM lietotaji WHERE darbinieks_id = ?');
            $statement->execute([$targetId]);
            $target = $statement->fetch(PDO::FETCH_ASSOC);
            if (!$target) {
                redirectWithMessage('Konts nav atrasts.');
            }
            $active = isset($_POST['active']) ? 1 : 0;
            if ($targetId === $adminId && $active !== 1) {
                redirectWithMessage('Savu administratora kontu nevar deaktivizēt.');
            }
            if ($password !== '' && !validNewPassword($password, $confirmation)) {
                redirectWithMessage('Parolēm jāsakrīt un jābūt 12–72 baitus garām.');
            }
            if ($active === 1 && $password === '' && $target['paroles_hash'] === null) {
                redirectWithMessage('Aktivizēšanai vispirms ievadi paroli.');
            }
            if ($targetId === $adminId && $password !== ''
                && (!is_string($target['paroles_hash']) || !password_verify($current, $target['paroles_hash']))) {
                redirectWithMessage('Pašreizējā parole nav pareiza.');
            }
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                unset($password, $confirmation, $current);
                $db->prepare('UPDATE lietotaji SET paroles_hash = ?, aktivs = ?, neveiksmigi_meginajumi = 0, blokeets_lidz = NULL WHERE darbinieks_id = ?')
                    ->execute([$hash, $active, $targetId]);
                if ($targetId === $adminId) {
                    session_regenerate_id(true);
                    $_SESSION['password_fingerprint'] = hash('sha256', $hash);
                }
            } else {
                $db->prepare('UPDATE lietotaji SET aktivs = ? WHERE darbinieks_id = ?')
                    ->execute([$active, $targetId]);
            }
            redirectWithMessage('Konta izmaiņas saglabātas.');
        }
        http_response_code(403);
        exit('Darbība nav atļauta.');
    }


    $users = $adminId !== 0
        ? $db->query('SELECT l.darbinieks_id, l.lietotajvards, l.aktivs, l.ir_admin, l.paroles_hash IS NOT NULL AS has_password, d.vards, d.uzvards FROM lietotaji l JOIN darbinieki d ON d.id = l.darbinieks_id ORDER BY l.darbinieks_id')->fetchAll(PDO::FETCH_ASSOC)
        : [];
    $message = $_SESSION['message'] ?? '';
    unset($_SESSION['message']);
} catch (Throwable $error) {
    error_log('Darbi users: ' . get_class($error));
    http_response_code(500);
    exit('Servera kļūda. Pārbaudi servera žurnālu.');
}
?>
<!doctype html>
<html lang="lv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Darbi · Lietotāju konti</title>
    <link rel="stylesheet" href="assets/users.css">
</head>
<body>
<main>
    <h1>Lietotāju konti</h1>
    <?php if ($message !== ''): ?><p class="notice" role="status"><?= h($message) ?></p><?php endif; ?>

    <?php if ($adminId !== 0): ?>
        <p>Administrators. Ievadi jaunu paroli, lai to mainītu; tukšs paroles lauks saglabā esošo.</p>
        <form method="post" class="logout">
            <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
            <input type="hidden" name="action" value="logout">
            <button type="submit">Izrakstīties</button>
        </form>
        <?php foreach ($users as $user): ?>
            <form method="post" class="card">
                <h2><?= h($user['vards'] . ' ' . $user['uzvards']) ?> <small>(<?= h($user['lietotajvards']) ?>)</small></h2>
                <p><?= (int) $user['aktivs'] === 1 ? 'Aktīvs' : 'Neaktīvs' ?> · <?= (int) $user['has_password'] === 1 ? 'Parole iestatīta' : 'Paroles nav' ?><?= (int) $user['ir_admin'] === 1 ? ' · Administrators' : '' ?></p>
                <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                <input type="hidden" name="action" value="save_user">
                <input type="hidden" name="darbinieks_id" value="<?= (int) $user['darbinieks_id'] ?>">
                <?php if ((int) $user['darbinieks_id'] === $adminId): ?>
                    <label>Pašreizējā parole <input name="current_password" type="password" autocomplete="current-password"></label>
                <?php endif; ?>
                <label>Jaunā parole <input name="password" type="password" autocomplete="new-password" minlength="12"></label>
                <label>Atkārtoti <input name="confirmation" type="password" autocomplete="new-password" minlength="12"></label>
                <label class="check"><input name="active" type="checkbox" <?= (int) $user['aktivs'] === 1 ? 'checked' : '' ?>> Aktīvs</label>
                <button type="submit">Saglabāt kontu</button>
            </form>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Administratora pieteikšanās.</p>
        <form method="post" class="card">
            <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
            <input type="hidden" name="action" value="login">
            <label>Lietotājvārds <input name="username" autocomplete="username" required></label>
            <label>Parole <input name="password" type="password" autocomplete="current-password" required></label>
            <button type="submit">Pieteikties</button>
        </form>
    <?php endif; ?>
</main>
</body>
</html>




