<script setup>
import { nextTick, ref } from 'vue';
import { Archive, ArchiveRestore, Check, ChevronDown, ChevronUp, Pencil, Plus, Trash2, X } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import IconButton from '../ui/IconButton.vue';
import StatusBadge from '../ui/StatusBadge.vue';

const props = defineProps({
    rename: { type: Function, required: true },
});
const variants = defineModel({ type: Array, required: true });
const archived = defineModel('archived', { type: Array, default: () => [] });
const { t } = useI18n();

const draft = ref('');
const editing = ref(null);
const newLabel = ref('');
const renaming = ref(false);
const error = ref(null);
const renameInput = ref(null);

const same = (a, b) => a.trim().toLowerCase() === b.trim().toLowerCase();
const taken = (label, except = null) => variants.value.some((variant) => variant !== except && same(variant, label));

function add() {
    const label = draft.value.trim();
    draft.value = '';
    error.value = null;
    if (label === '') return;
    if (taken(label)) {
        error.value = t('products.types.variants.duplicate', { variant: label });
        return;
    }
    variants.value = [...variants.value, label];
}

function move(index, offset) {
    const reordered = [...variants.value];
    [reordered[index], reordered[index + offset]] = [reordered[index + offset], reordered[index]];
    variants.value = reordered;
}

const remove = (variant) => {
    variants.value = variants.value.filter((existing) => existing !== variant);
    archived.value = archived.value.filter((existing) => existing !== variant);
};

const isArchived = (variant) => archived.value.includes(variant);
const toggleArchived = (variant) => {
    archived.value = isArchived(variant) ? archived.value.filter((existing) => existing !== variant) : [...archived.value, variant];
};

async function startRenaming(variant) {
    editing.value = variant;
    newLabel.value = variant;
    error.value = null;
    await nextTick();
    renameInput.value?.[0]?.focus();
}

function stopRenaming() {
    editing.value = null;
    error.value = null;
}

async function confirmRenaming() {
    const from = editing.value;
    const to = newLabel.value.trim();
    if (to === '' || to === from) {
        stopRenaming();
        return;
    }
    if (taken(to, from)) {
        error.value = t('products.types.variants.duplicate', { variant: to });
        return;
    }
    renaming.value = true;
    try {
        await props.rename(from, to);
        variants.value = variants.value.map((variant) => (variant === from ? to : variant));
        archived.value = archived.value.map((variant) => (variant === from ? to : variant));
        stopRenaming();
    } catch (e) {
        error.value = e.message;
    } finally {
        renaming.value = false;
    }
}
</script>

<template>
    <div class="type-variants">
        <ol v-if="variants.length" class="type-variants__list">
            <li v-for="(variant, index) in variants" :key="variant" :class="['type-variants__row', { 'type-variants__row--archived': isArchived(variant) }]">
                <template v-if="editing === variant">
                    <input
                        ref="renameInput"
                        v-model="newLabel"
                        type="text"
                        maxlength="100"
                        class="type-variants__rename"
                        :aria-label="t('products.types.variants.newLabel', { variant })"
                        :disabled="renaming"
                        @keydown.enter.prevent="confirmRenaming"
                        @keydown.esc.prevent.stop="stopRenaming"
                    >
                    <IconButton :icon="Check" :label="t('products.types.variants.confirmRename', { variant })" :disabled="renaming" @click="confirmRenaming" />
                    <IconButton :icon="X" :label="t('products.cancel')" :disabled="renaming" @click="stopRenaming" />
                </template>
                <template v-else>
                    <span class="type-variants__label">{{ variant }} <StatusBadge v-if="isArchived(variant)">{{ t('products.types.variants.archived') }}</StatusBadge></span>
                    <IconButton :icon="ChevronUp" :label="t('products.types.variants.moveUp', { variant })" :disabled="index === 0" @click="move(index, -1)" />
                    <IconButton :icon="ChevronDown" :label="t('products.types.variants.moveDown', { variant })" :disabled="index === variants.length - 1" @click="move(index, 1)" />
                    <IconButton :icon="Pencil" :label="t('products.types.variants.rename', { variant })" @click="startRenaming(variant)" />
                    <IconButton
                        :icon="isArchived(variant) ? ArchiveRestore : Archive"
                        :label="t(isArchived(variant) ? 'products.types.variants.restore' : 'products.types.variants.archive', { variant })"
                        @click="toggleArchived(variant)"
                    />
                    <IconButton :icon="Trash2" variant="danger" :label="t('products.types.variants.remove', { variant })" @click="remove(variant)" />
                </template>
            </li>
        </ol>
        <div class="type-variants__new">
            <input
                v-model="draft"
                type="text"
                maxlength="100"
                :placeholder="t('products.types.variants.placeholder')"
                :aria-label="t('products.types.variants.newVariant')"
                @keydown.enter.prevent="add"
            >
            <IconButton :icon="Plus" :label="t('products.types.variants.add')" @click="add" />
        </div>
        <span v-if="error" class="type-variants__error" role="alert">{{ error }}</span>
    </div>
</template>

<style scoped>
.type-variants { display: flex; flex-direction: column; gap: var(--space-2); }
.type-variants__list { margin: 0; padding: 0; list-style: none; border: 0.0625rem solid var(--color-border); border-radius: var(--radius); }

.type-variants__row {
    display: flex;
    align-items: center;
    gap: var(--space-1);
    min-height: 2.5rem;
    padding: var(--space-1) var(--space-1) var(--space-1) var(--space-3);
    border-top: 0.0625rem solid var(--color-border);
}

.type-variants__row:first-child { border-top: none; }
.type-variants__label { display: flex; flex: 1; align-items: center; gap: var(--space-2); color: var(--color-ink); }
.type-variants__row--archived .type-variants__label { color: var(--color-muted); }
.type-variants .type-variants__rename { flex: 1; min-height: 2rem; padding: var(--space-1) var(--space-2); }
.type-variants__row :deep(.icon-button:disabled) { opacity: 0.35; cursor: default; pointer-events: none; }
.type-variants__new { display: flex; align-items: center; gap: var(--space-2); }
.type-variants__new input { flex: 1; }
.type-variants__error { color: var(--color-danger); font-size: 0.85rem; }
</style>
