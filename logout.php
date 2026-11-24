<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';

use function Darbi\clearUserIdentity;
use function Darbi\validCsrf;

header('Cache-Control: no-store');
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
header('Location: login.php', true, 303);
exit;
