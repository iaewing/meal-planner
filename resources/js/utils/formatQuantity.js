export function formatQuantity(value) {
    const number = Number(value);

    if (Number.isNaN(number)) {
        return value;
    }

    const rounded = Math.round(number * 100) / 100;

    if (Math.abs(rounded - Math.round(rounded)) < 0.001) {
        return String(Math.round(rounded));
    }

    return String(rounded);
}
