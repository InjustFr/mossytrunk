<script setup>
import { ref } from 'vue';
import BaseButton from './BaseButton.vue';

// Two-step destructive action: first click arms, second click confirms.
defineProps({
    label: { type: String, required: true },
    confirmLabel: { type: String, default: 'Confirmer ?' },
});
const emit = defineEmits(['confirm']);

const armed = ref(false);
let timer = null;

function onClick() {
    if (armed.value) {
        clearTimeout(timer);
        armed.value = false;
        emit('confirm');
        return;
    }
    armed.value = true;
    timer = setTimeout(() => { armed.value = false; }, 3000);
}
</script>

<template>
    <BaseButton :variant="armed ? 'danger' : 'ghost'" class="confirm-button" @click="onClick">
        {{ armed ? confirmLabel : label }}
    </BaseButton>
</template>
