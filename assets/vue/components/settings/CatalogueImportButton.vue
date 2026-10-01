<script setup>
import { Upload } from '@lucide/vue';
import { ref } from 'vue';
import BaseButton from '../ui/BaseButton.vue';

defineProps({
    label: { type: String, required: true },
    loading: { type: Boolean, default: false },
});
const emit = defineEmits(['chosen']);
const input = ref(null);

function choose(event) {
    const [file] = event.target.files;
    if (file) emit('chosen', file);
    event.target.value = '';
}
</script>

<template>
    <span class="catalogue-import">
        <input ref="input" class="catalogue-import__input" type="file" accept=".csv,text/csv" :aria-label="label" @change="choose">
        <BaseButton variant="secondary" :loading="loading" @click="input.click()">
            <Upload size="1rem" aria-hidden="true" /> {{ label }}
        </BaseButton>
    </span>
</template>

<style scoped>
.catalogue-import__input {
    position: absolute;
    width: 0.0625rem;
    height: 0.0625rem;
    overflow: hidden;
    clip-path: inset(50%);
    white-space: nowrap;
}
</style>
