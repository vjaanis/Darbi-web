<?php
declare(strict_types=1);

namespace Darbi;

/** Viena PDO pieslēguma vieta visai lietotnei. */
function database(): \PDO
{
    static $connection = null;

    if ($connection === null) {
        $settings = require dirname(__DIR__) . "/config.php";
        $dsn =
            "mysql:host=" .
            $settings["host"] .
            ";dbname=" .
            $settings["database"] .
            ";charset=utf8mb4";

        $connection = new \PDO($dsn, $settings["user"], $settings["password"], [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    return $connection;
}

function fetchRows(string $sql, array $parameters = []): array
{
    $statement = database()->prepare($sql);
    $statement->execute($parameters);

    return $statement->fetchAll();
}

/** Tabulas un kolonnas nosaka servera kods, nevis pārlūka pieprasījums. */
function insertRecord(string $table, array $values): int
{
    if ($table !== 'darbi') {
        throw new \LogicException("Neatļauta ievades tabula.");
    }

    $columnList = "`" . implode("`, `", array_keys($values)) . "`";
    $placeholders = implode(", ", array_fill(0, count($values), "?"));
    $sql = "INSERT INTO $table ($columnList) VALUES ($placeholders)";

    $statement = database()->prepare($sql);
    $statement->execute(array_values($values));

    return (int) database()->lastInsertId();
}
