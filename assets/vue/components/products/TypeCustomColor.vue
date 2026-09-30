<script setup>
import { ref } from 'vue';
import { Plus } from '@lucide/vue';
import {
    ColorAreaArea,
    ColorAreaRoot,
    ColorAreaThumb,
    ColorFieldInput,
    ColorFieldRoot,
    ColorSliderRoot,
    ColorSliderThumb,
    ColorSliderTrack,
    PopoverClose,
    PopoverContent,
    PopoverPortal,
    PopoverRoot,
    PopoverTrigger,
} from 'reka-ui';
import BaseButton from '../ui/BaseButton.vue';

const props = defineProps({
    initial: { type: String, required: true },
});
const emit = defineEmits(['pick']);

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
        <PopoverTrigger class="type-custom-color__trigger" aria-label="Couleur personnalisée" title="Couleur personnalisée">
            <Plus size="1rem" aria-hidden="true" />
        </PopoverTrigger>
        <PopoverPortal>
            <PopoverContent class="type-custom-color" side="bottom" align="start" :side-offset="8" aria-label="Couleur personnalisée">
                <ColorAreaRoot v-slot="{ style }" v-model="draft" color-space="hsb" x-channel="saturation" y-channel="brightness" class="type-custom-color__area-root">
                    <ColorAreaArea class="type-custom-color__area" :style="style">
                        <ColorAreaThumb class="type-custom-color__thumb" aria-label="Saturation et luminosité" />
                    </ColorAreaArea>
                </ColorAreaRoot>
                <ColorSliderRoot v-model="draft" channel="hue" color-space="hsb" class="type-custom-color__slider">
                    <ColorSliderTrack class="type-custom-color__track" />
                    <ColorSliderThumb class="type-custom-color__thumb" aria-label="Teinte" />
                </ColorSliderRoot>
                <div class="type-custom-color__row">
                    <span class="type-custom-color__preview" :style="{ background: draft }" aria-hidden="true" />
                    <ColorFieldRoot v-model="draft" class="type-custom-color__field">
                        <ColorFieldInput class="type-custom-color__input" aria-label="Code hexadécimal" />
                    </ColorFieldRoot>
                </div>
                <div class="type-custom-color__actions">
                    <PopoverClose as-child><BaseButton variant="ghost">Annuler</BaseButton></PopoverClose>
                    <BaseButton variant="secondary" @click="add">Ajouter</BaseButton>
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
.type-custom-color__trigger:focus-visible { outline: 0.125rem solid var(--color-accent); outline-offset: 0.25rem; }
</style>

<style>
.type-custom-color {
    z-index: 60;
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
    width: 15rem;
    padding: var(--space-3);
    border: 0.0625rem solid var(--color-border);
    border-radius: var(--radius);
    background: var(--color-surface);
    box-shadow: var(--shadow);
}

.type-custom-color__area {
    position: relative;
    height: 9rem;
    border-radius: var(--radius);
    cursor: crosshair;
}

.type-custom-color__slider {
    position: relative;
    display: flex;
    align-items: center;
    height: 1rem;
}

.type-custom-color__track { position: relative; flex: 1; height: 0.75rem; border-radius: 0.375rem; }

.type-custom-color__thumb {
    display: block;
    width: 1rem;
    height: 1rem;
    border: 0.125rem solid #ffffff;
    border-radius: 50%;
    box-shadow: 0 0 0 0.0625rem rgb(0 0 0 / 0.35);
}

.type-custom-color__thumb:focus-visible { outline: 0.125rem solid var(--color-accent); outline-offset: 0.125rem; }

.type-custom-color__row { display: flex; align-items: center; gap: var(--space-2); }
.type-custom-color__preview { flex: none; width: 2.375rem; height: 2.375rem; border-radius: var(--radius); border: 0.0625rem solid var(--color-border); }
.type-custom-color__field { flex: 1; }

.type-custom-color__input {
    width: 100%;
    min-height: 2.375rem;
    padding: var(--space-2) var(--space-3);
    border: 0.0625rem solid var(--color-border-strong);
    border-radius: var(--radius);
    font: inherit;
    text-transform: uppercase;
}

.type-custom-color__actions { display: flex; justify-content: flex-end; gap: var(--space-2); }
</style>
