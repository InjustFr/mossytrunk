<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import BaseMoneyField from '../ui/BaseMoneyField.vue';
import BaseSwitch from '../ui/BaseSwitch.vue';
import FormActions from '../ui/FormActions.vue';
import FormError from '../ui/FormError.vue';
import FormField from '../ui/FormField.vue';
import FormSection from '../ui/FormSection.vue';
import { ownPriceOn, priceOn } from '../../composables/useChannelPrices.js';

const props = defineProps({
    product: { type: Object, required: true },
    channel: { type: Object, required: true },
    main: { type: Object, required: true },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel']);
const { t } = useI18n();

const follow = ref(!props.channel.main && ownPriceOn(props.product, props.channel) === null);
const price = ref(priceOn(props.product, props.channel));
const errors = ref({});
const saving = ref(false);
const shownPrice = computed(() => (follow.value ? props.product.sellingPrice : price.value));

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        await props.submit(follow.value ? null : price.value ?? -1);
        emit('saved', props.product.displayName);
    } catch (error) {
        errors.value = Object.keys(error.fieldErrors ?? {}).length ? error.fieldErrors : { form: error.message };
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="channel-price-form" novalidate @submit.prevent="onSubmit">
        <fieldset class="form-lock" :disabled="saving">
            <FormError v-if="errors.form">{{ errors.form }}</FormError>
            <FormSection>
                <label v-if="!channel.main" class="channel-price-form__follow">
                    <BaseSwitch v-model="follow" />
                    {{ t('channels.prices.follow', { main: main.name }) }}
                </label>
                <FormField :label="t('channels.prices.amount')" :error="errors.price">
                    <BaseMoneyField
                        :model-value="shownPrice"
                        :disabled="follow"
                        class="channel-price-form__amount"
                        @update:model-value="(value) => (price = value)"
                    />
                </FormField>
            </FormSection>
            <FormActions>
                <BaseButton variant="ghost" @click="emit('cancel')">{{ t('channels.prices.cancel') }}</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ t('channels.prices.save') }}</BaseButton>
            </FormActions>
        </fieldset>
    </form>
</template>

<style scoped>
.channel-price-form { display: flex; flex-direction: column; gap: var(--space-5); }
.channel-price-form__follow { display: flex; align-items: center; gap: var(--space-2); font-size: 0.875rem; cursor: pointer; }
.channel-price-form__amount { max-width: 11rem; }
</style>
