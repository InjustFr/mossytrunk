<script setup>
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import BaseButton from './BaseButton.vue';
import BaseSelect from './BaseSelect.vue';
import FieldError from './FieldError.vue';
import { formErrors } from '../../composables/useFormSubmit.js';

const props = defineProps({
    options: { type: Array, required: true },
    create: { type: Function, required: true },
    label: { type: String, required: true },
    placeholder: { type: String, required: true },
    createOption: { type: String, required: true },
    nameLabel: { type: String, required: true },
    namePlaceholder: { type: String, default: '' },
    nameRequired: { type: String, default: null },
});
const emit = defineEmits(['creating']);
const model = defineModel({ type: String, default: '' });
const { t } = useI18n();

const CREATE = '__create__';
const selected = ref(model.value);
const creating = ref(false);
const newName = ref('');
const error = ref(null);
const saving = ref(false);
const vFocus = { mounted: (element) => element.focus() };

const choices = computed(() => [...props.options, { value: CREATE, label: props.createOption }]);

watch(model, (value) => { selected.value = value; });

watch(selected, (value) => {
    if (value === CREATE) {
        creating.value = true;
        emit('creating');
        return;
    }
    model.value = value;
});

function close() {
    creating.value = false;
    newName.value = '';
    error.value = null;
}

async function confirm() {
    if (props.nameRequired && newName.value.trim() === '') {
        error.value = props.nameRequired;
        return;
    }
    saving.value = true;
    error.value = null;
    try {
        const created = await props.create(newName.value);
        model.value = created.id;
        selected.value = created.id;
        close();
    } catch (e) {
        error.value = formErrors(e, 'name').name ?? e.message;
    } finally {
        saving.value = false;
    }
}

function cancel() {
    close();
    selected.value = model.value;
}
</script>

<template>
    <div class="creatable-select">
        <BaseSelect v-if="!creating" v-model="selected" :options="choices" :placeholder="placeholder" :aria-label="label" />
        <div v-else class="creatable-select__create">
            <input
                v-model="newName"
                v-focus
                class="control"
                type="text"
                :placeholder="namePlaceholder"
                :aria-label="nameLabel"
                @keydown.enter.prevent="confirm"
                @keydown.esc.prevent.stop="cancel"
            >
            <slot name="extra" />
            <div class="actions-row">
                <BaseButton variant="ghost" @click="cancel">{{ t('common.cancel') }}</BaseButton>
                <BaseButton variant="secondary" :loading="saving" @click="confirm">{{ t('common.create') }}</BaseButton>
            </div>
        </div>
        <FieldError v-if="error">{{ error }}</FieldError>
    </div>
</template>

<style scoped>
.creatable-select { display: flex; flex-direction: column; gap: var(--space-1); }
.creatable-select__create { display: flex; flex-direction: column; gap: var(--space-3); padding: var(--space-3); border-radius: var(--radius); background: var(--color-bg); }
</style>
