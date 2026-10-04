import { t } from '../i18n/index.js';
import { useApi } from './useApi.js';

const LOT_ORIGINS = {
    purchase: 'stock.origin.purchase',
    supplier_order: 'stock.origin.supplierOrder',
    correction: 'stock.origin.correction',
    return: 'stock.origin.return',
};

const MOVEMENTS = {
    ...LOT_ORIGINS,
    correction: 'stock.movement.surplus',
    sale: 'stock.movement.sale',
    loss: 'stock.movement.loss',
    supply: 'stock.movement.supply',
};

export const lotOriginLabel = (origin) => t(LOT_ORIGINS[origin]);

export const movementLabel = (kind) => t(MOVEMENTS[kind]);

export function useStock() {
    const api = useApi();

    return {
        restock: (payload) => api.post('/api/stock/restock', payload),
        productStock: (productId, target) => api.load(`/api/products/${productId}/stock`, target),
        stockSheet: (eventId, target) => api.load(`/api/events/${eventId}/stock-sheet`, target),
        stockChecks: (eventId, target) => api.load(`/api/events/${eventId}/stock-checks`, target),
        takeStockCheck: (eventId, items) => api.post(`/api/events/${eventId}/stock-checks`, { items }),
        dismiss: (checkId, lineId) => api.post(`/api/stock-checks/${checkId}/lines/${lineId}/dismissal`),
    };
}
