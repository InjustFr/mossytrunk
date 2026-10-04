<script setup>
import { reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import FormActions from '../ui/FormActions.vue';
import FormError from '../ui/FormError.vue';
import BatchChannelPrice from '../products/BatchChannelPrice.vue';
import { channelPriceChangePayload, emptyChannelPriceChange } from '../../composables/useChannelPrices.js';

const props = defineProps({
    channel: { type: Object, required: true },
    channels: { type: Array, required: true },
    products: { type: Array, required: true },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel']);
const { t } = useI18n();

const change = reactive({ ...emptyChannelPriceChange(), target: props.channel.id });
const errors = ref({});
const saving = ref(false);

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        const result = await props.submit({ productIds: props.products.map((product) => product.id), channelPrice: channelPriceChangePayload(change) });
        emit('saved', result.updated);
    } catch (error) {
        errors.value = Object.keys(error.fieldErrors ?? {}).length ? error.fieldErrors : { form: error.message };
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="channel-batch-price-form" novalidate @submit.prevent="onSubmit">
        <fieldset class="form-lock" :disabled="saving">
            <FormError v-if="errors.form">{{ errors.form }}</FormError>
            <p class="channel-batch-price-form__intro">{{ t('channels.prices.batchIntro', products.length) }}</p>
            <BatchChannelPrice v-model="change" :channels="channels" :products="products" :errors="errors" fixed-target />
            <FormActions>
                <BaseButton variant="ghost" @click="emit('cancel')">{{ t('channels.prices.cancel') }}</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ t('channels.prices.apply', products.length) }}</BaseButton>
            </FormActions>
        </fieldset>
    </form>
</template>

<style scoped>
.channel-batch-price-form { display: flex; flex-direction: column; gap: var(--space-5); }
.channel-batch-price-form__intro { margin: 0; color: var(--color-muted); font-size: 0.8125rem; }
</style>
