<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Palette } from '@lucide/vue';
import BaseButton from '../ui/BaseButton.vue';
import BaseSelect from '../ui/BaseSelect.vue';
import FormField from '../ui/FormField.vue';

const props = defineProps({
    product: { type: Object, required: true },
    design: { type: Object, default: null },
    gabarits: { type: Array, required: true },
    designs: { type: Array, required: true },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['designed']);

const mode = ref(null);
const form = reactive({ gabaritId: '', designId: '' });
const error = ref(null);
const saving = ref(false);

const gabaritOptions = computed(() => props.gabarits.map((g) => ({ value: g.id, label: g.typeName ? `${g.name} (${g.typeName})` : g.name })));
const designOptions = computed(() => props.designs.map((d) => ({ value: d.id, label: d.name })));
const suggestedGabarit = computed(() => props.gabarits.find((g) => g.typeId && g.typeId === props.product.typeId)?.id ?? '');

watch(mode, () => {
    Object.assign(form, { gabaritId: suggestedGabarit.value, designId: '' });
    error.value = null;
});

async function onSubmit() {
    saving.value = true;
    error.value = null;
    try {
        const { designId } = await props.submit({ gabaritId: form.gabaritId, designId: mode.value === 'attach' ? form.designId : null });
        emit('designed', designId);
    } catch (exception) {
        error.value = exception.message;
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <div class="product-design">
        <a v-if="design" :href="`/creations/${design.id}`" class="product-design__link">
            <Palette size="1.25rem" aria-hidden="true" />
            <span>
                <strong>{{ design.name }}</strong>
                <span class="product-design__hint">Ouvrir le design pour le décliner sur un autre gabarit</span>
            </span>
        </a>
        <template v-else>
            <p class="product-design__intro">Ce produit n'a pas encore de design. Donnez-lui le sien pour le décliner facilement sur d'autres supports.</p>
            <div v-if="mode === null" class="product-design__choices">
                <BaseButton variant="secondary" :disabled="gabarits.length === 0" @click="mode = 'create'">Créer son design</BaseButton>
                <BaseButton variant="ghost" :disabled="gabarits.length === 0 || designs.length === 0" @click="mode = 'attach'">Rattacher à un design</BaseButton>
            </div>
            <p v-if="gabarits.length === 0" class="product-design__hint">Créez d'abord un gabarit depuis la page <a href="/creations">Créations</a>.</p>
            <form v-if="mode" class="product-design__form" novalidate @submit.prevent="onSubmit">
                <FormField v-if="mode === 'attach'" as="group" label="Design">
                    <BaseSelect v-model="form.designId" :options="designOptions" aria-label="Design" placeholder="Choisir un design" />
                </FormField>
                <FormField as="group" label="Gabarit de ce produit" hint="Le support sur lequel ce produit est déjà décliné.">
                    <BaseSelect v-model="form.gabaritId" :options="gabaritOptions" aria-label="Gabarit" placeholder="Choisir un gabarit" />
                </FormField>
                <p v-if="error" class="product-design__error" role="alert">{{ error }}</p>
                <div class="product-design__actions">
                    <BaseButton variant="ghost" @click="mode = null">Annuler</BaseButton>
                    <BaseButton type="submit" :loading="saving">{{ mode === 'create' ? 'Créer le design' : 'Rattacher' }}</BaseButton>
                </div>
            </form>
        </template>
    </div>
</template>

<style scoped>
.product-design { display: flex; flex-direction: column; gap: var(--space-3); }
.product-design__link { display: flex; align-items: flex-start; gap: var(--space-3); padding: var(--space-3); border: 0.0625rem solid var(--color-border); border-radius: var(--radius); color: var(--color-text); text-decoration: none; transition: border-color var(--transition); }
.product-design__link:hover { border-color: var(--color-ink); }
.product-design__link svg { flex: none; color: var(--color-accent); }
.product-design__link strong { display: block; font-family: var(--font-display); font-size: 1.2rem; font-weight: 400; }
.product-design__intro { margin: 0; color: var(--color-muted); font-size: 0.9rem; }
.product-design__hint { display: block; color: var(--color-muted); font-size: 0.8rem; }
.product-design__choices { display: flex; gap: var(--space-2); flex-wrap: wrap; }
.product-design__form { display: flex; flex-direction: column; gap: var(--space-3); }
.product-design__actions { display: flex; justify-content: flex-end; gap: var(--space-2); }
.product-design__error { margin: 0; color: var(--color-danger); font-size: 0.85rem; }
</style>
