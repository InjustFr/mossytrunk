<script setup>
import { computed } from 'vue';

const props = defineProps({
    result: { type: Number, required: true },
    turnover: { type: Number, required: true },
});

const keptShare = computed(() => (props.turnover > 0 ? Math.max(0, Math.min(1, props.result / props.turnover)) * 100 : 0));
</script>

<template>
    <span :class="['track', { 'track--loss': result < 0 }]" aria-hidden="true">
        <span class="kept-share-track__kept" :style="{ width: `${keptShare}%` }" />
    </span>
</template>

<style scoped>
.kept-share-track__kept { background: var(--color-accent); }
</style>
