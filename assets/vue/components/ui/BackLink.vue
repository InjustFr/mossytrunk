<script setup>
import { ArrowLeft } from '@lucide/vue';

defineProps({
    href: { type: String, required: true },
});
</script>

<template>
    <a class="back-link" :href="href"><ArrowLeft size="0.875rem" aria-hidden="true" /> <slot /></a>
</template>

<style scoped>
.back-link { display: inline-flex; align-items: center; gap: var(--space-1); }
</style>
