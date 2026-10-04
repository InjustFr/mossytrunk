<script setup>
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import BackButton from '../ui/BackButton.vue';
import BaseModal from '../ui/BaseModal.vue';
import ServiceForm from './ServiceForm.vue';
import ServicePicker from './ServicePicker.vue';

const { t } = useI18n();

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
    if (props.editing) return t('settings.modal.edit', { label: props.editing.label });
    return service.value ? t('settings.modal.add', { label: service.value.label }) : t('settings.modal.addService');
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
        <Transition name="step" mode="out-in">
            <ServicePicker v-if="!service" key="picker" :services="services" @choose="chosen = $event" />
            <div v-else :key="service.key" class="service-modal__step">
                <BackButton v-if="!editing" @click="chosen = null">{{ t('settings.modal.otherService') }}</BackButton>
                <ServiceForm :service="service" :submit="submit" @saved="onSaved" @cancel="open = false" />
            </div>
        </Transition>
    </BaseModal>
</template>

<style scoped>
.service-modal__step { display: flex; flex-direction: column; gap: var(--space-4); }

</style>
