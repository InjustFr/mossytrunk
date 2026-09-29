import { computed, ref } from 'vue';
import { useApi } from './useApi.js';
import { useToast } from './useToast.js';
import { formatDate } from './useDate.js';
import { plural } from './usePlural.js';

export function useImport(service) {
    const api = useApi();
    const toast = useToast();
    const importing = ref(false);
    const problem = ref(null);
    const items = ref([]);

    const unlinked = computed(() => items.value.filter((item) => !item.linkedTo));

    async function loadItems() {
        items.value = await api.get(`/api/services/${service.key}/items`);
    }

    async function run() {
        importing.value = true;
        problem.value = null;
        try {
            const report = await api.post(`/api/services/${service.key}/import`);
            toast.success(summary(report));
            problem.value = describeProblem(report);
            await loadItems();
            return report;
        } catch (error) {
            problem.value = { message: error.message, dates: [] };
            return null;
        } finally {
            importing.value = false;
        }
    }

    const link = (itemId, productId, variant) => api.put(`/api/services/${service.key}/items/${itemId}`, { productId, variant: variant || null });

    return { service, importing, problem, items, unlinked, loadItems, run, link, dismiss: () => { problem.value = null; } };
}

function summary(report) {
    const parts = [
        plural(report.ordersImported, 'commande importée', 'commandes importées'),
        plural(report.ordersAlreadyImported, 'déjà importée', 'déjà importées'),
    ];
    if (report.productsCreated > 0) {
        parts.push(plural(report.productsCreated, 'produit créé', 'produits créés'));
    }
    if (report.typesCreated > 0) {
        parts.push(plural(report.typesCreated, 'type créé', 'types créés'));
    }
    if (report.ordersWaitingForItems > 0) {
        parts.push(`${plural(report.ordersWaitingForItems, 'commande en attente', 'commandes en attente')} d'association`);
    }
    return `Import ${report.label} terminé : ${parts.join(', ')}.`;
}

function describeProblem(report) {
    const parts = [];
    if (report.ordersWithoutEvent > 0) {
        parts.push(`${plural(report.ordersWithoutEvent, 'commande non importée', 'commandes non importées')} : aucun marché ne couvre leur date. Créez le marché puis relancez l'import.`);
    }
    if (report.salesWithoutItems > 0) {
        parts.push(`${plural(report.salesWithoutItems, 'paiement ignoré', 'paiements ignorés')} : aucun article.`);
    }
    if (parts.length === 0) {
        return null;
    }
    return { message: parts.join(' '), dates: report.datesWithoutEvent.map(formatDate) };
}
