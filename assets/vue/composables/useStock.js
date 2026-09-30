import { t } from '../i18n/index.js';
import { useApi } from './useApi.js';

export const LOW_STOCK_PARAM = 'stock';

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
};

export const lotOriginLabel = (origin) => t(LOT_ORIGINS[origin]);

export const movementLabel = (kind) => t(MOVEMENTS[kind]);

export function useStock() {
    const api = useApi();

    return {
        restock: (payload) => api.post('/api/stock/restock', payload),
        productStock: (productId) => api.get(`/api/products/${productId}/stock`),
        stockSheet: (eventId) => api.get(`/api/events/${eventId}/stock-sheet`),
        stockChecks: (eventId) => api.get(`/api/events/${eventId}/stock-checks`),
        takeStockCheck: (eventId, items) => api.post(`/api/events/${eventId}/stock-checks`, { items }),
        dismiss: (checkId, lineId) => api.post(`/api/stock-checks/${checkId}/lines/${lineId}/dismissal`),
    };
}
