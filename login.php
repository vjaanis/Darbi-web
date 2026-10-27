<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';

use function Darbi\clearUserIdentity;
use function Darbi\csrfToken;
use function Darbi\currentUser;
use function Darbi\database;
use function Darbi\validCsrf;

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'none'; style-src 'self'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");

function loginError(): string
{
    usleep(300000);
    return 'Nepareizs lietotājvārds vai parole.';
}

$errorMessage = '';
try {
    if (currentUser() !== null) {
        header('Location: index.php', true, 303);
        exit;
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        if (!validCsrf($_POST['csrf'] ?? null)) {
            http_response_code(403);
            exit('Nederīgs pieprasījums.');
        }
        $username = $_POST['username'] ?? null;
        $password = $_POST['password'] ?? null;
        if (!is_string($username) || !is_string($password) || strlen($username) > 100 || strlen($password) > 1024) {
            $errorMessage = loginError();
        } else {
            $db = database();
            $query = $db->prepare('SELECT darbinieks_id, paroles_hash, aktivs, ir_admin, (blokeets_lidz IS NOT NULL AND blokeets_lidz > NOW()) AS locked, CASE WHEN blokeets_lidz IS NOT NULL AND blokeets_lidz <= NOW() THEN 0 ELSE neveiksmigi_meginajumi END AS attempts FROM lietotaji WHERE lietotajvards = ?');
            $query->execute([trim($username)]);
            $account = $query->fetch(PDO::FETCH_ASSOC);
            if (!$account || (int) $account['locked'] === 1) {
                $errorMessage = loginError();
            } else {
                $storedHash = $account['paroles_hash'];
                $modernHash = is_string($storedHash) && (password_get_info($storedHash)['algoName'] ?? 'unknown') !== 'unknown';
                $modernMatch = $modernHash && password_verify($password, $storedHash);
                // Tikai sākotnējam administratora kontam atļauj vienreizēju MD5 nomaiņu.
                $legacyMatch = (int) $account['darbinieks_id'] === 18 && !$modernHash && is_string($storedHash)
                    && preg_match('/^[0-9a-f]{32}$/iD', $storedHash) === 1
                    && hash_equals(strtolower($storedHash), md5($password));
                if ((int) $account['aktivs'] !== 1 || !($modernMatch || $legacyMatch)) {
                    $attempts = (int) $account['attempts'] + 1;
                    $failure = $attempts >= 5
                        ? $db->prepare('UPDATE lietotaji SET neveiksmigi_meginajumi = ?, blokeets_lidz = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE darbinieks_id = ?')
                        : $db->prepare('UPDATE lietotaji SET neveiksmigi_meginajumi = ?, blokeets_lidz = NULL WHERE darbinieks_id = ?');
                    $failure->execute([$attempts, $account['darbinieks_id']]);
                    $errorMessage = loginError();
                } else {
                    $finalHash = $storedHash;
                    if ($legacyMatch || password_needs_rehash($storedHash, PASSWORD_DEFAULT)) {
                        $upgradedHash = password_hash($password, PASSWORD_DEFAULT);
                        $upgrade = $db->prepare('UPDATE lietotaji SET paroles_hash = ? WHERE darbinieks_id = ? AND paroles_hash = ?');
                        $upgrade->execute([$upgradedHash, $account['darbinieks_id'], $storedHash]);
                        if ($upgrade->rowCount() !== 1) {
                            $errorMessage = 'Konta dati mainījās. Pieslēdzies vēlreiz.';
                        } else {
                            $finalHash = $upgradedHash;
                        }
                    }
                    if ($errorMessage === '') {
                        $db->prepare('UPDATE lietotaji SET neveiksmigi_meginajumi = 0, blokeets_lidz = NULL WHERE darbinieks_id = ?')
                            ->execute([$account['darbinieks_id']]);
                        session_regenerate_id(true);
                        clearUserIdentity();
                        $_SESSION['user_id'] = (int) $account['darbinieks_id'];
                        if ((int) $account['ir_admin'] === 1) {
                            $_SESSION['admin_id'] = (int) $account['darbinieks_id'];
                        }
                        $_SESSION['password_fingerprint'] = hash('sha256', $finalHash);
                        $_SESSION['last_seen'] = time();
                        $_SESSION['csrf'] = bin2hex(random_bytes(32));
                        header('Location: index.php', true, 303);
                        exit;
                    }
                }
            }
        }
        unset($password);
    } elseif (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
        header('Allow: GET, POST');
        http_response_code(405);
        exit('Neatļauta metode.');
    }
    $csrf = csrfToken();
} catch (Throwable $exception) {
    error_log('Darbi login: ' . get_class($exception));
    http_response_code(500);
    exit('Servera kļūda. Pārbaudi servera žurnālu.');
}
?>
<!doctype html>
<html lang="lv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Darbi · Pieteikšanās</title>
    <link rel="stylesheet" href="assets/users.css">
</head>
<body>
<main>
    <h1>Darbi</h1>
    <p>Piesakies ar savu lietotājvārdu un paroli.</p>
    <?php if ($errorMessage !== ''): ?><p class="notice" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p><?php endif; ?>
    <form method="post" class="card">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <label>Lietotājvārds <input name="username" autocomplete="username" required autofocus></label>
        <label>Parole <input name="password" type="password" autocomplete="current-password" required></label>
        <button type="submit">Pieteikties</button>
    </form>
</main>
</body>
</html>
