<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import BaseSelect from '../ui/BaseSelect.vue';
import { DESIGN_SCOPES } from '../../composables/useDesignFilters.js';

const props = defineProps({
    shelves: { type: Array, required: true },
    total: { type: Number, required: true },
    searching: { type: Boolean, default: false },
});
const scope = defineModel('scope', { type: String, required: true });
const search = defineModel('search', { type: String, required: true });
const { t } = useI18n();

const ALL_OPTION = '__all__';
const nameOf = (shelf) => shelf.collection?.name ?? t('designs.noCollection');
const tally = (shelf) => {
    if (props.searching) return String(shelf.matching.length);
    if (shelf.open > 0) return t('designs.index.inProgress', { count: shelf.open });
    return t(shelf.designs.length > 0 ? 'designs.index.done' : 'designs.index.empty');
};
const tallyHint = (shelf) => t('designs.index.tallyHint', { open: shelf.open, total: shelf.designs.length }, shelf.open);
const options = computed(() => [
    { value: ALL_OPTION, label: t('designs.index.all') },
    ...props.shelves.map((shelf) => ({ value: shelf.key, label: nameOf(shelf) })),
]);
const selectedOption = computed({
    get: () => scope.value || ALL_OPTION,
    set: (value) => { scope.value = value === ALL_OPTION ? DESIGN_SCOPES.all : value; },
});
</script>

<template>
    <nav class="design-index" :aria-label="t('designs.index.label')">
        <input v-model="search" class="control control--compact design-index__search" type="search" :placeholder="t('designs.index.placeholder')" :aria-label="t('designs.index.search')">
        <BaseSelect v-model="selectedOption" :options="options" :aria-label="t('designs.index.label')" class="design-index__select" />
        <ul class="design-index__list">
            <li>
                <button
                    type="button"
                    :class="['design-index__entry', { 'design-index__entry--selected': scope === DESIGN_SCOPES.all }]"
                    :aria-current="scope === DESIGN_SCOPES.all ? 'true' : undefined"
                    @click="scope = DESIGN_SCOPES.all"
                >
                    <span class="design-index__name">{{ t('designs.index.all') }}</span>
                    <span class="design-index__tally">{{ total }}</span>
                </button>
            </li>
            <li v-for="shelf in shelves" :key="shelf.key">
                <button
                    type="button"
                    :class="['design-index__entry', { 'design-index__entry--selected': scope === shelf.key, 'design-index__entry--done': shelf.open === 0 && shelf.designs.length > 0 }]"
                    :aria-current="scope === shelf.key ? 'true' : undefined"
                    @click="scope = shelf.key"
                >
                    <span v-if="shelf.collection?.current" class="design-index__bench" role="img" :aria-label="t('designs.onBench')" />
                    <span :class="['design-index__name', { 'design-index__name--loose': !shelf.collection }]">{{ nameOf(shelf) }}</span>
                    <span class="design-index__tally" :title="tallyHint(shelf)">{{ tally(shelf) }}</span>
                </button>
            </li>
        </ul>
    </nav>
</template>

<style scoped>
.design-index { display: flex; flex-direction: column; gap: var(--space-3); }

.design-index__search { width: 100%; }

.design-index :deep(.design-index__select) { display: none; }
.design-index__list { display: flex; flex-direction: column; margin: 0; padding: 0; list-style: none; }

.design-index__entry {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    width: 100%;
    padding: 0.4375rem var(--space-3);
    border: none;
    border-left: 0.125rem solid transparent;
    background: none;
    color: var(--color-text);
    font: inherit;
    font-size: var(--font-size-md);
    text-align: left;
    cursor: pointer;
    transition: background var(--transition), border-color var(--transition);
}

.design-index__entry:hover { background: var(--color-surface); }
.design-index__entry:focus-visible { outline-offset: -0.125rem; }
.design-index__entry--selected { border-left-color: var(--color-ink); background: var(--color-surface); font-weight: 600; }
.design-index__entry--done { color: var(--color-muted); }
.design-index__bench { flex-shrink: 0; width: 0.4375rem; height: 0.4375rem; border-radius: 50%; background: var(--color-accent); }
.design-index__name { flex: 1 1 auto; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.design-index__name--loose { font-style: italic; }
.design-index__tally { flex-shrink: 0; color: var(--color-muted); font-size: var(--font-size-sm); font-variant-numeric: tabular-nums; font-weight: 400; }

@media (max-width: 56rem) {
    .design-index :deep(.design-index__select) { display: flex; }
    .design-index__list { display: none; }
}
</style>
