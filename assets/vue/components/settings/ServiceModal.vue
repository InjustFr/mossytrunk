<script setup>
import { computed, ref, watch } from 'vue';
import { ChevronLeft } from '@lucide/vue';
import BaseModal from '../ui/BaseModal.vue';
import ServiceForm from './ServiceForm.vue';
import ServicePicker from './ServicePicker.vue';

const props = defineProps({
    services: { type: Array, required: true },
    editing: { type: Object, default: null },
    add: { type: Function, required: true },
    update: { type: Function, required: true },
});
const emit = defineEmits(['saved']);
const open = defineModel('open', { type: Boolean, required: true });

const chosen = ref(null);

watch(open, (isOpen) => {
    if (isOpen) {
        chosen.value = props.editing;
    }
});

const service = computed(() => chosen.value);
const title = computed(() => {
    if (props.editing) return `Modifier ${props.editing.label}`;
    return service.value ? `Ajouter ${service.value.label}` : 'Ajouter un service';
});

function submit(payload) {
    return props.editing ? props.update(service.value.key, payload) : props.add(service.value.key, payload);
}

function onSaved() {
    emit('saved', service.value, !props.editing);
    open.value = false;
}
</script>

<template>
    <BaseModal v-model:open="open" :title="title">
        <Transition name="service-modal__step" mode="out-in">
            <ServicePicker v-if="!service" key="picker" :services="services" @choose="chosen = $event" />
            <div v-else :key="service.key" class="service-modal__step">
                <button v-if="!editing" type="button" class="service-modal__back" @click="chosen = null">
                    <ChevronLeft size="1rem" aria-hidden="true" /> Autre service
                </button>
                <ServiceForm :service="service" :submit="submit" @saved="onSaved" @cancel="open = false" />
            </div>
        </Transition>
    </BaseModal>
</template>

<style>
.service-modal__step { display: flex; flex-direction: column; gap: var(--space-4); }

.service-modal__back {
    display: inline-flex;
    align-self: flex-start;
    align-items: center;
    gap: var(--space-1);
    padding: 0;
    border: none;
    background: none;
    color: var(--color-muted);
    font: inherit;
    font-size: 0.875rem;
    cursor: pointer;
}

.service-modal__back:hover { color: var(--color-ink); }

.service-modal__step-enter-active,
.service-modal__step-leave-active { transition: opacity var(--transition), transform var(--transition); }
.service-modal__step-enter-from { opacity: 0; transform: translateX(0.5rem); }
.service-modal__step-leave-to { opacity: 0; transform: translateX(-0.5rem); }

@media (prefers-reduced-motion: reduce) {
    .service-modal__step-enter-active,
    .service-modal__step-leave-active { transition: none; }
}
</style>
