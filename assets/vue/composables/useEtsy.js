import { computed, ref } from 'vue';
import { useApi } from './useApi.js';
import { useToast } from './useToast.js';
import { plural } from './usePlural.js';

export function useEtsy() {
    const api = useApi();
    const toast = useToast();
    const importing = ref(false);
    const listings = ref([]);

    const unlinked = computed(() => listings.value.filter((listing) => !listing.linkedTo));

    async function loadListings() {
        listings.value = await api.get('/api/etsy/listings');
    }

    async function run() {
        importing.value = true;
        try {
            const report = await api.post('/api/etsy/import');
            const waiting = report.ordersWaitingForListings > 0 ? `, ${plural(report.ordersWaitingForListings, 'en attente d\'association', 'en attente d\'association')}` : '';
            toast.success(`Import Etsy terminé : ${plural(report.ordersImported, 'commande importée', 'commandes importées')}, ${plural(report.ordersAlreadyImported, 'déjà importée', 'déjà importées')}${waiting}.`);
            await loadListings();
            return report;
        } catch (error) {
            toast.error(error.message);
            return null;
        } finally {
            importing.value = false;
        }
    }

    const link = (listingId, productId, variant) => api.put(`/api/etsy/listings/${listingId}`, { productId, variant: variant || null });

    return { importing, listings, unlinked, loadListings, run, link };
}
