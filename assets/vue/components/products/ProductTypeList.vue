<script setup>
import { Pencil } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import IconButton from '../ui/IconButton.vue';
import TypeMark from '../ui/TypeMark.vue';

defineProps({
    types: { type: Array, required: true },
});
const emit = defineEmits(['edit']);
const { t } = useI18n();
</script>

<template>
    <ul class="product-type-list">
        <li v-for="type in types" :key="type.id" class="product-type-list__row">
            <TypeMark :color="type.color" />
            <span class="product-type-list__name">{{ type.name }}</span>
            <span class="product-type-list__code">{{ type.code }}</span>
            <span class="product-type-list__variants">{{ type.variants.join(' · ') }}</span>
            <IconButton :icon="Pencil" :label="t('products.types.edit', { name: type.name })" @click="emit('edit', type)" />
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
.product-type-list__name { color: var(--color-ink); font-weight: 500; }

.product-type-list__code {
    color: var(--color-muted);
    font-size: 0.75rem;
    letter-spacing: 0.06rem;
    text-transform: uppercase;
}

.product-type-list__variants {
    overflow: hidden;
    color: var(--color-muted);
    font-size: 0.8rem;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>
