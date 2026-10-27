<?php
declare(strict_types=1);

namespace Darbi;

const QUANTITY_DECIMAL_PLACES = 2;
const PRICE_DECIMAL_PLACES = 4;

const MAXIMUM_SCALED_QUANTITY = 999999; // 9999.99
const MAXIMUM_SCALED_QUANTITY_1800 = 10000000; // 100000.00
const MAXIMUM_SCALED_PRICE = 9999999900; // 999999.9900

/**
 * VFP izmanto LEFT(RIGHT(ALLTRIM(mervien), 10), 4).
 * UTF-8 teksts jāskaita zīmēs, citādi, piemēram, "1800 zīmes" dod kļūdainu kodu.
 */
function unitDivisor(string $unit): int
{
    $characters = preg_split("//u", trim($unit), -1, PREG_SPLIT_NO_EMPTY);
    $lastTenCharacters = array_slice($characters, -10);
    $code = (int) implode("", array_slice($lastTenCharacters, 0, 4));

    return in_array($code, [1500, 1800], true) ? $code : 1;
}

function roundPositiveDivision(int $numerator, int $denominator): int
{
    return intdiv($numerator + intdiv($denominator, 2), $denominator);
}

/** Vispirms dala reizinājumu, lai procentu piemērošana nepārpildītu 64 bitus. */
function roundDiscountedProduct(int $product, int $percent, int $denominator): int
{
    $whole = intdiv($product, $denominator);
    $remainder = $product % $denominator;

    return $whole * $percent + roundPositiveDivision($remainder * $percent, $denominator);
}

/** Tīrs aprēķins: nelasa datubāzi un neko nesaglabā. */
function calculateAmount(array $input): array
{
    $unit = readText($input, "mervien", 50, "Mērvienība");
    $divisor = unitDivisor($unit);
    $quantity = readScaledDecimal(
        $input,
        "daudz",
        QUANTITY_DECIMAL_PLACES,
        $divisor === 1800 ? MAXIMUM_SCALED_QUANTITY_1800 : MAXIMUM_SCALED_QUANTITY,
        "Daudzums",
    );
    $price = readScaledDecimal($input, "cena", PRICE_DECIMAL_PLACES, MAXIMUM_SCALED_PRICE, "Cena");
    $discount = readInteger($input, "atlaide", 0, 100, "Atlaide");

    // Daudzumam ir 2 decimālzīmes, cenai 4; atlaide ir procentos.
    $product = $quantity * $price;
    $percent = 100 - $discount;
    $baseDenominator = 10 ** QUANTITY_DECIMAL_PLACES * 100;

    if ($divisor === 1) {
        $amount = roundDiscountedProduct($product, $percent, $baseDenominator);
    } else {
        // VFP īpašajām mērvienībām noapaļo līdz centiem, nevis 4 zīmēm.
        $centDenominator = $baseDenominator * $divisor * 100;
        $amount = roundDiscountedProduct($product, $percent, $centDenominator) * 100;
    }

    return [
        "daudz" => formatDecimal($quantity, QUANTITY_DECIMAL_PLACES),
        "cena" => formatDecimal($price, PRICE_DECIMAL_PLACES),
        "atlaide" => $discount,
        "summa" => formatDecimal($amount, PRICE_DECIMAL_PLACES),
        "divisor" => $divisor,
    ];
}
