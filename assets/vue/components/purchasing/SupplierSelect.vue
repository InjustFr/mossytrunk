<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import BaseSelect from '../ui/BaseSelect.vue';
import FieldError from '../ui/FieldError.vue';

const { t } = useI18n();

const props = defineProps({
    suppliers: { type: Array, required: true },
    save: { type: Function, required: true },
});
const supplierId = defineModel({ type: String, default: '' });

const CREATE = '__create__';
const selected = ref(supplierId.value);
const creating = ref(false);
const newName = ref('');
const error = ref(null);
const saving = ref(false);
const input = ref(null);

const options = computed(() => [
    ...props.suppliers.map((supplier) => ({ value: supplier.id, label: supplier.name })),
    { value: CREATE, label: t('purchasing.supplierSelect.new') },
]);

watch(supplierId, (value) => { selected.value = value; });

watch(selected, async (value) => {
    if (value === CREATE) {
        creating.value = true;
        await nextTick();
        input.value?.focus();
        return;
    }
    supplierId.value = value;
});

async function confirm() {
    saving.value = true;
    error.value = null;
    try {
        const supplier = await props.save(null, { name: newName.value });
        supplierId.value = supplier.id;
        selected.value = supplier.id;
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
    selected.value = supplierId.value;
}
</script>

<template>
    <div class="supplier-select">
        <BaseSelect v-if="!creating" v-model="selected" :options="options" :placeholder="t('purchasing.supplierSelect.placeholder')" :aria-label="t('purchasing.supplierSelect.label')" />
        <div v-else class="supplier-select__create">
            <input
                ref="input"
                class="control"
                v-model="newName"
                type="text"
                :placeholder="t('purchasing.supplierSelect.namePlaceholder')"
                :aria-label="t('purchasing.supplierSelect.nameLabel')"
                @keydown.enter.prevent="confirm"
                @keydown.esc.prevent.stop="cancel"
            >
            <div class="actions-row">
                <BaseButton variant="ghost" @click="cancel">{{ t('purchasing.supplierSelect.cancel') }}</BaseButton>
                <BaseButton variant="secondary" :loading="saving" @click="confirm">{{ t('purchasing.supplierSelect.create') }}</BaseButton>
            </div>
        </div>
        <FieldError v-if="error">{{ error }}</FieldError>
    </div>
</template>

<style scoped>
.supplier-select { display: flex; flex-direction: column; gap: var(--space-1); }
.supplier-select__create { display: flex; flex-direction: column; gap: var(--space-2); }
</style>
