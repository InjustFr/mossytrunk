<script setup>
import { computed } from 'vue';
import { parseDate } from '@internationalized/date';
import { CalendarDays, ChevronLeft, ChevronRight } from '@lucide/vue';
import {
    DateRangePickerCalendar,
    DateRangePickerCell,
    DateRangePickerCellTrigger,
    DateRangePickerContent,
    DateRangePickerField,
    DateRangePickerGrid,
    DateRangePickerGridBody,
    DateRangePickerGridHead,
    DateRangePickerGridRow,
    DateRangePickerHeadCell,
    DateRangePickerHeader,
    DateRangePickerHeading,
    DateRangePickerInput,
    DateRangePickerNext,
    DateRangePickerPrev,
    DateRangePickerRoot,
    DateRangePickerTrigger,
} from 'reka-ui';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
    invalid: { type: Boolean, default: false },
});
const start = defineModel('start', { type: String, default: '' });
const end = defineModel('end', { type: String, default: '' });

const toDate = (iso) => (iso ? parseDate(iso) : undefined);

const range = computed({
    get: () => ({ start: toDate(start.value), end: toDate(end.value) }),
    set: (value) => {
        start.value = value?.start ? value.start.toString() : '';
        end.value = value?.end ? value.end.toString() : '';
    },
});
</script>

<template>
    <DateRangePickerRoot v-model="range" :week-starts-on="1" fixed-weeks>
        <DateRangePickerField v-slot="{ segments }" class="control date-picker__field" :data-invalid="invalid || undefined">
            <template v-for="item in segments.start" :key="`start-${item.part}`">
                <DateRangePickerInput v-if="item.part === 'literal'" :part="item.part" type="start" class="date-picker__literal">{{ item.value }}</DateRangePickerInput>
                <DateRangePickerInput v-else :part="item.part" type="start" class="date-picker__segment">{{ item.value }}</DateRangePickerInput>
            </template>
            <span class="date-picker__literal" aria-hidden="true">→</span>
            <template v-for="item in segments.end" :key="`end-${item.part}`">
                <DateRangePickerInput v-if="item.part === 'literal'" :part="item.part" type="end" class="date-picker__literal">{{ item.value }}</DateRangePickerInput>
                <DateRangePickerInput v-else :part="item.part" type="end" class="date-picker__segment">{{ item.value }}</DateRangePickerInput>
            </template>
            <DateRangePickerTrigger class="date-picker__trigger" :aria-label="t('ui.datePicker.open')">
                <CalendarDays size="1rem" aria-hidden="true" />
            </DateRangePickerTrigger>
        </DateRangePickerField>

        <DateRangePickerContent class="popover date-picker__content" :side-offset="4" align="start">
            <DateRangePickerCalendar v-slot="{ weekDays, grid }">
                <DateRangePickerHeader class="date-picker__header">
                    <DateRangePickerPrev class="date-picker__nav" :aria-label="t('ui.datePicker.previousMonth')"><ChevronLeft size="1rem" aria-hidden="true" /></DateRangePickerPrev>
                    <DateRangePickerHeading class="date-picker__heading" />
                    <DateRangePickerNext class="date-picker__nav" :aria-label="t('ui.datePicker.nextMonth')"><ChevronRight size="1rem" aria-hidden="true" /></DateRangePickerNext>
                </DateRangePickerHeader>
                <DateRangePickerGrid v-for="month in grid" :key="month.value.toString()" class="date-picker__grid">
                    <DateRangePickerGridHead>
                        <DateRangePickerGridRow>
                            <DateRangePickerHeadCell v-for="day in weekDays" :key="day" class="date-picker__head-cell">{{ day }}</DateRangePickerHeadCell>
                        </DateRangePickerGridRow>
                    </DateRangePickerGridHead>
                    <DateRangePickerGridBody>
                        <DateRangePickerGridRow v-for="(week, index) in month.rows" :key="`week-${index}`">
                            <DateRangePickerCell v-for="day in week" :key="day.toString()" :date="day" class="date-picker__cell">
                                <DateRangePickerCellTrigger :day="day" :month="month.value" class="date-picker__day" />
                            </DateRangePickerCell>
                        </DateRangePickerGridRow>
                    </DateRangePickerGridBody>
                </DateRangePickerGrid>
            </DateRangePickerCalendar>
        </DateRangePickerContent>
    </DateRangePickerRoot>
</template>

<style src="../../../styles/date-picker.css"></style>
