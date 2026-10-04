<script setup>
import { ref } from 'vue';
import { Plus } from '@lucide/vue';
import { PopoverClose, PopoverContent, PopoverPortal, PopoverRoot, PopoverTrigger } from 'reka-ui';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import ColorPanel from '../ui/ColorPanel.vue';

const props = defineProps({
    initial: { type: String, required: true },
});
const emit = defineEmits(['pick']);
const { t } = useI18n();

const open = ref(false);
const draft = ref(props.initial);

function onOpen(isOpen) {
    if (isOpen) draft.value = props.initial;
}

function add() {
    emit('pick', draft.value.toLowerCase());
    open.value = false;
}
</script>

<template>
    <PopoverRoot v-model:open="open" @update:open="onOpen">
        <PopoverTrigger class="type-custom-color__trigger" :aria-label="t('products.colors.custom')" :title="t('products.colors.custom')">
            <Plus size="1rem" aria-hidden="true" />
        </PopoverTrigger>
        <PopoverPortal>
            <PopoverContent class="popover type-custom-color" side="bottom" align="start" :side-offset="8" :aria-label="t('products.colors.custom')">
                <ColorPanel v-model="draft" />
                <div class="actions-row">
                    <PopoverClose as-child><BaseButton variant="ghost">{{ t('common.cancel') }}</BaseButton></PopoverClose>
                    <BaseButton variant="secondary" @click="add">{{ t('common.add') }}</BaseButton>
                </div>
            </PopoverContent>
        </PopoverPortal>
    </PopoverRoot>
</template>

<style scoped>
.type-custom-color__trigger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.75rem;
    height: 1.75rem;
    padding: 0;
    border: 0.0625rem dashed var(--color-border-strong);
    border-radius: 50%;
    background: var(--color-surface);
    color: var(--color-muted);
    cursor: pointer;
    transition: color var(--transition), border-color var(--transition);
}

.type-custom-color__trigger:hover,
.type-custom-color__trigger[data-state='open'] { border-color: var(--color-ink); color: var(--color-ink); }
.type-custom-color__trigger:focus-visible { outline-offset: 0.25rem; }
</style>

<style>
.type-custom-color {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
    width: 15rem;
    padding: var(--space-3);
}
</style>
