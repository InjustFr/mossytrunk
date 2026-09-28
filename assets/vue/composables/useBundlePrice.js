export function regularBundlePrice(products, productIds, typeIds, bundleSize) {
    const prices = products
        .filter((product) => productIds.includes(product.id) || (product.typeId !== null && typeIds.includes(product.typeId)))
        .map((product) => product.sellingPrice);
    if (prices.length === 0 || !bundleSize) {
        return null;
    }
    return { min: Math.min(...prices) * bundleSize, max: Math.max(...prices) * bundleSize };
}
