<script setup>
import { computed, ref } from 'vue';
import { Plus } from '@lucide/vue';
import { ToggleGroupItem, ToggleGroupRoot } from 'reka-ui';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    options: { type: Array, required: true },
    inputLabel: { type: String, default: null },
});
const selected = defineModel({ type: Array, required: true });
const { t } = useI18n();

const added = ref([]);
const draft = ref('');

const same = (a, b) => a.trim().toLowerCase() === b.trim().toLowerCase();
const known = (list, label) => list.some((existing) => same(existing, label));

const choices = computed(() => {
    const all = [...props.options];
    for (const label of [...added.value, ...selected.value]) {
        if (!known(all, label)) all.push(label);
    }
    return all;
});

const ordered = computed({
    get: () => selected.value,
    set: (labels) => {
        selected.value = choices.value.filter((choice) => labels.includes(choice));
    },
});

function add() {
    const label = draft.value.trim();
    draft.value = '';
    if (label === '') return;
    const existing = choices.value.find((choice) => same(choice, label));
    if (!existing) added.value = [...added.value, label];
    const chosen = existing ?? label;
    if (!selected.value.includes(chosen)) ordered.value = [...selected.value, chosen];
}
</script>

<template>
    <div class="variant-picker">
        <ToggleGroupRoot v-if="choices.length" v-model="ordered" type="multiple" class="variant-picker__chips" :aria-label="t('products.variantPicker.label')">
            <ToggleGroupItem v-for="choice in choices" :key="choice" :value="choice" class="variant-picker__chip">{{ choice }}</ToggleGroupItem>
        </ToggleGroupRoot>
        <div class="variant-picker__new">
            <input
                v-model="draft"
                type="text"
                maxlength="100"
                class="variant-picker__field"
                :placeholder="t('products.variantPicker.placeholder')"
                :aria-label="inputLabel ?? t('products.variantPicker.newVariant')"
                @keydown.enter.prevent="add"
                @blur="add"
            >
            <button type="button" class="variant-picker__add" :aria-label="t('products.variantPicker.add')" @click="add"><Plus size="0.875rem" aria-hidden="true" /></button>
        </div>
    </div>
</template>

<style scoped>
.variant-picker { display: flex; flex-direction: column; gap: var(--space-2); }
.variant-picker__chips { display: flex; flex-wrap: wrap; gap: var(--space-2); }

.variant-picker__chip {
    padding: var(--space-1) var(--space-3);
    border: 0.0625rem solid var(--color-border-strong);
    border-radius: 62.4375rem;
    background: var(--color-surface);
    cursor: pointer;
    font-size: 0.85rem;
    transition: background var(--transition), color var(--transition), border-color var(--transition);
}

.variant-picker__chip:hover { border-color: var(--color-ink); }
.variant-picker__chip:focus-visible { outline: 0.125rem solid var(--color-accent); outline-offset: 0.125rem; }
.variant-picker__chip[data-state="on"] { background: var(--color-accent-soft); border-color: var(--color-accent); color: var(--color-accent-strong); }

.variant-picker__new { display: flex; gap: var(--space-2); align-items: center; }
.variant-picker .variant-picker__field { flex: 1; }

.variant-picker__add {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.375rem;
    height: 2.375rem;
    border: 0.0625rem solid var(--color-border-strong);
    border-radius: var(--radius);
    background: var(--color-surface);
    color: var(--color-muted);
    cursor: pointer;
}

.variant-picker__add:hover { color: var(--color-ink); border-color: var(--color-ink); }
</style>
