<script setup>
import { reactive, ref } from 'vue';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import BaseDatePicker from '../ui/BaseDatePicker.vue';
import BaseMoneyField from '../ui/BaseMoneyField.vue';
import ConfirmButton from '../ui/ConfirmButton.vue';
import FieldError from '../ui/FieldError.vue';
import FormActions from '../ui/FormActions.vue';
import FormField from '../ui/FormField.vue';
import FormSection from '../ui/FormSection.vue';
import IconButton from '../ui/IconButton.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import { formatDate } from '../../composables/useDate.js';
import { formatSignedCents } from '../../composables/useMoney.js';

const props = defineProps({
    history: { type: Array, required: true },
    save: { type: Function, required: true },
    forget: { type: Function, required: true },
});

const { t } = useI18n();

const NEW = '__new__';
const editing = ref(null);
const form = reactive({ price: null, since: '' });
const errors = ref({});
const saving = ref(false);

function edit(change = null) {
    editing.value = change?.id ?? NEW;
    Object.assign(form, { price: change?.price ?? null, since: change?.sinceDay ?? '' });
    errors.value = {};
}

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        await props.save(editing.value === NEW ? null : editing.value, { price: form.price ?? -1, since: form.since });
        editing.value = null;
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
    <div class="price-history">
        <ol class="price-history__list">
            <li v-for="(change, index) in history" :key="change.id" :class="['price-history__change', { 'price-history__change--current': index === 0 }]">
                <form v-if="editing === change.id" class="price-history__form" novalidate @submit.prevent="onSubmit">
                    <FieldError v-if="errors.form">{{ errors.form }}</FieldError>
                    <FormSection>
                        <FormField :label="t('products.prices.price')" :error="errors.price">
                            <BaseMoneyField v-model="form.price" class="price-history__amount" />
                        </FormField>
                        <FormField as="group" :label="t('products.prices.since')" :error="errors.since">
                            <BaseDatePicker v-model="form.since" :aria-label="t('products.prices.since')" />
                        </FormField>
                    </FormSection>
                    <FormActions>
                        <template v-if="index === 0" #note>{{ t('products.prices.currentHint') }}</template>
                        <BaseButton variant="ghost" @click="editing = null">{{ t('products.cancel') }}</BaseButton>
                        <BaseButton type="submit" :loading="saving">{{ t('products.save') }}</BaseButton>
                    </FormActions>
                </form>
                <template v-else>
                    <span class="price-history__price"><MoneyAmount :cents="change.price" /></span>
                    <span v-if="history[index + 1]" :class="['price-history__delta', change.price > history[index + 1].price ? 'price-history__delta--up' : 'price-history__delta--down']">
                        {{ formatSignedCents(change.price - history[index + 1].price) }}
                    </span>
                    <span class="price-history__tools">
                        <IconButton :icon="Pencil" :label="t('products.prices.edit', { date: formatDate(change.since) })" @click="edit(change)" />
                        <ConfirmButton
                            v-if="history.length > 1"
                            :icon="Trash2"
                            :label="t('products.prices.remove', { date: formatDate(change.since) })"
                            :message="t(index === 0 ? 'products.prices.removeCurrent' : 'products.prices.removePast')"
                            @confirm="forget(change.id)"
                        />
                    </span>
                    <span class="price-history__since">{{ t(index === 0 ? 'products.prices.sinceDate' : 'products.prices.onDate', { date: formatDate(change.since) }) }}</span>
                </template>
            </li>
        </ol>

        <form v-if="editing === NEW" class="price-history__form price-history__form--new" novalidate @submit.prevent="onSubmit">
            <FieldError v-if="errors.form">{{ errors.form }}</FieldError>
            <FormSection>
                <FormField :label="t('products.prices.price')" :error="errors.price">
                    <BaseMoneyField v-model="form.price" class="price-history__amount" />
                </FormField>
                <FormField as="group" :label="t('products.prices.since')" :error="errors.since">
                    <BaseDatePicker v-model="form.since" :aria-label="t('products.prices.since')" />
                </FormField>
            </FormSection>
            <FormActions>
                <template #note>{{ t('products.prices.newHint') }}</template>
                <BaseButton variant="ghost" @click="editing = null">{{ t('products.cancel') }}</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ t('products.prices.add') }}</BaseButton>
            </FormActions>
        </form>
        <BaseButton v-else variant="ghost" class="price-history__add" @click="edit()"><Plus size="0.875rem" aria-hidden="true" /> {{ t('products.prices.addPast') }}</BaseButton>
    </div>
</template>

<style scoped>
.price-history { display: flex; flex-direction: column; gap: var(--space-3); }
.price-history__list { display: flex; flex-direction: column; margin: 0; padding: 0 0 0 var(--space-3); border-left: 0.125rem solid var(--color-border); list-style: none; }
.price-history__change { position: relative; display: flex; align-items: baseline; flex-wrap: wrap; gap: var(--space-2); padding: var(--space-2) 0; color: var(--color-muted); }
.price-history__change::before { content: ""; position: absolute; top: 1rem; left: calc(-1 * var(--space-3) - 0.3125rem); width: 0.5rem; height: 0.5rem; border-radius: 50%; background: var(--color-border-strong); }
.price-history__change--current { color: var(--color-ink); }
.price-history__change--current::before { background: var(--color-accent); }
.price-history__change--current .price-history__price { font-family: var(--font-display); font-size: 1.6rem; }
.price-history__price { font-variant-numeric: tabular-nums; }
.price-history__delta { font-size: var(--font-size-sm); font-variant-numeric: tabular-nums; }
.price-history__delta--up { color: var(--color-accent-strong); }
.price-history__delta--down { color: var(--color-danger); }
.price-history__tools { display: inline-flex; margin-left: auto; opacity: 0.5; transition: opacity var(--transition); }
.price-history__change:hover .price-history__tools,
.price-history__change:focus-within .price-history__tools { opacity: 1; }
.price-history__since { width: 100%; font-size: var(--font-size-sm); }
.price-history__form { display: flex; flex-direction: column; gap: var(--space-4); width: 100%; padding: var(--space-2) 0; color: var(--color-text); }
.price-history__form--new { padding: var(--space-4); border-radius: var(--radius); background: var(--color-bg); }
.price-history__amount { max-width: 11rem; }
.price-history__add { align-self: flex-start; }
</style>
