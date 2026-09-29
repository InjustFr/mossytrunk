<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Trash2 } from '@lucide/vue';
import BaseButton from '../ui/BaseButton.vue';
import BaseCheckbox from '../ui/BaseCheckbox.vue';
import BaseMoneyField from '../ui/BaseMoneyField.vue';
import ConfirmButton from '../ui/ConfirmButton.vue';
import FormField from '../ui/FormField.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import StatusBadge from '../ui/StatusBadge.vue';
import VariantsInput from '../products/VariantsInput.vue';

const props = defineProps({
    declination: { type: Object, required: true },
    locked: { type: Boolean, default: false },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['tick', 'withdraw']);

const form = reactive({ productName: '', sellingPrice: null, buyingPrice: 0, variants: [] });
const errors = ref({});
const saving = ref(false);

watch(() => props.declination, (declination) => {
    Object.assign(form, { productName: declination.productName, sellingPrice: declination.sellingPrice, buyingPrice: declination.buyingPrice, variants: [...declination.variants] });
}, { immediate: true });

const dirty = computed(() => form.productName !== props.declination.productName
    || form.sellingPrice !== props.declination.sellingPrice
    || form.buyingPrice !== props.declination.buyingPrice
    || form.variants.join('|') !== props.declination.variants.join('|'));
const done = (adaptation) => props.declination.doneAdaptations.includes(adaptation);
const typePrefix = computed(() => props.declination.gabarit.typeName);

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        await props.submit({ ...form, sellingPrice: form.sellingPrice ?? -1, buyingPrice: form.buyingPrice ?? 0 });
    } catch (error) {
        errors.value = error.fieldErrors ?? { productName: error.message };
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <article :class="['declination', { 'declination--ready': declination.ready }]" :aria-label="declination.gabarit.name">
        <header class="declination__header">
            <div>
                <h3 class="declination__gabarit">{{ declination.gabarit.name }}</h3>
                <p class="declination__product">{{ declination.displayName }}</p>
            </div>
            <StatusBadge v-if="declination.productId" tone="success">Produit créé</StatusBadge>
            <StatusBadge v-else-if="declination.ready" tone="success">Prête</StatusBadge>
            <StatusBadge v-else tone="warning">{{ declination.adaptations.length - declination.doneAdaptations.length }} à adapter</StatusBadge>
            <ConfirmButton
                v-if="!locked"
                :icon="Trash2"
                :label="`Retirer la déclinaison ${declination.gabarit.name}`"
                message="Les adaptations cochées pour ce gabarit seront perdues."
                @confirm="emit('withdraw')"
            />
        </header>

        <section v-if="declination.adaptations.length" class="declination__checklist" :aria-label="`Adaptations ${declination.gabarit.name}`">
            <label v-for="adaptation in declination.adaptations" :key="adaptation" :class="['declination__adaptation', { 'declination__adaptation--done': done(adaptation) }]">
                <BaseCheckbox :model-value="done(adaptation)" :disabled="locked" @update:model-value="emit('tick', adaptation, $event)" />
                {{ adaptation }}
            </label>
        </section>
        <p v-else class="declination__none">Aucune adaptation demandée par ce gabarit.</p>

        <dl v-if="locked" class="declination__facts">
            <div><dt>Vente</dt><dd><MoneyAmount :cents="declination.sellingPrice" /></dd></div>
            <div><dt>Achat</dt><dd><MoneyAmount :cents="declination.buyingPrice" /></dd></div>
            <div><dt>Variantes</dt><dd>{{ declination.variants.join(', ') || 'Unique' }}</dd></div>
        </dl>
        <form v-else class="declination__form" novalidate @submit.prevent="onSubmit">
            <fieldset class="form-lock" :disabled="saving">
                <FormField label="Nom du produit" :error="errors.productName" :hint="typePrefix ? `Affiché « ${typePrefix} ${form.productName} »` : null">
                    <input v-model="form.productName" type="text">
                </FormField>
                <div class="declination__row">
                    <FormField label="Vente (€)" :error="errors.sellingPrice">
                        <BaseMoneyField v-model="form.sellingPrice" />
                    </FormField>
                    <FormField label="Achat (€)" :error="errors.buyingPrice">
                        <BaseMoneyField v-model="form.buyingPrice" />
                    </FormField>
                </div>
                <FormField as="group" label="Variantes" :error="errors.variants">
                    <VariantsInput v-model="form.variants" />
                </FormField>
                <div v-if="dirty" class="declination__actions">
                    <BaseButton type="submit" variant="secondary" :loading="saving">Enregistrer la déclinaison</BaseButton>
                </div>
            </fieldset>
        </form>
    </article>
</template>

<style scoped>
.declination { display: flex; flex-direction: column; gap: var(--space-3); padding: var(--space-4); border: 0.0625rem solid var(--color-border); border-top: 0.25rem solid var(--color-warning); border-radius: var(--radius); background: var(--color-surface); }
.declination--ready { border-top-color: var(--color-accent); }
.declination__header { display: flex; align-items: flex-start; gap: var(--space-2); }
.declination__header > div { flex: 1; }
.declination__gabarit { margin: 0; font-size: 1.1rem; }
.declination__product { margin: 0; color: var(--color-muted); font-size: 0.85rem; }
.declination__checklist { display: flex; flex-direction: column; gap: var(--space-1); padding: var(--space-3); border-radius: var(--radius); background: var(--color-bg); }
.declination__adaptation { display: flex; align-items: center; gap: var(--space-2); cursor: pointer; }
.declination__adaptation--done { color: var(--color-muted); text-decoration: line-through; }
.declination__none { margin: 0; color: var(--color-subtle); font-size: 0.85rem; }
.declination__form { display: flex; flex-direction: column; gap: var(--space-3); }
.declination__row { display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-3); }
.declination__actions { display: flex; justify-content: flex-end; }
.declination__facts { display: flex; gap: var(--space-5); margin: 0; }
.declination__facts dt { color: var(--color-muted); font-size: 0.8rem; }
.declination__facts dd { margin: 0; }
</style>
