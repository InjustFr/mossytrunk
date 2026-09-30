<script setup>
import { computed, ref, watch } from 'vue';
import { ChevronLeft, Plus } from '@lucide/vue';
import BaseButton from '../ui/BaseButton.vue';
import BaseModal from '../ui/BaseModal.vue';
import EmptyState from '../ui/EmptyState.vue';
import ProductTypeForm from './ProductTypeForm.vue';
import ProductTypeList from './ProductTypeList.vue';
import { useProductTypes } from '../../composables/useProductTypes.js';
import { nextTypeColor } from '../../composables/useTypeColor.js';

const emit = defineEmits(['saved']);
const open = defineModel('open', { type: Boolean, required: true });
const { types, create, update } = useProductTypes();

const NEW = 'new';
const editing = ref(null);

watch(open, (isOpen) => {
    if (isOpen) editing.value = null;
});

const title = computed(() => {
    if (!editing.value) return 'Types de produit';
    return editing.value === NEW ? 'Nouveau type de produit' : `Modifier le type ${editing.value.name}`;
});

const submit = (payload) => (editing.value === NEW ? create(payload.name, payload.color, payload.code) : update(editing.value.id, payload));

function onSaved(name) {
    emit('saved', name, editing.value === NEW);
    editing.value = null;
}
</script>

<template>
    <BaseModal v-model:open="open" :title="title">
        <Transition name="product-types-modal__step" mode="out-in">
            <div v-if="!editing" key="list" class="product-types-modal__step">
                <p class="product-types-modal__intro">Chaque produit s'affiche « Type Nom » ; la couleur repère le type dans les listes et les rapports.</p>
                <ProductTypeList v-if="types.length" :types="types" @edit="editing = $event" />
                <EmptyState v-else>Aucun type. Ajoutez Print, Sticker… pour classer vos produits.</EmptyState>
                <div class="product-types-modal__actions">
                    <BaseButton variant="secondary" @click="editing = NEW"><Plus size="1rem" aria-hidden="true" /> Ajouter un type</BaseButton>
                </div>
            </div>
            <div v-else :key="editing === NEW ? NEW : editing.id" class="product-types-modal__step">
                <button type="button" class="product-types-modal__back" @click="editing = null">
                    <ChevronLeft size="1rem" aria-hidden="true" /> Tous les types
                </button>
                <ProductTypeForm
                    :type="editing === NEW ? null : editing"
                    :default-color="nextTypeColor(types)"
                    :submit="submit"
                    @saved="onSaved"
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
