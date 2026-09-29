<script setup>
import { Pencil, Trash2 } from '@lucide/vue';
import BaseButton from '../ui/BaseButton.vue';
import ConfirmButton from '../ui/ConfirmButton.vue';
import IconButton from '../ui/IconButton.vue';
import StatusBadge from '../ui/StatusBadge.vue';
import { SALES_CONTEXTS, UNKNOWN_ITEMS } from '../../composables/useServices.js';
import { plural } from '../../composables/usePlural.js';

defineProps({
    services: { type: Array, required: true },
});
const emit = defineEmits(['edit', 'remove', 'disconnect']);

const labelOf = (options, value) => options.find((option) => option.value === value)?.label ?? value;

function status(service) {
    const connection = service.connection;
    if (!connection.configured) return { tone: 'danger', label: 'Accès incomplets' };
    if (!service.authorizes) return { tone: 'success', label: 'Prêt' };
    return connection.authorized ? { tone: 'success', label: 'Connecté' } : { tone: 'warning', label: 'À connecter' };
}
</script>

<template>
    <ul class="connected-services">
        <li v-for="service in services" :key="service.key" class="connected-services__row">
            <span class="connected-services__mark" aria-hidden="true">{{ service.label.slice(0, 2) }}</span>
            <div class="connected-services__body">
                <p class="connected-services__title">
                    <span class="connected-services__name">{{ service.label }}</span>
                    <span v-if="service.connection.accountName" class="connected-services__account">{{ service.connection.accountName }}</span>
                    <StatusBadge :tone="status(service).tone">{{ status(service).label }}</StatusBadge>
                </p>
                <dl class="connected-services__options">
                    <div>
                        <dt>Ventes</dt>
                        <dd>{{ labelOf(SALES_CONTEXTS, service.connection.salesContext) }}</dd>
                    </div>
                    <div>
                        <dt>Article inconnu</dt>
                        <dd>{{ labelOf(UNKNOWN_ITEMS, service.connection.unknownItems) }}</dd>
                    </div>
                </dl>
                <p v-if="service.connection.itemsToLink" class="connected-services__waiting">
                    <a href="/commandes">{{ plural(service.connection.itemsToLink, 'article à associer', 'articles à associer') }}</a>
                </p>
            </div>
            <div class="connected-services__actions">
                <BaseButton
                    v-if="service.authorizes && service.connection.configured && !service.connection.authorized"
                    :href="`/parametres/${service.key}/connexion`"
                    data-turbo="false"
                >
                    Connecter la boutique
                </BaseButton>
                <ConfirmButton
                    v-if="service.connection.authorized"
                    label="Déconnecter"
                    confirm-label="Déconnecter"
                    :message="`MossyTrunk n'aura plus accès à ${service.label}. Les commandes déjà importées restent.`"
                    @confirm="emit('disconnect', service)"
                />
                <IconButton :icon="Pencil" :label="`Modifier ${service.label}`" @click="emit('edit', service)" />
                <ConfirmButton
                    :icon="Trash2"
                    :label="`Retirer ${service.label}`"
                    confirm-label="Retirer"
                    message="Ses accès sont oubliés. Les commandes déjà importées restent."
                    @confirm="emit('remove', service)"
                />
            </div>
        </li>
    </ul>
</template>

<style scoped>
.connected-services { margin: 0; padding: 0; list-style: none; }

.connected-services__row {
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: start;
    gap: var(--space-3) var(--space-4);
    padding: var(--space-4) 0;
    border-top: 0.0625rem solid var(--color-border);
}

.connected-services__row:first-child { border-top: none; padding-top: 0; }

.connected-services__mark {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 50%;
    background: var(--color-accent-soft);
    color: var(--color-accent-strong);
    font-family: var(--font-display);
    font-size: 1rem;
}

.connected-services__body { display: flex; flex-direction: column; gap: var(--space-2); min-width: 0; }
.connected-services__title { display: flex; flex-wrap: wrap; align-items: center; gap: var(--space-2); margin: 0; }
.connected-services__name { font-family: var(--font-display); font-size: 1.125rem; color: var(--color-ink); }
.connected-services__account { color: var(--color-muted); }

.connected-services__options { display: flex; flex-wrap: wrap; gap: var(--space-1) var(--space-5); margin: 0; font-size: 0.875rem; }
.connected-services__options div { display: flex; gap: var(--space-2); }
.connected-services__options dt { color: var(--color-muted); }
.connected-services__options dd { margin: 0; color: var(--color-text); }

.connected-services__waiting { margin: 0; font-size: 0.875rem; }
.connected-services__waiting a { color: var(--color-warning); }

.connected-services__actions { display: flex; flex-wrap: wrap; justify-content: flex-end; align-items: center; gap: var(--space-2); }

@media (max-width: 40rem) {
    .connected-services__row { grid-template-columns: auto 1fr; }
    .connected-services__actions { grid-column: 1 / -1; justify-content: flex-start; }
}
</style>
