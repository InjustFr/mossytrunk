<script setup>
import { ref } from 'vue';
import { ImagePlus, LoaderCircle, PenLine, X } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import IconButton from '../ui/IconButton.vue';

defineProps({
    pages: { type: Array, required: true },
});
const emit = defineEmits(['add-photos', 'add-typed', 'remove', 'write']);
const { t } = useI18n();
const input = ref(null);

function onPicked(event) {
    const files = Array.from(event.target.files ?? []);
    event.target.value = '';
    if (files.length) emit('add-photos', files);
}
</script>

<template>
    <div class="notebook-pages">
        <ol v-if="pages.length" class="notebook-pages__list">
            <li v-for="(page, index) in pages" :key="page.id" class="notebook-pages__page">
                <div class="notebook-pages__photo">
                    <img v-if="page.preview" class="notebook-pages__image" :src="page.preview" :alt="t('notebook.page.pageNumber', { number: index + 1 })">
                    <PenLine v-else class="notebook-pages__typed" size="1.5rem" aria-hidden="true" />
                    <span class="notebook-pages__number">{{ index + 1 }}</span>
                </div>
                <div class="notebook-pages__text">
                    <p v-if="page.reading" class="notebook-pages__status" role="status">
                        <LoaderCircle class="notebook-pages__spinner" size="1rem" aria-hidden="true" /> {{ t('notebook.page.reading') }}
                    </p>
                    <p v-else-if="page.error" class="notebook-pages__error" role="alert">{{ page.error }}</p>
                    <textarea
                        v-else
                        class="notebook-pages__textarea"
                        rows="8"
                        :value="page.text"
                        :aria-label="t('notebook.page.pageText', { number: index + 1 })"
                        :placeholder="t('notebook.page.textPlaceholder')"
                        @input="emit('write', page.id, $event.target.value)"
                    />
                </div>
                <IconButton class="notebook-pages__remove" :icon="X" :label="t('notebook.page.removePage', { number: index + 1 })" @click="emit('remove', page.id)" />
            </li>
        </ol>
        <input ref="input" class="notebook-pages__input" type="file" accept="image/*" multiple :aria-label="t('notebook.page.addPhotos')" @change="onPicked">
        <div class="notebook-pages__add">
            <BaseButton variant="secondary" @click="input.click()"><ImagePlus size="1rem" aria-hidden="true" /> {{ t('notebook.page.addPhotos') }}</BaseButton>
            <BaseButton variant="ghost" @click="emit('add-typed')"><PenLine size="1rem" aria-hidden="true" /> {{ t('notebook.page.addTyped') }}</BaseButton>
        </div>
    </div>
</template>

<style scoped>
.notebook-pages { display: flex; flex-direction: column; gap: var(--space-3); }
.notebook-pages__list { display: flex; flex-direction: column; gap: var(--space-3); margin: 0; padding: 0; list-style: none; }
.notebook-pages__page { display: grid; grid-template-columns: 7.5rem 1fr auto; gap: var(--space-3); align-items: start; }
.notebook-pages__photo { position: relative; display: flex; align-items: center; justify-content: center; aspect-ratio: 3 / 4; overflow: hidden; border: 0.0625rem solid var(--color-border); border-radius: var(--radius); background: var(--color-bg); color: var(--color-subtle); }
.notebook-pages__image { width: 100%; height: 100%; object-fit: cover; }
.notebook-pages__number { position: absolute; left: var(--space-2); bottom: var(--space-2); padding: 0 var(--space-2); border-radius: var(--radius); background: var(--color-surface); color: var(--color-ink); font-size: 0.75rem; font-weight: 600; }
.notebook-pages__textarea { width: 100%; min-height: 10rem; padding: var(--space-2) var(--space-3); border: 0.0625rem solid var(--color-border-strong); border-radius: var(--radius); font: inherit; font-family: ui-monospace, monospace; font-size: 0.875rem; resize: vertical; }
.notebook-pages__status { display: flex; align-items: center; gap: var(--space-2); margin: 0; color: var(--color-muted); }
.notebook-pages__spinner { animation: notebook-pages-spin 1s linear infinite; }
.notebook-pages__error { margin: 0; padding: var(--space-2) var(--space-3); border-radius: var(--radius); background: var(--color-danger-soft); color: var(--color-danger); }
.notebook-pages__input { position: absolute; width: 0.0625rem; height: 0.0625rem; overflow: hidden; clip-path: inset(50%); }
.notebook-pages__add { display: flex; flex-wrap: wrap; gap: var(--space-2); }
@media (max-width: 40rem) { .notebook-pages__page { grid-template-columns: 5rem 1fr auto; } }
@keyframes notebook-pages-spin { to { transform: rotate(360deg); } }
</style>
