<script setup>
import { ColorAreaArea, ColorAreaRoot, ColorAreaThumb, ColorFieldInput, ColorFieldRoot, ColorSliderRoot, ColorSliderThumb, ColorSliderTrack } from 'reka-ui';
import { useI18n } from 'vue-i18n';

const color = defineModel({ type: String, required: true });
const { t } = useI18n();
</script>

<template>
    <div class="color-panel">
        <ColorAreaRoot v-slot="{ style }" v-model="color" color-space="hsb" x-channel="saturation" y-channel="brightness">
            <ColorAreaArea class="color-panel__area" :style="style">
                <ColorAreaThumb class="color-panel__thumb" :aria-label="t('ui.color.area')" />
            </ColorAreaArea>
        </ColorAreaRoot>
        <ColorSliderRoot v-model="color" channel="hue" color-space="hsb" class="color-panel__slider">
            <ColorSliderTrack class="color-panel__track" />
            <ColorSliderThumb class="color-panel__thumb" :aria-label="t('ui.color.hue')" />
        </ColorSliderRoot>
        <div class="color-panel__row">
            <span class="color-panel__preview" :style="{ background: color }" aria-hidden="true" />
            <ColorFieldRoot v-model="color" class="color-panel__field">
                <ColorFieldInput class="color-panel__input" :aria-label="t('ui.color.hex')" />
            </ColorFieldRoot>
        </div>
    </div>
</template>

<style>
.color-panel { display: flex; flex-direction: column; gap: var(--space-3); }

.color-panel__area {
    position: relative;
    height: 9rem;
    border-radius: var(--radius);
    cursor: crosshair;
}

.color-panel__slider {
    position: relative;
    display: flex;
    align-items: center;
    height: 1rem;
}

.color-panel__track { position: relative; flex: 1; height: 0.75rem; border-radius: 0.375rem; }

.color-panel__thumb {
    display: block;
    width: 1rem;
    height: 1rem;
    border: 0.125rem solid #ffffff;
    border-radius: 50%;
    box-shadow: 0 0 0 0.0625rem rgb(0 0 0 / 0.35);
}

.color-panel__thumb:focus-visible { outline: 0.125rem solid var(--color-accent); outline-offset: 0.125rem; }

.color-panel__row { display: flex; align-items: center; gap: var(--space-2); }
.color-panel__preview { flex: none; width: 2.375rem; height: 2.375rem; border-radius: var(--radius); border: 0.0625rem solid var(--color-border); }
.color-panel__field { flex: 1; }

.color-panel__input {
    width: 100%;
    min-height: 2.375rem;
    padding: var(--space-2) var(--space-3);
    border: 0.0625rem solid var(--color-border-strong);
    border-radius: var(--radius);
    background: var(--color-surface);
    font: inherit;
    text-transform: uppercase;
}
</style>
