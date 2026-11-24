<?php
declare(strict_types=1);

namespace Darbi;

require_once __DIR__ . '/database.php';

function startUserSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    ini_set('session.use_strict_mode', '1');
    session_name('darbi_users');
    $path = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . '/';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $path,
        'secure' => (($_SERVER['HTTPS'] ?? '') === 'on' || getenv('DARBI_SECURE_COOKIE') === '1'),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

function browserCookieOptions(): array
{
    return [
        'expires' => 0,
        'path' => rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . '/',
        'secure' => (($_SERVER['HTTPS'] ?? '') === 'on' || getenv('DARBI_SECURE_COOKIE') === '1'),
        'httponly' => true,
        'samesite' => 'Strict',
    ];
}

function setBrowserCookie(string $name, string $value, bool $clear = false): void
{
    $options = browserCookieOptions();
    if ($clear) {
        $options['expires'] = time() - 3600;
    }
    setcookie($name, $value, $options);
}

function base64UrlEncode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function base64UrlDecode(string $value): ?string
{
    if (!preg_match('/^[A-Za-z0-9_-]+$/D', $value)) {
        return null;
    }
    $decoded = base64_decode(strtr($value, '-_', '+/') . str_repeat('=', (4 - strlen($value) % 4) % 4), true);

    return is_string($decoded) ? $decoded : null;
}

function csrfToken(): string
{
    $token = $_COOKIE['darbi_csrf'] ?? null;
    if (!is_string($token) || preg_match('/^[a-f0-9]{64}$/D', $token) !== 1) {
        $token = bin2hex(random_bytes(32));
        setBrowserCookie('darbi_csrf', $token);
    }

    return $token;
}

function validCsrf(mixed $submitted): bool
{
    return is_string($submitted) && hash_equals(csrfToken(), $submitted);
}

function clearUserIdentity(): void
{
    setBrowserCookie('darbi_auth', '', true);
    setBrowserCookie('darbi_csrf', '', true);
    if (session_status() === PHP_SESSION_ACTIVE) {
        unset($_SESSION['user_id'], $_SESSION['admin_id'], $_SESSION['last_seen'], $_SESSION['password_fingerprint']);
    }
}

function authenticateUser(int $id, string $passwordHash, bool $renewCsrf = false): void
{
    $payload = base64UrlEncode(json_encode(['id' => $id, 'expires' => time() + 1800], JSON_THROW_ON_ERROR));
    setBrowserCookie('darbi_auth', $payload . '.' . hash_hmac('sha256', $payload, $passwordHash));
    if ($renewCsrf) {
        setBrowserCookie('darbi_csrf', bin2hex(random_bytes(32)));
    }
}

function authenticatedUserId(array $row): ?int
{
    $cookie = $_COOKIE['darbi_auth'] ?? null;
    if (!is_string($cookie) || substr_count($cookie, '.') !== 1) {
        return null;
    }
    [$payload, $signature] = explode('.', $cookie, 2);
    $decoded = base64UrlDecode($payload);
    if ($decoded === null || preg_match('/^[a-f0-9]{64}$/D', $signature) !== 1) {
        return null;
    }
    try {
        $data = json_decode($decoded, true, 3, JSON_THROW_ON_ERROR);
    } catch (\JsonException) {
        return null;
    }
    if (!is_array($data) || !is_int($data['id'] ?? null) || !is_int($data['expires'] ?? null)
        || $data['id'] !== (int) $row['darbinieks_id'] || $data['expires'] < time()) {
        return null;
    }

    return hash_equals(hash_hmac('sha256', $payload, (string) $row['paroles_hash']), $signature) ? $data['id'] : null;
}

function currentUser(): ?array
{
    // Klientu importa īslaicīgajiem datiem saglabājam esošo PHP sesiju,
    // taču pieteikšanās identitāti no tās vairs neatvasina.
    startUserSession();
    $cookie = $_COOKIE['darbi_auth'] ?? null;
    if (!is_string($cookie) || !str_contains($cookie, '.')) {
        return null;
    }
    $payload = strstr($cookie, '.', true);
    $decoded = is_string($payload) ? base64UrlDecode($payload) : null;
    if ($decoded === null) return null;
    try { $data = json_decode($decoded, true, 3, JSON_THROW_ON_ERROR); } catch (\JsonException) { return null; }
    $id = is_array($data) && is_int($data['id'] ?? null) ? $data['id'] : 0;
    if ($id < 1) return null;
    $query = database()->prepare('SELECT l.darbinieks_id, l.lietotajvards, l.aktivs, l.ir_admin, l.paroles_hash, d.vards, d.uzvards FROM lietotaji l JOIN darbinieki d ON d.id = l.darbinieks_id WHERE l.darbinieks_id = ?');
    $query->execute([$id]);
    $row = $query->fetch(\PDO::FETCH_ASSOC);
    if (!$row || (int) $row['aktivs'] !== 1 || authenticatedUserId($row) === null) {
        clearUserIdentity();
        return null;
    }
    authenticateUser($id, $row['paroles_hash']);
    return [
        'darbinieks_id' => $id,
        'lietotajvards' => $row['lietotajvards'],
        'vards' => $row['vards'],
        'uzvards' => $row['uzvards'],
        'ir_admin' => (int) $row['ir_admin'] === 1,
    ];
}
