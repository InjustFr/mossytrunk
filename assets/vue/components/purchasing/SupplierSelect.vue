<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import CreatableSelect from '../ui/CreatableSelect.vue';

const props = defineProps({
    suppliers: { type: Array, required: true },
    save: { type: Function, required: true },
});
const supplierId = defineModel({ type: String, default: '' });
const { t } = useI18n();

const options = computed(() => props.suppliers.map((supplier) => ({ value: supplier.id, label: supplier.name })));
</script>

<template>
    <CreatableSelect
        v-model="supplierId"
        :options="options"
        :create="(name) => save(null, { name })"
        :label="t('purchasing.supplierSelect.label')"
        :placeholder="t('purchasing.supplierSelect.placeholder')"
        :create-option="t('purchasing.supplierSelect.new')"
        :name-label="t('purchasing.supplierSelect.nameLabel')"
        :name-placeholder="t('purchasing.supplierSelect.namePlaceholder')"
    />
</template>
