// Tas pats aprēķins, kas app/calculation.php. BigInt novērš naudas noapaļošanas kļūdas.
const QUANTITY_PLACES = 2;
const PRICE_PLACES = 4;
const MAXIMUM_QUANTITY = 999999n;
const MAXIMUM_QUANTITY_1800 = 10000000n;
const MAXIMUM_PRICE = 9999999900n;

function scaledDecimal(value, places, maximum) {
    const text = String(value).trim().replace(",", ".");
    const pattern = new RegExp("^\\d+(?:\\.\\d{1," + places + "})?$");

    if (!pattern.test(text)) {
        throw new Error("Ievadi derīgu daudzumu un cenu.");
    }

    const [whole, fraction = ""] = text.split(".");
    const result = BigInt(whole) * 10n ** BigInt(places) + BigInt(fraction.padEnd(places, "0"));

    if (result > maximum) {
        throw new Error("Pārsniegta daudzuma vai cenas robeža.");
    }

    return result;
}

function decimalText(value, places) {
    const scale = 10n ** BigInt(places);
    const whole = value / scale;
    const fraction = String(value % scale).padStart(places, "0");

    return whole + "." + fraction;
}

export function unitDivisor(unit) {
    // Array.from skaita Unicode zīmes, tāpat kā PHP variants.
    const lastTenCharacters = Array.from(unit.trim()).slice(-10);
    const code = Number.parseInt(lastTenCharacters.slice(0, 4).join(""), 10);

    return [1500, 1800].includes(code) ? code : 1;
}

function roundedDivision(numerator, denominator) {
    return (numerator + denominator / 2n) / denominator;
}

export function calculateAmount(input) {
    const divisor = unitDivisor(input.mervien);
    const maximumQuantity = divisor === 1800 ? MAXIMUM_QUANTITY_1800 : MAXIMUM_QUANTITY;
    const quantity = scaledDecimal(input.daudz, QUANTITY_PLACES, maximumQuantity);
    const price = scaledDecimal(input.cena, PRICE_PLACES, MAXIMUM_PRICE);
    const discountText = String(input.atlaide).trim();

    if (!/^\d+$/.test(discountText)) {
        throw new Error("Atlaidei jābūt veselam skaitlim no 0 līdz 100.");
    }

    const discount = Number(discountText);

    if (!Number.isInteger(discount) || discount < 0 || discount > 100) {
        throw new Error("Atlaidei jābūt veselam skaitlim no 0 līdz 100.");
    }

    const numerator = quantity * price * BigInt(100 - discount);
    const baseDenominator = 10n ** BigInt(QUANTITY_PLACES) * 100n;
    let amount;

    if (divisor === 1) {
        amount = roundedDivision(numerator, baseDenominator);
    } else {
        const centDenominator = baseDenominator * BigInt(divisor) * 100n;
        amount = roundedDivision(numerator, centDenominator) * 100n;
    }

    return {
        daudz: decimalText(quantity, QUANTITY_PLACES),
        cena: decimalText(price, PRICE_PLACES),
        atlaide: discount,
        summa: decimalText(amount, PRICE_PLACES),
        divisor,
    };
}
