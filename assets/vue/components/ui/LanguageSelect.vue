<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import BaseSelect from './BaseSelect.vue';
import { LANGUAGES, currentLanguage } from '../../i18n/locale.js';
import { useLanguage } from '../../composables/useLanguage.js';

const { t } = useI18n();
const { choose } = useLanguage();
const language = ref(currentLanguage());
const options = LANGUAGES.map(({ value, label }) => ({ value, label }));

function onChange(value) {
    if (value !== currentLanguage()) choose(value);
}
</script>

<template>
    <BaseSelect :model-value="language" :options="options" size="small" :aria-label="t('common.language')" @update:model-value="onChange" />
</template>
