<script setup>
import { TriangleAlert } from '@lucide/vue';
import ConfirmButton from '../ui/ConfirmButton.vue';

const emit = defineEmits(['delete-orders', 'delete-products']);
</script>

<template>
    <div class="delete-all-data">
        <p class="delete-all-data__warning">
            <TriangleAlert size="1rem" aria-hidden="true" />
            Ces suppressions sont définitives : aucune annulation possible.
        </p>
        <div class="delete-all-data__row">
            <div>
                <h3 class="delete-all-data__title">Toutes les commandes</h3>
                <p class="delete-all-data__detail">Le chiffre d'affaires et les marges des événements repartent de zéro. Les articles vendus reviennent en stock. Un import SumUp pourra ramener les ventes SumUp.</p>
            </div>
            <ConfirmButton
                label="Supprimer toutes les commandes"
                confirm-label="Tout supprimer"
                message="Toutes les commandes de l'espace de travail seront supprimées définitivement."
                variant="danger"
                @confirm="emit('delete-orders')"
            />
        </div>
        <div class="delete-all-data__row">
            <div>
                <h3 class="delete-all-data__title">Tous les produits</h3>
                <p class="delete-all-data__detail">Les commandes passées gardent leurs lignes. Le stock des produits est supprimé avec eux. Les remises par lot qui ne listent que des produits sont supprimées aussi.</p>
            </div>
            <ConfirmButton
                label="Supprimer tous les produits"
                confirm-label="Tout supprimer"
                message="Tout le catalogue et les remises par lot qui ne listent que des produits seront supprimés définitivement."
                variant="danger"
                @confirm="emit('delete-products')"
            />
        </div>
    </div>
</template>

<style scoped>
.delete-all-data { display: flex; flex-direction: column; gap: var(--space-4); }
.delete-all-data__warning {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    margin: 0;
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius);
    background: var(--color-danger-soft);
    color: var(--color-danger);
}
.delete-all-data__row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: var(--space-3);
    padding-top: var(--space-4);
    border-top: 0.0625rem solid var(--color-border);
}
.delete-all-data__title { margin: 0; }
.delete-all-data__detail { margin: var(--space-1) 0 0; max-width: 36rem; color: var(--color-muted); font-size: 0.9rem; }
</style>
