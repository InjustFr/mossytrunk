<script setup>
import { computed, ref } from 'vue';
import { RadioGroupItem, RadioGroupRoot } from 'reka-ui';
import { useI18n } from 'vue-i18n';
import BaseColorPicker from '../ui/BaseColorPicker.vue';
import { useToast } from '../../composables/useToast.js';
import { THEMES, applyTheme, presetOf, themeNamed, useTheme } from '../../composables/useTheme.js';

const { t } = useI18n();
const toast = useToast();
const { saved, choose } = useTheme();

const kept = ref({ ...saved });
const theme = ref({ ...saved });
const preset = computed(() => presetOf(theme.value));

async function save(next) {
    theme.value = { ...next };
    if (next.background === kept.value.background && next.accent === kept.value.accent) return;
    try {
        await choose(next);
        kept.value = { ...next };
        toast.success(t('settings.appearance.saved'));
    } catch (error) {
        theme.value = { ...kept.value };
        applyTheme(kept.value);
        toast.error(error.message);
    }
}

function preview(key, color) {
    theme.value = { ...theme.value, [key]: color.toLowerCase() };
    applyTheme(theme.value);
}

const pick = (name) => save(themeNamed(name));
</script>

<template>
    <div class="appearance">
        <RadioGroupRoot :model-value="preset" class="appearance__themes" :aria-label="t('settings.appearance.themesLabel')" @update:model-value="pick">
            <RadioGroupItem v-for="option in THEMES" :key="option.name" :value="option.name" class="appearance__theme">
                <span class="appearance__sample" :style="{ background: option.background }" aria-hidden="true">
                    <span class="appearance__sample-accent" :style="{ background: option.accent }" />
                </span>
                <span class="appearance__name">{{ t(`settings.appearance.themes.${option.name}`) }}</span>
            </RadioGroupItem>
        </RadioGroupRoot>
        <div class="appearance__colors">
            <div class="appearance__color">
                <span class="appearance__label" aria-hidden="true">{{ t('settings.appearance.background') }}</span>
                <BaseColorPicker
                    :model-value="theme.background"
                    :label="t('settings.appearance.background')"
                    @update:model-value="preview('background', $event)"
                    @commit="save(theme)"
                />
            </div>
            <div class="appearance__color">
                <span class="appearance__label" aria-hidden="true">{{ t('settings.appearance.accent') }}</span>
                <BaseColorPicker
                    :model-value="theme.accent"
                    :label="t('settings.appearance.accent')"
                    @update:model-value="preview('accent', $event)"
                    @commit="save(theme)"
                />
            </div>
        </div>
    </div>
</template>

<style scoped>
.appearance { display: flex; flex-direction: column; gap: var(--space-5); }
.appearance__themes { display: grid; grid-template-columns: repeat(auto-fill, minmax(8rem, 1fr)); gap: var(--space-3); }

.appearance__theme {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
    padding: var(--space-2);
    border: 0.0625rem solid var(--color-border);
    border-radius: var(--radius);
    background: var(--color-surface);
    color: inherit;
    font: inherit;
    text-align: left;
    cursor: pointer;
    transition: border-color var(--transition), background var(--transition);
}

.appearance__theme:hover { border-color: var(--color-border-strong); }
.appearance__theme[data-state='checked'] { border-color: var(--color-accent); background: var(--color-accent-soft); }

.appearance__sample {
    display: flex;
    align-items: flex-end;
    height: 3rem;
    padding: var(--space-2);
    border: 0.0625rem solid var(--color-border);
    border-radius: calc(var(--radius) - 0.125rem);
}

.appearance__sample-accent { width: 40%; height: 0.5rem; border-radius: 0.25rem; }
.appearance__name { font-size: 0.875rem; font-weight: 500; color: var(--color-ink); }

.appearance__colors { display: flex; flex-wrap: wrap; gap: var(--space-5); }
.appearance__color { display: flex; flex-direction: column; gap: var(--space-2); }
.appearance__label { color: var(--color-muted); font-size: 0.75rem; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; }
</style>
