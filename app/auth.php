<?php
declare(strict_types=1);

namespace Darbi;

require_once __DIR__ . '/database.php';

final class DatabaseSessionHandler implements \SessionHandlerInterface
{
    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string
    {
        $query = database()->prepare(
            'SELECT saturs FROM darba_web_sesijas WHERE sesijas_id = ? AND beidzas > NOW()'
        );
        $query->execute([$id]);
        $value = $query->fetchColumn();

        return is_string($value) ? $value : '';
    }

    public function write(string $id, string $data): bool
    {
        $query = database()->prepare(
            'INSERT INTO darba_web_sesijas (sesijas_id, saturs, beidzas) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 2 HOUR))
             ON DUPLICATE KEY UPDATE saturs = VALUES(saturs), beidzas = VALUES(beidzas)'
        );

        return $query->execute([$id, $data]);
    }

    public function destroy(string $id): bool
    {
        return database()->prepare('DELETE FROM darba_web_sesijas WHERE sesijas_id = ?')->execute([$id]);
    }

    public function gc(int $maxLifetime): int|false
    {
        $query = database()->prepare('DELETE FROM darba_web_sesijas WHERE beidzas <= NOW()');
        $query->execute();

        return $query->rowCount();
    }
}

function databaseSessionStorageIsAvailable(): bool
{
    static $available = null;
    if ($available !== null) {
        return $available;
    }

    try {
        database()->exec(
            'CREATE TABLE IF NOT EXISTS darba_web_sesijas (
                sesijas_id VARCHAR(128) NOT NULL PRIMARY KEY,
                saturs MEDIUMBLOB NOT NULL,
                beidzas DATETIME NOT NULL,
                KEY idx_darba_web_sesijas_beidzas (beidzas)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        return $available = true;
    } catch (\Throwable $exception) {
        error_log('Darbi session storage: ' . get_class($exception));
        return $available = false;
    }
}

function startUserSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    ini_set('session.use_strict_mode', '1');
    session_name('darbi_users');
    if (databaseSessionStorageIsAvailable()) {
        session_set_save_handler(new DatabaseSessionHandler(), true);
    }
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

function csrfToken(): string
{
    startUserSession();
    if (!isset($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function validCsrf(mixed $submitted): bool
{
    return is_string($submitted) && hash_equals(csrfToken(), $submitted);
}

function clearUserIdentity(): void
{
    unset($_SESSION['user_id'], $_SESSION['admin_id'], $_SESSION['last_seen'], $_SESSION['password_fingerprint']);
}

function currentUser(): ?array
{
    startUserSession();
    $id = (int) ($_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? 0);
    if ($id < 1) {
        return null;
    }
    if (time() - (int) ($_SESSION['last_seen'] ?? 0) > 1800) {
        clearUserIdentity();
        return null;
    }
    $query = database()->prepare('SELECT l.darbinieks_id, l.lietotajvards, l.aktivs, l.ir_admin, l.paroles_hash, d.vards, d.uzvards FROM lietotaji l JOIN darbinieki d ON d.id = l.darbinieks_id WHERE l.darbinieks_id = ?');
    $query->execute([$id]);
    $row = $query->fetch(\PDO::FETCH_ASSOC);
    $fingerprint = $row && is_string($row['paroles_hash']) ? hash('sha256', $row['paroles_hash']) : '';
    if (!$row || (int) $row['aktivs'] !== 1 || !hash_equals($fingerprint, (string) ($_SESSION['password_fingerprint'] ?? ''))) {
        clearUserIdentity();
        return null;
    }
    $_SESSION['last_seen'] = time();
    $_SESSION['user_id'] = $id;
    if ((int) $row['ir_admin'] !== 1) {
        unset($_SESSION['admin_id']);
    }
    return [
        'darbinieks_id' => $id,
        'lietotajvards' => $row['lietotajvards'],
        'vards' => $row['vards'],
        'uzvards' => $row['uzvards'],
        'ir_admin' => (int) $row['ir_admin'] === 1,
    ];
}
