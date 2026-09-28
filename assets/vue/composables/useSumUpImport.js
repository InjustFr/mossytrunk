import { ref } from 'vue';
import { useApi } from './useApi.js';
import { useToast } from './useToast.js';
import { formatDate } from './useDate.js';

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
                `Import SumUp terminé : ${report.ordersImported} commande(s) importée(s), `
                + `${report.productsCreated} produit(s) créé(s), ${report.ordersAlreadyImported} déjà importée(s)`
                + (report.typesCreated > 0 ? `, ${report.typesCreated} type(s) créé(s).` : '.'),
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
        parts.push(`${report.ordersWithoutEvent} commande(s) non importée(s) : aucun événement ne couvre leur date. Créez l'événement correspondant puis relancez l'import.`);
    }
    if (report.ordersWithUnresolvedProducts > 0) {
        parts.push(`${report.ordersWithUnresolvedProducts} commande(s) non importée(s) : variante inconnue pour certains produits.`);
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
