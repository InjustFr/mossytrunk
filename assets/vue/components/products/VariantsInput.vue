<script setup>
import { X } from '@lucide/vue';
import { TagsInputInput, TagsInputItem, TagsInputItemDelete, TagsInputItemText, TagsInputRoot } from 'reka-ui';
import { useI18n } from 'vue-i18n';

defineProps({
    inputLabel: { type: String, default: null },
    placeholder: { type: String, default: null },
    itemLabel: { type: String, default: null },
});
const variants = defineModel({ type: Array, required: true });
const { t } = useI18n();
</script>

<template>
    <TagsInputRoot v-model="variants" add-on-blur add-on-paste class="variants-input">
        <TagsInputItem v-for="variant in variants" :key="variant" :value="variant" class="variants-input__chip">
            <TagsInputItemText />
            <TagsInputItemDelete class="variants-input__remove" :aria-label="t('products.variantsInput.remove', { item: itemLabel ?? t('products.variantsInput.itemLabel'), variant })">
                <X size="0.75rem" aria-hidden="true" />
            </TagsInputItemDelete>
        </TagsInputItem>
        <TagsInputInput class="variants-input__field" :placeholder="placeholder ?? t('products.variantsInput.placeholder')" :aria-label="inputLabel ?? t('products.variantsInput.inputLabel')" @keypress.enter.prevent />
    </TagsInputRoot>
</template>

<style scoped>
.variants-input {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--space-1);
    min-height: 2.375rem;
    padding: var(--space-1) var(--space-2);
    border: 0.0625rem solid var(--color-border-strong);
    border-radius: var(--radius);
    background: var(--color-surface);
    transition: border-color var(--transition), box-shadow var(--transition);
}

.variants-input:focus-within {
    border-color: var(--color-accent);
    box-shadow: 0 0 0 0.1875rem var(--color-accent-soft);
}

.variants-input__chip {
    display: inline-flex;
    align-items: center;
    gap: var(--space-1);
    padding: 0.125rem var(--space-2);
    border-radius: 62.4375rem;
    background: var(--color-accent-soft);
    color: var(--color-accent-strong);
    font-size: 0.85rem;
    animation: variants-input-chip-in var(--transition);
}

.variants-input__chip[data-state="active"] { outline: 0.125rem solid var(--color-accent); }

.variants-input__remove {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
    background: none;
    cursor: pointer;
    color: inherit;
    padding: 0;
    line-height: 1;
}

.variants-input .variants-input__field {
    flex: 1;
    min-width: 10rem;
    min-height: auto;
    padding: var(--space-1);
    border: none;
    background: none;
    box-shadow: none;
    outline: none;
}

.variants-input .variants-input__field:focus { border: none; box-shadow: none; }

@keyframes variants-input-chip-in { from { opacity: 0; transform: scale(0.9); } }
</style>
