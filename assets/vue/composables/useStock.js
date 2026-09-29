import { useApi } from './useApi.js';

export const LOW_STOCK_PARAM = 'stock';

export const LOT_ORIGINS = {
    purchase: 'Achat',
    supplier_order: 'Commande fournisseur',
    correction: 'Inventaire',
    return: 'Retour de commande',
};

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
