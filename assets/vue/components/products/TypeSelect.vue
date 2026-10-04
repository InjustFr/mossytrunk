<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import BaseSelect from '../ui/BaseSelect.vue';
import FieldError from '../ui/FieldError.vue';
import TypeColorPicker from './TypeColorPicker.vue';
import { useProductTypes } from '../../composables/useProductTypes.js';
import { nextTypeColor } from '../../composables/useTypeColor.js';

const props = defineProps({
    allowNone: { type: Boolean, default: false },
});
const typeId = defineModel({ type: String, default: '' });
const { types, create } = useProductTypes();
const { t } = useI18n();

const CREATE = '__create__';
const selected = ref(typeId.value);
const creating = ref(false);
const newName = ref('');
const newColor = ref('');
const error = ref(null);
const saving = ref(false);
const input = ref(null);

const options = computed(() => [
    ...(props.allowNone ? [{ value: '', label: t('products.types.none') }] : []),
    ...types.value
        .filter((type) => !type.archived || type.id === typeId.value)
        .map((type) => ({ value: type.id, label: type.archived ? t('products.types.archivedOption', { name: type.name }) : type.name })),
    { value: CREATE, label: t('products.types.createOption') },
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
        error.value = t('products.types.nameRequired');
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
        <BaseSelect v-if="!creating" v-model="selected" :options="options" :placeholder="t('products.types.choose')" :aria-label="t('products.types.select')" />
        <div v-else class="type-select__create">
            <input
                ref="input"
                v-model="newName"
                type="text"
                :placeholder="t('products.types.nameExample')"
                :aria-label="t('products.types.newName')"
                @keydown.enter.prevent="confirm"
                @keydown.esc.prevent.stop="cancel"
            >
            <TypeColorPicker v-model="newColor" />
            <div class="type-select__actions">
                <BaseButton variant="ghost" @click="cancel">{{ t('products.cancel') }}</BaseButton>
                <BaseButton variant="secondary" :loading="saving" @click="confirm">{{ t('products.types.createShort') }}</BaseButton>
            </div>
        </div>
        <FieldError v-if="error">{{ error }}</FieldError>
    </div>
</template>

<style scoped>
.type-select { display: flex; flex-direction: column; gap: var(--space-1); }
.type-select__create { display: flex; flex-direction: column; gap: var(--space-3); padding: var(--space-3); border-radius: var(--radius); background: var(--color-bg); }
.type-select__actions { display: flex; justify-content: flex-end; gap: var(--space-2); }
</style>
