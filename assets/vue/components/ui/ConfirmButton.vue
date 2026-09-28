<script setup>
import { ref } from 'vue';
import BaseButton from './BaseButton.vue';
import IconButton from './IconButton.vue';

// Two-step destructive action: first click arms, second click confirms.
// With an `icon`, the resting state is an icon-only button (table rows); armed, it always shows the confirm text.
defineProps({
    label: { type: String, required: true },
    confirmLabel: { type: String, default: 'Confirmer ?' },
    icon: { type: [Object, Function], default: null },
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
    <IconButton v-if="icon && !armed" :icon="icon" :label="label" variant="danger" class="confirm-button" @click="onClick" />
    <BaseButton v-else :variant="armed ? 'danger' : 'ghost'" class="confirm-button" @click="onClick">
        {{ armed ? confirmLabel : label }}
    </BaseButton>
</template>
