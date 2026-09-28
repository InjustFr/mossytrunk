import { ref } from 'vue';
import { useApi } from './useApi.js';
import { useToast } from './useToast.js';
import { formatDate } from './useDate.js';
import { plural } from './usePlural.js';

/**
 * Runs the SumUp import. Success goes to a toast; orders that could not be imported are summed up
 * in ONE message (`problem`), whatever their number.
 */
export function useSumUpImport() {
    const api = useApi();
    const toast = useToast();
    const importing = ref(false);
    const problem = ref(null);

    async function run() {
        importing.value = true;
        problem.value = null;
        try {
            const report = await api.post('/api/sumup/import');
            toast.success(
                `Import SumUp terminé : ${plural(report.ordersImported, 'commande importée', 'commandes importées')}, `
                + `${plural(report.productsCreated, 'produit créé', 'produits créés')}, ${plural(report.ordersAlreadyImported, 'déjà importée', 'déjà importées')}`
                + (report.typesCreated > 0 ? `, ${plural(report.typesCreated, 'type créé', 'types créés')}.` : '.'),
            );
            problem.value = describeProblem(report);
            return report;
        } catch (error) {
            problem.value = { message: error.message, dates: [], products: [] };
            return null;
        } finally {
            importing.value = false;
        }
    }

    return { importing, problem, run, dismiss: () => { problem.value = null; } };
}

function describeProblem(report) {
    const parts = [];
    if (report.ordersWithoutEvent > 0) {
        parts.push(`${plural(report.ordersWithoutEvent, 'commande non importée', 'commandes non importées')} : aucun événement ne couvre leur date. Créez l'événement correspondant puis relancez l'import.`);
    }
    if (report.ordersWithUnresolvedProducts > 0) {
        parts.push(`${plural(report.ordersWithUnresolvedProducts, 'commande non importée', 'commandes non importées')} : variante inconnue pour certains produits.`);
    }
    if (parts.length === 0) {
        return null;
    }
    return {
        message: parts.join(' '),
        dates: report.datesWithoutEvent.map(formatDate),
        products: report.unresolvedProducts,
    };
}
