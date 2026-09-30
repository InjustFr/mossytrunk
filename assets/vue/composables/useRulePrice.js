import { t } from '../i18n/index.js';
import { formatCents } from './useMoney.js';
import { intlLocale } from '../i18n/locale.js';

const matching = (products, condition) => products
    .filter((product) => (condition.kind === 'type' ? product.typeId === condition.id : product.id === condition.id))
    .map((product) => product.sellingPrice);

export function regularPrice(products, conditions) {
    let min = 0;
    let max = 0;
    for (const condition of conditions) {
        const prices = matching(products, condition);
        if (!condition.id || prices.length === 0 || !condition.quantity) {
            return null;
        }
        min += Math.min(...prices) * condition.quantity;
        max += Math.max(...prices) * condition.quantity;
    }
    return conditions.length ? { min, max } : null;
}

export function savingOn(regular, action) {
    if (!action.value) {
        return 0;
    }
    if (action.kind === 'fixedPrice') {
        return Math.max(0, regular - action.value);
    }
    if (action.kind === 'amountOff') {
        return Math.min(action.value, regular);
    }
    return Math.round((regular * action.value) / 10000);
}

export function describeAction(action) {
    if (action.kind === 'fixedPrice') {
        return t('discounts.action.fixedPrice', { price: formatCents(action.value) });
    }
    if (action.kind === 'amountOff') {
        return `−${formatCents(action.value).replace('−', '')}`;
    }
    return t('discounts.action.percentOff', { percent: (action.value / 100).toLocaleString(intlLocale()) });
}
