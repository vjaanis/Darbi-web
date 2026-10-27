<?php
declare(strict_types=1);

namespace Darbi;

function readText(
    array $input,
    string $name,
    int $maximumLength,
    string $label,
    string $default = "",
): string {
    $rawValue = $input[$name] ?? $default;

    if (!is_string($rawValue) && !is_numeric($rawValue)) {
        throw new \InvalidArgumentException($label . ": nederīga vērtība.");
    }

    $value = trim((string) $rawValue);
    $length = preg_match_all("/./us", $value);

    if ($length === false || $length > $maximumLength) {
        throw new \InvalidArgumentException(
            $label . ": atļautas līdz " . $maximumLength . " zīmēm.",
        );
    }

    return $value;
}

function readInteger(
    array $input,
    string $name,
    int $minimum,
    int $maximum,
    string $label,
    int $default = 0,
): int {
    $rawValue = $input[$name] ?? $default;

    if (is_bool($rawValue) || is_array($rawValue)) {
        throw new \InvalidArgumentException($label . ": ievadi veselu skaitli.");
    }

    $value = filter_var($rawValue, FILTER_VALIDATE_INT);

    if ($value === false || $value < $minimum || $value > $maximum) {
        throw new \InvalidArgumentException(
            $label . ": atļauta vērtība no " . $minimum . " līdz " . $maximum . ".",
        );
    }

    return $value;
}

function readDate(array $input, string $name, string $label): ?string
{
    $value = readText($input, $name, 10, $label);

    if ($value === "") {
        return null;
    }

    $date = \DateTimeImmutable::createFromFormat("!Y-m-d", $value);

    if (!$date || $date->format("Y-m-d") !== $value) {
        throw new \InvalidArgumentException($label . ": nederīgs datums.");
    }

    return $value;
}

function readBoolean(array $input, string $name): bool
{
    $value = $input[$name] ?? false;

    if (in_array($value, [true, 1, "1", "on"], true)) {
        return true;
    }

    if (in_array($value, [false, 0, "0", "", null], true)) {
        return false;
    }

    throw new \InvalidArgumentException("Nederīga izvēles rūtiņas vērtība: " . $name);
}

/** Piemēram, 12.34 ar divām zīmēm pārvērš par veselu skaitli 1234. */
function readScaledDecimal(
    array $input,
    string $name,
    int $places,
    int $maximum,
    string $label,
): int {
    $value = str_replace(",", ".", readText($input, $name, 30, $label, "0"));
    $pattern = "/^\d+(?:\.\d{1," . $places . '})?$/D';

    if (!preg_match($pattern, $value)) {
        throw new \InvalidArgumentException(
            $label . ": atļautas līdz " . $places . " decimālzīmēm.",
        );
    }

    $parts = explode(".", $value);
    $wholePart = ltrim($parts[0], "0");

    if (strlen($wholePart) > 9) {
        throw new \InvalidArgumentException($label . ": pārāk liels skaitlis.");
    }

    $fraction = str_pad($parts[1] ?? "", $places, "0");
    $scaledValue = (int) $parts[0] * 10 ** $places + (int) $fraction;

    if ($scaledValue > $maximum) {
        throw new \InvalidArgumentException($label . ": pārsniegta atļautā vērtība.");
    }

    return $scaledValue;
}

function formatDecimal(int $scaledValue, int $places): string
{
    $scale = 10 ** $places;
    $wholePart = intdiv($scaledValue, $scale);
    $fraction = str_pad((string) ($scaledValue % $scale), $places, "0", STR_PAD_LEFT);

    return $wholePart . "." . $fraction;
}
