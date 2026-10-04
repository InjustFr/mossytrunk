<script setup>
import { computed } from 'vue';
import { parseDate, parseDateTime } from '@internationalized/date';
import { CalendarDays, ChevronLeft, ChevronRight } from '@lucide/vue';
import {
    DatePickerCalendar,
    DatePickerCell,
    DatePickerCellTrigger,
    DatePickerContent,
    DatePickerField,
    DatePickerGrid,
    DatePickerGridBody,
    DatePickerGridHead,
    DatePickerGridRow,
    DatePickerHeadCell,
    DatePickerHeader,
    DatePickerHeading,
    DatePickerInput,
    DatePickerNext,
    DatePickerPrev,
    DatePickerRoot,
    DatePickerTrigger,
} from 'reka-ui';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    withTime: { type: Boolean, default: false },
    invalid: { type: Boolean, default: false },
});
const model = defineModel({ type: String, default: '' });

const date = computed({
    get: () => {
        if (!model.value) {
            return undefined;
        }
        return props.withTime ? parseDateTime(model.value) : parseDate(model.value);
    },
    set: (value) => {
        model.value = value ? value.toString().slice(0, props.withTime ? 16 : 10) : '';
    },
});
</script>

<template>
    <DatePickerRoot
        v-model="date"
        :granularity="withTime ? 'minute' : 'day'"
        :hour-cycle="24"
        :week-starts-on="1"
        fixed-weeks
    >
        <DatePickerField v-slot="{ segments }" class="control date-picker__field" :data-invalid="invalid || undefined">
            <template v-for="item in segments" :key="item.part">
                <DatePickerInput v-if="item.part === 'literal'" :part="item.part" class="date-picker__literal">{{ item.value }}</DatePickerInput>
                <DatePickerInput v-else :part="item.part" class="date-picker__segment">{{ item.value }}</DatePickerInput>
            </template>
            <DatePickerTrigger class="date-picker__trigger" :aria-label="t('ui.datePicker.open')">
                <CalendarDays size="1rem" aria-hidden="true" />
            </DatePickerTrigger>
        </DatePickerField>

        <DatePickerContent class="popover date-picker__content" :side-offset="4" align="start">
            <DatePickerCalendar v-slot="{ weekDays, grid }">
                <DatePickerHeader class="date-picker__header">
                    <DatePickerPrev class="date-picker__nav" :aria-label="t('ui.datePicker.previousMonth')"><ChevronLeft size="1rem" aria-hidden="true" /></DatePickerPrev>
                    <DatePickerHeading class="date-picker__heading" />
                    <DatePickerNext class="date-picker__nav" :aria-label="t('ui.datePicker.nextMonth')"><ChevronRight size="1rem" aria-hidden="true" /></DatePickerNext>
                </DatePickerHeader>
                <DatePickerGrid v-for="month in grid" :key="month.value.toString()" class="date-picker__grid">
                    <DatePickerGridHead>
                        <DatePickerGridRow>
                            <DatePickerHeadCell v-for="day in weekDays" :key="day" class="date-picker__head-cell">{{ day }}</DatePickerHeadCell>
                        </DatePickerGridRow>
                    </DatePickerGridHead>
                    <DatePickerGridBody>
                        <DatePickerGridRow v-for="(week, index) in month.rows" :key="`week-${index}`">
                            <DatePickerCell v-for="day in week" :key="day.toString()" :date="day" class="date-picker__cell">
                                <DatePickerCellTrigger :day="day" :month="month.value" class="date-picker__day date-picker__day--single" />
                            </DatePickerCell>
                        </DatePickerGridRow>
                    </DatePickerGridBody>
                </DatePickerGrid>
            </DatePickerCalendar>
        </DatePickerContent>
    </DatePickerRoot>
</template>

<style src="../../../styles/date-picker.css"></style>
