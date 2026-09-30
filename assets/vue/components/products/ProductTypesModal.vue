<script setup>
import { computed, ref, watch } from 'vue';
import { ChevronLeft, Plus } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import BaseModal from '../ui/BaseModal.vue';
import EmptyState from '../ui/EmptyState.vue';
import ProductTypeForm from './ProductTypeForm.vue';
import ProductTypeList from './ProductTypeList.vue';
import { useProductTypes } from '../../composables/useProductTypes.js';
import { nextTypeColor } from '../../composables/useTypeColor.js';
import { useToast } from '../../composables/useToast.js';

const emit = defineEmits(['saved', 'renamed', 'changed']);
const open = defineModel('open', { type: Boolean, required: true });
const { types, create, update, archive, restore, remove } = useProductTypes();
const { t } = useI18n();
const toast = useToast();

const activeTypes = computed(() => types.value.filter((type) => !type.archived));
const archivedTypes = computed(() => types.value.filter((type) => type.archived));

async function change(action, type, message) {
    try {
        await action(type.id);
        toast.success(t(message, { name: type.name }));
        emit('changed');
    } catch (error) {
        toast.error(error.message);
    }
}

const onArchive = (type) => change(archive, type, 'products.toast.typeArchived');
const onRestore = (type) => change(restore, type, 'products.toast.typeRestored');
const onRemove = (type) => change(remove, type, 'products.toast.typeRemoved');

const NEW = 'new';
const editing = ref(null);

watch(open, (isOpen) => {
    if (isOpen) editing.value = null;
});

const title = computed(() => {
    if (!editing.value) return t('products.page.types');
    return editing.value === NEW ? t('products.types.new') : t('products.types.edit', { name: editing.value.name });
});

const submit = (payload) => (editing.value === NEW ? create(payload.name, payload.color, payload.code, payload.variants, payload.prefixesNames) : update(editing.value.id, payload));

function onSaved(name) {
    emit('saved', name, editing.value === NEW);
    editing.value = null;
}
</script>

<template>
    <BaseModal v-model:open="open" :title="title">
        <Transition name="product-types-modal__step" mode="out-in">
            <div v-if="!editing" key="list" class="product-types-modal__step">
                <p class="product-types-modal__intro">{{ t('products.types.intro') }}</p>
                <ProductTypeList v-if="activeTypes.length" :types="activeTypes" @edit="editing = $event" @archive="onArchive" @remove="onRemove" />
                <EmptyState v-else>{{ t('products.types.empty') }}</EmptyState>
                <div class="product-types-modal__actions">
                    <BaseButton variant="secondary" @click="editing = NEW"><Plus size="1rem" aria-hidden="true" /> {{ t('products.types.add') }}</BaseButton>
                </div>
                <section v-if="archivedTypes.length" class="product-types-modal__archived" aria-labelledby="archived-types-title">
                    <h3 id="archived-types-title" class="product-types-modal__archived-title">{{ t('products.types.archivedTitle') }}</h3>
                    <p class="product-types-modal__intro">{{ t('products.types.archivedHint') }}</p>
                    <ProductTypeList :types="archivedTypes" @restore="onRestore" @remove="onRemove" />
                </section>
            </div>
            <div v-else :key="editing === NEW ? NEW : editing.id" class="product-types-modal__step">
                <button type="button" class="product-types-modal__back" @click="editing = null">
                    <ChevronLeft size="1rem" aria-hidden="true" /> {{ t('products.types.all') }}
                </button>
                <ProductTypeForm
                    :type="editing === NEW ? null : editing"
                    :default-color="nextTypeColor(types)"
                    :submit="submit"
                    @saved="onSaved"
                    @renamed="emit('renamed')"
                    @cancel="editing = null"
                />
            </div>
        </Transition>
    </BaseModal>
</template>

<style>
.product-types-modal__step { display: flex; flex-direction: column; gap: var(--space-4); }
.product-types-modal__intro { margin: 0; color: var(--color-muted); font-size: 0.9rem; }
.product-types-modal__actions { display: flex; justify-content: flex-end; }
.product-types-modal__archived { display: flex; flex-direction: column; gap: var(--space-2); padding-top: var(--space-4); border-top: 0.0625rem solid var(--color-border); }
.product-types-modal__archived-title { margin: 0; color: var(--color-muted); font-family: var(--font-body); font-size: 0.75rem; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; }

.product-types-modal__back {
    display: inline-flex;
    align-self: flex-start;
    align-items: center;
    gap: var(--space-1);
    padding: 0;
    border: none;
    background: none;
    color: var(--color-muted);
    font: inherit;
    font-size: 0.875rem;
    cursor: pointer;
}

.product-types-modal__back:hover { color: var(--color-ink); }

.product-types-modal__step-enter-active,
.product-types-modal__step-leave-active { transition: opacity var(--transition), transform var(--transition); }
.product-types-modal__step-enter-from { opacity: 0; transform: translateX(0.5rem); }
.product-types-modal__step-leave-to { opacity: 0; transform: translateX(-0.5rem); }

@media (prefers-reduced-motion: reduce) {
    .product-types-modal__step-enter-active,
    .product-types-modal__step-leave-active { transition: none; }
}
</style>
