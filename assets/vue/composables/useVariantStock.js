export function variantStock(product, variants) {
    if (variants.length === 0) {
        return { entries: product.stock, onHand: product.onHand, low: product.lowStock, negative: product.negativeStock };
    }
    const entries = product.stock.filter((entry) => variants.includes(entry.variant));
    return {
        entries,
        onHand: entries.reduce((total, entry) => total + entry.onHand, 0),
        low: entries.some((entry) => entry.low),
        negative: entries.some((entry) => entry.negative),
    };
}

export const sameVariant = (a, b) => a.trim().toLowerCase() === b.trim().toLowerCase();
