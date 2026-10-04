<script setup>
import { computed } from 'vue';
import { X } from '@lucide/vue';
import { DialogClose, DialogContent, DialogOverlay, DialogPortal, DialogRoot, DialogTitle } from 'reka-ui';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    title: { type: String, required: true },
    variant: { type: String, default: 'dialog' },
    focusField: { type: Boolean, default: true },
});
const open = defineModel('open', { type: Boolean, required: true });

const modal = computed(() => props.variant === 'dialog');

function focusFirstField(event) {
    if (!props.focusField) {
        event.preventDefault();
        event.target.focus({ preventScroll: true });
        return;
    }
    const field = event.target.querySelector('.modal__body :is(input, textarea, button, [role="combobox"])');
    if (field) {
        event.preventDefault();
        field.focus();
    }
}
</script>

<template>
    <DialogRoot v-model:open="open" :modal="modal">
        <DialogPortal>
            <component :is="modal ? DialogOverlay : 'div'" :class="['modal', `modal--${props.variant}`]">
                <DialogContent class="modal__panel" :aria-describedby="undefined" @open-auto-focus="focusFirstField">
                    <header class="modal__header">
                        <DialogTitle class="modal__title">{{ title }}</DialogTitle>
                        <DialogClose class="modal__close" :aria-label="t('common.close')"><X size="1rem" aria-hidden="true" /></DialogClose>
                    </header>
                    <div class="modal__body"><slot /></div>
                </DialogContent>
            </component>
        </DialogPortal>
    </DialogRoot>
</template>
