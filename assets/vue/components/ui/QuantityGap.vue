<script setup>
import StatusBadge from './StatusBadge.vue';

defineProps({
    gap: { type: Number, default: null },
});
</script>

<template>
    <StatusBadge v-if="gap > 0" tone="warning">+{{ gap }}</StatusBadge>
    <StatusBadge v-else-if="gap < 0" tone="danger">{{ gap }}</StatusBadge>
</template>
