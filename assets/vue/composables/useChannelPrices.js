export const UNCHANGED = '__unchanged__';

export const CHANNEL_PRICE_MODES = { fixed: 'fixed', derived: 'derived', sellingPrice: 'selling_price' };
export const ADJUSTMENT_UNITS = { cents: 'cents', percent: 'percent' };

export const ownPriceOn = (product, channel) => (channel.main ? null : product.channelPrices?.[channel.id] ?? null);

export const priceOn = (product, channel) => (channel ? ownPriceOn(product, channel) ?? product.sellingPrice : product.sellingPrice);

export const mainChannelOf = (channels) => channels.find((channel) => channel.main) ?? null;
export const otherChannelsOf = (channels) => channels.filter((channel) => !channel.main);

export const emptyChannelPriceChange = () => ({
    target: UNCHANGED,
    mode: CHANNEL_PRICE_MODES.fixed,
    price: null,
    source: null,
    adjustment: 0,
    unit: ADJUSTMENT_UNITS.percent,
});

const roundHalfAwayFromZero = (value) => Math.sign(value) * Math.round(Math.abs(value));
const adjustmentOf = (change) => (Number.isFinite(change.adjustment) ? change.adjustment : 0);

export function channelPriceChangePayload(change) {
    if (change.target === UNCHANGED) return null;
    const adjustment = adjustmentOf(change);
    return {
        channelId: change.target,
        mode: change.mode,
        price: change.mode === CHANNEL_PRICE_MODES.fixed ? change.price : null,
        sourceChannelId: change.mode === CHANNEL_PRICE_MODES.derived ? change.source : null,
        adjustment: change.unit === ADJUSTMENT_UNITS.cents ? Math.round(adjustment * 100) : adjustment,
        adjustmentUnit: change.unit,
    };
}

export function previewChannelPrice(product, change, source) {
    if (change.mode === CHANNEL_PRICE_MODES.sellingPrice) return product.sellingPrice;
    if (change.mode === CHANNEL_PRICE_MODES.fixed) return change.price;
    const base = priceOn(product, source);
    const adjustment = adjustmentOf(change);
    return change.unit === ADJUSTMENT_UNITS.percent
        ? base + roundHalfAwayFromZero((base * Math.round(adjustment * 100)) / 10_000)
        : base + Math.round(adjustment * 100);
}
