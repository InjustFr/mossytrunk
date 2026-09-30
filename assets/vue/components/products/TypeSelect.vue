<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import BaseButton from '../ui/BaseButton.vue';
import BaseSelect from '../ui/BaseSelect.vue';
import TypeColorPicker from './TypeColorPicker.vue';
import { useProductTypes } from '../../composables/useProductTypes.js';
import { nextTypeColor } from '../../composables/useTypeColor.js';

const typeId = defineModel({ type: String, default: '' });
const { types, create } = useProductTypes();

const CREATE = '__create__';
const selected = ref(typeId.value);
const creating = ref(false);
const newName = ref('');
const newColor = ref('');
const error = ref(null);
const saving = ref(false);
const input = ref(null);

const options = computed(() => [
    { value: '', label: 'Sans type' },
    ...types.value.map((type) => ({ value: type.id, label: type.name })),
    { value: CREATE, label: '＋ Créer un type…' },
]);

watch(typeId, (value) => { selected.value = value; });

watch(selected, async (value) => {
    if (value === CREATE) {
        creating.value = true;
        newColor.value = nextTypeColor(types.value);
        await nextTick();
        input.value?.focus();
        return;
    }
    typeId.value = value;
});

async function confirm() {
    if (newName.value.trim() === '') {
        error.value = 'Le nom du type est obligatoire.';
        return;
    }
    saving.value = true;
    error.value = null;
    try {
        const type = await create(newName.value, newColor.value);
        typeId.value = type.id;
        selected.value = type.id;
        creating.value = false;
        newName.value = '';
    } catch (e) {
        error.value = e.fieldErrors?.name ?? e.message;
    } finally {
        saving.value = false;
    }
}

function cancel() {
    creating.value = false;
    newName.value = '';
    error.value = null;
    selected.value = typeId.value;
}
</script>

<template>
    <div class="type-select">
        <BaseSelect v-if="!creating" v-model="selected" :options="options" aria-label="Type" />
        <div v-else class="type-select__create">
            <div class="type-select__name">
                <input
                    ref="input"
                    v-model="newName"
                    type="text"
                    placeholder="Ex. Print, Sticker…"
                    aria-label="Nom du nouveau type"
                    @keydown.enter.prevent="confirm"
                    @keydown.esc.prevent.stop="cancel"
                >
                <BaseButton variant="ghost" @click="cancel">Annuler</BaseButton>
                <BaseButton variant="secondary" :loading="saving" @click="confirm">Créer</BaseButton>
            </div>
            <TypeColorPicker v-model="newColor" />
        </div>
        <span v-if="error" class="type-select__error" role="alert">{{ error }}</span>
    </div>
</template>

<style scoped>
.type-select { display: flex; flex-direction: column; gap: var(--space-1); }
.type-select__create { display: flex; flex-direction: column; gap: var(--space-2); }
.type-select__name { display: flex; gap: var(--space-2); align-items: center; }
.type-select__name input { flex: 1; }
.type-select__error { color: var(--color-danger); font-size: 0.85rem; }
</style>
