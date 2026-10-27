<?php
declare(strict_types=1);

namespace Darbi;

// Šajā failā ir tikai HTTP maršruti. SQL un biznesa loģika atrodas app/ mapē.
require_once __DIR__ . "/app/database.php";
require_once __DIR__ . "/app/validation.php";
require_once __DIR__ . "/app/calculation.php";
require_once __DIR__ . "/app/client-fields.php";
require_once __DIR__ . "/app/clients.php";
require_once __DIR__ . "/app/catalog.php";
require_once __DIR__ . "/app/jobs.php";
require_once __DIR__ . "/app/http.php";
require_once __DIR__ . "/app/auth.php";
require_once __DIR__ . "/app/exports.php";
require_once __DIR__ . "/app/client-import.php";

try {
    $user = currentUser();
    if ($user === null) {
        sendJson(["error" => "Jāpiesakās."], 401);
    }
    $action = $_GET["action"] ?? "status";

    switch ($action) {
        case 'preview_client_import':
            requireClientImportAdmin($user);
            requirePost();
            if (!validCsrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) sendJson(['error'=>'Nederīgs drošības tokens.'],403);
            $file=$_FILES['file'] ?? null;
            if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK) {
                throw new \InvalidArgumentException('Neizdevās augšupielādēt CSV. Izvēlies failu līdz 2 MB.');
            }
            if (!is_string($file['tmp_name']) || !is_uploaded_file($file['tmp_name']) || $file['size']>2097152) {
                throw new \InvalidArgumentException('Nederīga augšupielāde vai fails pārsniedz 2 MB.');
            }
            $raw=file_get_contents($file['tmp_name']);
            if ($raw===false) throw new \RuntimeException('CSV nevar nolasīt.');
            sendJson(previewClientImport($raw,(string)$file['name'],$user));

        case 'commit_client_import':
            requireClientImportAdmin($user);
            requirePost();
            requireJsonRequest();
            sendJson(commitClientImport(readJsonBody(),$user));

        case 'client_import_history':
            requireClientImportAdmin($user);
            sendJson(clientImportHistory());

        case 'client_import_template':
            requireClientImportAdmin($user);
            downloadClientImportTemplate();

        case 'client_export_tool':
            requireClientImportAdmin($user);
            header('Content-Type: text/plain; charset=us-ascii');
            header('Content-Disposition: attachment; filename="eksportet_klientus.prg"');
            header('Cache-Control: no-store');
            readfile(__DIR__.'/app/vfp/eksportet_klientus.prg');
            exit;

        case 'export_authors':
            requireExportAdmin($user);
            sendJson(exportAuthors());

        case 'export_jobs':
            requireExportAdmin($user);
            sendJson(listExportJobs($_GET));

        case 'exports':
            requireExportAdmin($user);
            sendJson(listExports($_GET));

        case 'create_export':
            requireExportAdmin($user);
            requirePost();
            requireJsonRequest();
            sendJson(createExport(readJsonBody(), $user), 201);

        case 'download_export':
            requireExportAdmin($user);
            downloadExport(readInteger($_GET, 'nr', 1, 2147483647, 'Eksporta numurs'));

        case "status":
            sendJson(databaseStatus());

        case "clients":
            $search = readText($_GET, "q", 60, "Klienta meklēšana");
            sendJson(searchClients($search));

        case "employees":
            sendJson(employees());

        case "catalog":
            $kind = readText($_GET, "kind", 20, "Saraksta veids", "types");
            $language = readText($_GET, "lng", 3, "Valoda", "LV");
            $type = readText($_GET, "tips", 100, "Pakalpojuma veids");
            $section = readText($_GET, "sdala", 220, "Apakšiedaļa");

            switch ($kind) {
                case "types":
                    sendJson(serviceTypes($language));
                case "sections":
                    sendJson(serviceSections($language, $type));
                case "units":
                    sendJson(serviceUnits($section));
                default:
                    throw new \InvalidArgumentException("Nezināms klasifikatora saraksts.");
            }

        case "history":
            $clientId = readInteger($_GET, "id", 1, 2147483647, "Klients");
            sendJson(clientHistory($clientId, $user['darbinieks_id']));

        case "recent":
            sendJson(recentJobs($user['darbinieks_id']));

        case "jobs":
            sendJson(listJobs($_GET, $user['darbinieks_id']));

        case "job":
            sendJson(jobDetails(readInteger($_GET, 'nr', 1, 2147483647, 'Darba numurs'), $user['darbinieks_id']));

        case "update_job":
        case "delete_job":
            requirePost();
            requireJsonRequest();
            sendJson(changeJob(readJsonBody(), $user, $action === 'delete_job'));

        case "calculate":
            requirePost();
            requireJsonRequest();
            sendJson(calculateAmount(readJsonBody()));

        case "create_job":
            requirePost();
            requireJsonRequest();
            sendJson(createJob(readJsonBody(), $user['darbinieks_id']), 201);

        default:
            sendJson(["error" => "Darbība nav atrasta."], 404);
    }
} catch (ExportConflict | ClientImportConflict $error) {
    sendJson(['error' => $error->getMessage()], 409);
} catch (\InvalidArgumentException $error) {
    sendJson(["error" => $error->getMessage()], 422);
} catch (\JsonException $error) {
    sendJson(["error" => "Nederīgs JSON pieprasījums."], 400);
} catch (\Throwable $error) {
    error_log("Darbi: " . $error->getMessage());
    sendJson(
        ["error" => "Servera kļūda. Dati nav apstiprināti; pārbaudi servera kļūdu žurnālu."],
        500,
    );
}
