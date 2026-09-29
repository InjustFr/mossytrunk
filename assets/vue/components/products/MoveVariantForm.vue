<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { ToggleGroupItem, ToggleGroupRoot } from 'reka-ui';
import BaseButton from '../ui/BaseButton.vue';
import BaseCombobox from '../ui/BaseCombobox.vue';
import BaseSelect from '../ui/BaseSelect.vue';
import FormField from '../ui/FormField.vue';

const props = defineProps({
    product: { type: Object, required: true },
    products: { type: Array, required: true },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['moved', 'cancel']);

const form = reactive({ variant: '', mode: 'new', targetProductId: '', newProductName: '', targetVariant: '' });
const errors = ref({});
const saving = ref(false);

const hasVariants = computed(() => props.product.variants.length > 0);
const variantOptions = computed(() => props.product.variants.map((variant) => ({ value: variant, label: variant })));
const productOptions = computed(() => props.products
    .filter((product) => product.id !== props.product.id)
    .map((product) => ({ value: product.id, label: product.displayName })));
const target = computed(() => props.products.find((product) => product.id === form.targetProductId) ?? null);
const targetName = computed(() => (form.mode === 'new' ? form.newProductName.trim() : target.value?.displayName ?? ''));

const lastWord = (name) => name.trim().split(/\s+/).at(-1) ?? '';
const withoutLastWord = (name) => name.trim().split(/\s+/).slice(0, -1).join(' ');

watch(() => props.product, (product) => {
    const whole = product.variants.length === 0;
    Object.assign(form, {
        variant: product.variants[0] ?? '',
        mode: 'new',
        targetProductId: '',
        newProductName: whole ? withoutLastWord(product.name) : product.name,
        targetVariant: whole ? lastWord(product.name) : product.variants[0] ?? '',
    });
    errors.value = {};
}, { immediate: true });

watch(() => form.variant, (variant) => {
    if (hasVariants.value) {
        form.targetVariant = variant;
    }
});

watch(target, (product) => {
    if (!hasVariants.value && product && props.product.displayName.startsWith(`${product.displayName} `)) {
        form.targetVariant = props.product.displayName.slice(product.displayName.length + 1);
    }
});

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        await props.submit({
            variant: hasVariants.value ? form.variant : null,
            targetProductId: form.mode === 'existing' ? form.targetProductId || null : null,
            newProductName: form.mode === 'new' ? form.newProductName : null,
            targetVariant: form.targetVariant,
        });
        emit('moved', { variant: form.targetVariant.trim(), target: targetName.value });
    } catch (error) {
        errors.value = error.fieldErrors ?? {};
        if (Object.keys(errors.value).length === 0) {
            errors.value = { form: error.message };
        }
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="move-variant-form" novalidate @submit.prevent="onSubmit">
        <fieldset class="form-lock" :disabled="saving">
            <p v-if="errors.form" class="move-variant-form__error" role="alert">{{ errors.form }}</p>

            <FormField v-if="hasVariants" as="group" label="Variante à déplacer">
                <BaseSelect v-model="form.variant" :options="variantOptions" aria-label="Variante à déplacer" />
            </FormField>
            <p v-else class="move-variant-form__intro">
                « {{ product.displayName }} » devient une variante d'un autre produit, avec toutes ses ventes. Il disparaît ensuite du catalogue.
            </p>

            <ToggleGroupRoot :model-value="form.mode" type="single" class="move-variant-form__modes" aria-label="Destination" @update:model-value="(mode) => mode && (form.mode = mode)">
                <ToggleGroupItem value="new" class="move-variant-form__mode">Nouveau produit</ToggleGroupItem>
                <ToggleGroupItem value="existing" class="move-variant-form__mode">Produit existant</ToggleGroupItem>
            </ToggleGroupRoot>

            <FormField v-if="form.mode === 'new'" label="Nom du nouveau produit" :error="errors.newProductName" hint="Même type et mêmes prix que le produit d'origine.">
                <input v-model="form.newProductName" type="text">
            </FormField>
            <FormField v-else as="group" label="Produit de destination" :error="errors.targetProductId">
                <BaseCombobox v-model="form.targetProductId" :options="productOptions" aria-label="Produit de destination" placeholder="Rechercher un produit…" />
            </FormField>

            <FormField label="Variante dans le produit de destination" :error="errors.targetVariant" hint="Créée si elle n'existe pas. Laisser vide pour fusionner avec un produit unique.">
                <input v-model="form.targetVariant" type="text">
            </FormField>

            <p class="move-variant-form__summary">
                Les ventes passées gardent leur prix et comptent désormais pour
                <strong>{{ targetName || '…' }}<template v-if="form.targetVariant.trim()"> — {{ form.targetVariant.trim() }}</template></strong>.
            </p>

            <div class="move-variant-form__actions">
                <BaseButton variant="ghost" @click="emit('cancel')">Annuler</BaseButton>
                <BaseButton type="submit" :loading="saving">Déplacer</BaseButton>
            </div>
        </fieldset>
    </form>
</template>

<style scoped>
.move-variant-form { display: flex; flex-direction: column; gap: var(--space-3); }
.move-variant-form__intro,
.move-variant-form__summary { margin: 0; color: var(--color-muted); font-size: 0.9rem; }
.move-variant-form__summary strong { color: var(--color-ink); }

.move-variant-form__modes { display: flex; gap: var(--space-2); }

.move-variant-form__mode {
    padding: var(--space-1) var(--space-3);
    border: 0.0625rem solid var(--color-border-strong);
    border-radius: 62.4375rem;
    background: var(--color-surface);
    font-size: 0.85rem;
    cursor: pointer;
    transition: background var(--transition), color var(--transition), border-color var(--transition);
}

.move-variant-form__mode:hover { border-color: var(--color-ink); }
.move-variant-form__mode[data-state="on"] { background: var(--color-ink); border-color: var(--color-ink); color: var(--color-surface); }

.move-variant-form__actions { display: flex; justify-content: flex-end; gap: var(--space-2); }

.move-variant-form__error {
    margin: 0;
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius);
    background: var(--color-danger-soft);
    color: var(--color-danger);
}
</style>
