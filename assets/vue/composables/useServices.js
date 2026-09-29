import { computed, ref } from 'vue';
import { useApi } from './useApi.js';

export const SALES_CONTEXTS = [
    { value: 'at_event', label: 'Au marché du jour', description: 'Chaque vente rejoint le marché ou salon qui couvre sa date.' },
    { value: 'online', label: 'En ligne', description: 'Les ventes n\'appartiennent à aucun marché.' },
];

export const UNKNOWN_ITEMS = [
    { value: 'create_product', label: 'Créer le produit', description: 'Au prix de vente du service, prix d\'achat à compléter.' },
    { value: 'link_by_hand', label: 'Me demander', description: 'La commande attend que vous associez l\'article à un produit.' },
];

export function isReady(service) {
    const connection = service.connection;
    return Boolean(connection?.configured && (!service.authorizes || connection.authorized));
}

export function useServices() {
    const api = useApi();
    const services = ref([]);

    const added = computed(() => services.value.filter((service) => service.connection));
    const ready = computed(() => added.value.filter(isReady));

    async function load() {
        services.value = await api.get('/api/services');
    }

    const add = (service, payload) => api.post('/api/services', { service, ...payload });
    const update = (service, payload) => api.put(`/api/services/${service}`, payload);
    const remove = (service) => api.del(`/api/services/${service}`);
    const disconnect = (service) => api.del(`/api/services/${service}/authorization`);

    return { services, added, ready, load, add, update, remove, disconnect };
}
