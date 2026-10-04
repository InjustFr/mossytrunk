<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import CreatableSelect from '../ui/CreatableSelect.vue';
import TypeColorPicker from './TypeColorPicker.vue';
import { useProductTypes } from '../../composables/useProductTypes.js';
import { nextTypeColor } from '../../composables/useTypeColor.js';

const typeId = defineModel({ type: String, default: '' });
const { types, create } = useProductTypes();
const { t } = useI18n();

const newColor = ref('');

const options = computed(() => types.value
    .filter((type) => !type.archived || type.id === typeId.value)
    .map((type) => ({ value: type.id, label: type.archived ? t('products.types.archivedOption', { name: type.name }) : type.name })));
</script>

<template>
    <CreatableSelect
        v-model="typeId"
        :options="options"
        :create="(name) => create(name, newColor)"
        :label="t('products.types.select')"
        :placeholder="t('products.types.choose')"
        :create-option="t('products.types.createOption')"
        :name-label="t('products.types.newName')"
        :name-placeholder="t('products.types.nameExample')"
        :name-required="t('products.types.nameRequired')"
        @creating="newColor = nextTypeColor(types)"
    >
        <template #extra><TypeColorPicker v-model="newColor" /></template>
    </CreatableSelect>
</template>
