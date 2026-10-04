<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import StatusBadge from '../ui/StatusBadge.vue';

const props = defineProps({
    units: { type: Number, required: true },
    low: { type: Boolean, default: false },
    negative: { type: Boolean, default: false },
});

const { t } = useI18n();

const flag = computed(() => {
    if (props.negative) {
        return { tone: 'danger', label: t('products.negative') };
    }
    if (props.units === 0) {
        return { tone: 'danger', label: t('products.emptyStock') };
    }
    if (props.low) {
        return { tone: 'warning', label: t('products.lowStock') };
    }
    return null;
});
</script>

<template>
    <StatusBadge v-if="flag" :tone="flag.tone">{{ flag.label }}</StatusBadge>
</template>
