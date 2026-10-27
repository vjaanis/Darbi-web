<?php
declare(strict_types=1);

namespace Darbi;

function sendJson($value, int $status = 200): void
{
    http_response_code($status);
    header("Content-Type: application/json; charset=utf-8");
    header("Cache-Control: no-store");

    echo json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit();
}

function requirePost(): void
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        header("Allow: POST");
        sendJson(["error" => "Šai darbībai vajadzīgs POST pieprasījums."], 405);
    }
}

function requireJsonRequest(): void
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (!preg_match('~^application/json(?:\s*;|\s*$)~i', $contentType)) {
        sendJson(['error' => 'Pieprasījumam jābūt JSON formātā.'], 415);
    }
    if (!validCsrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
        sendJson(['error' => 'Nederīgs drošības tokens.'], 403);
    }
    if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 32768) {
        sendJson(['error' => 'Pieprasījums ir pārāk liels.'], 413);
    }
}

function readJsonBody(): array
{
    $raw = file_get_contents('php://input', false, null, 0, 32769);
    if ($raw === false) {
        sendJson(['error' => 'Neizdevās nolasīt pieprasījumu.'], 400);
    }
    if (strlen($raw) > 32768) {
        sendJson(['error' => 'Pieprasījums ir pārāk liels.'], 413);
    }
    $body = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

    if (!is_array($body) || (array_is_list($body) && $body !== [])) {
        throw new \InvalidArgumentException("Pieprasījumam jābūt JSON objektam.");
    }

    return $body;
}

function databaseStatus(): array
{
    $counts = [];

    foreach (["client", "darbi", "mnu_dati", "darbinieki"] as $table) {
        $counts[$table] = (int) database()
            ->query("SELECT COUNT(*) FROM $table")
            ->fetchColumn();
    }

    return ["ready" => true, "counts" => $counts];
}
