<script setup>
import { Archive, ArchiveRestore, Pencil, Trash2 } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import ConfirmButton from '../ui/ConfirmButton.vue';
import IconButton from '../ui/IconButton.vue';
import TypeMark from '../ui/TypeMark.vue';

defineProps({
    types: { type: Array, required: true },
});
const emit = defineEmits(['edit', 'archive', 'restore', 'remove']);
const { t } = useI18n();

const activeVariants = (type) => type.variants.filter((variant) => !type.archivedVariants.includes(variant));
</script>

<template>
    <ul class="product-type-list">
        <li v-for="type in types" :key="type.id" :class="['product-type-list__row', { 'product-type-list__row--archived': type.archived }]">
            <TypeMark :color="type.color" />
            <span class="product-type-list__name">{{ type.name }}</span>
            <span class="product-type-list__code">{{ type.code }}</span>
            <span class="product-type-list__variants">{{ activeVariants(type).join(' · ') }}</span>
            <span class="product-type-list__actions">
                <template v-if="type.archived">
                    <IconButton :icon="ArchiveRestore" :label="t('products.types.restore', { name: type.name })" @click="emit('restore', type)" />
                </template>
                <template v-else>
                    <IconButton :icon="Pencil" :label="t('products.types.edit', { name: type.name })" @click="emit('edit', type)" />
                    <IconButton :icon="Archive" :label="t('products.types.archive', { name: type.name })" @click="emit('archive', type)" />
                </template>
                <ConfirmButton
                    :icon="Trash2"
                    :label="t('products.types.remove', { name: type.name })"
                    :message="t('products.types.removeMessage')"
                    @confirm="emit('remove', type)"
                />
            </span>
        </li>
    </ul>
</template>

<style scoped>
.product-type-list { margin: 0; padding: 0; list-style: none; }

.product-type-list__row {
    display: grid;
    grid-template-columns: auto auto auto 1fr auto;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-2) 0;
    border-top: 0.0625rem solid var(--color-border);
}

.product-type-list__row:first-child { border-top: none; }
.product-type-list__row--archived .product-type-list__name,
.product-type-list__row--archived .product-type-list__variants { color: var(--color-muted); }
.product-type-list__name { color: var(--color-ink); font-weight: 500; }

.product-type-list__code {
    color: var(--color-muted);
    font-size: var(--font-size-xs);
    letter-spacing: var(--tracking-caps);
    text-transform: uppercase;
}

.product-type-list__variants {
    overflow: hidden;
    color: var(--color-muted);
    font-size: var(--font-size-sm);
    text-overflow: ellipsis;
    white-space: nowrap;
}

.product-type-list__actions { display: flex; align-items: center; gap: var(--space-1); }
</style>
