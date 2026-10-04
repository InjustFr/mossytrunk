<script setup>
import { ProgressIndicator, ProgressRoot } from 'reka-ui';

defineProps({
    percent: { type: Number, required: true },
    label: { type: String, required: true },
});
</script>

<template>
    <ProgressRoot :model-value="percent" :get-value-label="() => label" class="progress">
        <ProgressIndicator class="progress__fill" :style="{ width: `${percent}%` }" />
    </ProgressRoot>
</template>

<style scoped>
.progress { display: block; height: var(--progress-height, 0.5rem); overflow: hidden; border-radius: var(--radius-pill); background: var(--color-bg); }
.progress__fill { display: block; height: 100%; background: var(--color-accent); transition: width var(--transition); }
</style>
