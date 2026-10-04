<script setup>
import { ToggleGroupRoot } from 'reka-ui';

const model = defineModel({ type: [String, Number], required: true });
const choose = (value) => { if (value) model.value = value; };
</script>

<template>
    <ToggleGroupRoot type="single" :model-value="model" @update:model-value="choose"><slot /></ToggleGroupRoot>
</template>
