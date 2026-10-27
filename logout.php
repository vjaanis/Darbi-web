<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';

use function Darbi\clearUserIdentity;
use function Darbi\startUserSession;
use function Darbi\validCsrf;

header('Cache-Control: no-store');
startUserSession();
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Neatļauta metode.');
}
if (!validCsrf($_POST['csrf'] ?? null)) {
    http_response_code(403);
    exit('Nederīgs pieprasījums.');
}
clearUserIdentity();
unset($_SESSION['csrf']);
session_regenerate_id(true);
header('Location: login.php', true, 303);
exit;
