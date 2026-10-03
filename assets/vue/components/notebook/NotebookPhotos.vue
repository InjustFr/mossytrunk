<script setup>
import { onBeforeUnmount, ref, watch } from 'vue';
import { ImagePlus, X } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import IconButton from '../ui/IconButton.vue';

const files = defineModel({ type: Array, required: true });
const { t } = useI18n();
const input = ref(null);
const previews = ref([]);

function onPicked(event) {
    files.value = [...files.value, ...Array.from(event.target.files ?? [])];
    event.target.value = '';
}

const remove = (index) => {
    files.value = files.value.filter((_, position) => position !== index);
};

watch(files, (current) => {
    previews.value.forEach(URL.revokeObjectURL);
    previews.value = current.map((file) => URL.createObjectURL(file));
}, { immediate: true });

onBeforeUnmount(() => previews.value.forEach(URL.revokeObjectURL));
</script>

<template>
    <div class="notebook-photos">
        <ol v-if="previews.length" class="notebook-photos__pages">
            <li v-for="(preview, index) in previews" :key="preview" class="notebook-photos__page">
                <img class="notebook-photos__image" :src="preview" :alt="t('notebook.page.pageNumber', { number: index + 1 })">
                <span class="notebook-photos__number">{{ index + 1 }}</span>
                <IconButton class="notebook-photos__remove" :icon="X" :label="t('notebook.page.removePage', { number: index + 1 })" @click="remove(index)" />
            </li>
        </ol>
        <input ref="input" class="notebook-photos__input" type="file" accept="image/*" multiple :aria-label="t('notebook.page.addPhotos')" @change="onPicked">
        <BaseButton variant="secondary" @click="input.click()">
            <ImagePlus size="1rem" aria-hidden="true" /> {{ t('notebook.page.addPhotos') }}
        </BaseButton>
    </div>
</template>

<style scoped>
.notebook-photos { display: flex; flex-direction: column; align-items: flex-start; gap: var(--space-3); }
.notebook-photos__pages { display: grid; grid-template-columns: repeat(auto-fill, minmax(7.5rem, 1fr)); gap: var(--space-3); width: 100%; margin: 0; padding: 0; list-style: none; }
.notebook-photos__page { position: relative; aspect-ratio: 3 / 4; overflow: hidden; border: 0.0625rem solid var(--color-border); border-radius: var(--radius); background: var(--color-bg); }
.notebook-photos__image { width: 100%; height: 100%; object-fit: cover; }
.notebook-photos__number { position: absolute; left: var(--space-2); bottom: var(--space-2); padding: 0 var(--space-2); border-radius: var(--radius); background: var(--color-surface); color: var(--color-ink); font-size: 0.75rem; font-weight: 600; }
.notebook-photos__remove { position: absolute; top: var(--space-1); right: var(--space-1); background: var(--color-surface); }
.notebook-photos__input { position: absolute; width: 0.0625rem; height: 0.0625rem; overflow: hidden; clip-path: inset(50%); }
</style>
