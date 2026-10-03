<script setup>
import { computed } from 'vue';
import { ChevronRight } from '@lucide/vue';
import { CollapsibleContent, CollapsibleRoot, CollapsibleTrigger } from 'reka-ui';
import { useI18n } from 'vue-i18n';
import NotebookOrder from './NotebookOrder.vue';
import NotebookSale from './NotebookSale.vue';

const props = defineProps({
    report: { type: Object, required: true },
});
const { t } = useI18n();

const tiles = computed(() => [
    { key: 'notebookSales', value: props.report.summary.entries },
    { key: 'orders', value: props.report.summary.orders },
    { key: 'matching', value: props.report.summary.matching, tone: 'success' },
    { key: 'differing', value: props.report.summary.differing, tone: 'warning' },
    { key: 'notRecorded', value: props.report.summary.notRecorded, tone: 'danger' },
    { key: 'notNoted', value: props.report.summary.notNoted, tone: 'danger' },
]);
const allMatching = computed(() => props.report.summary.differing + props.report.summary.notRecorded + props.report.summary.notNoted === 0);
</script>

<template>
    <div class="notebook-report">
        <dl class="notebook-report__tiles">
            <div v-for="tile in tiles" :key="tile.key" :class="['notebook-report__tile', { [`notebook-report__tile--${tile.tone}`]: tile.tone && tile.value > 0 }]">
                <dt class="notebook-report__tile-label">{{ t(`notebook.report.${tile.key}`) }}</dt>
                <dd class="notebook-report__tile-value">{{ tile.value }}</dd>
            </div>
        </dl>

        <p v-if="allMatching" class="notebook-report__all-matching">{{ t('notebook.report.allMatching') }}</p>

        <section v-if="report.notRecorded.length" class="notebook-report__section notebook-report__section--danger" aria-labelledby="notebook-not-recorded">
            <h2 id="notebook-not-recorded" class="notebook-report__title">{{ t('notebook.report.notRecordedTitle') }}</h2>
            <p class="notebook-report__hint">{{ t('notebook.report.notRecordedHint') }}</p>
            <ul class="notebook-report__items">
                <li v-for="entry in report.notRecorded" :key="entry.number" class="notebook-report__item"><NotebookSale :entry="entry" /></li>
            </ul>
        </section>

        <section v-if="report.notNoted.length" class="notebook-report__section notebook-report__section--danger" aria-labelledby="notebook-not-noted">
            <h2 id="notebook-not-noted" class="notebook-report__title">{{ t('notebook.report.notNotedTitle') }}</h2>
            <p class="notebook-report__hint">{{ t('notebook.report.notNotedHint') }}</p>
            <ul class="notebook-report__items">
                <li v-for="order in report.notNoted" :key="order.id" class="notebook-report__item"><NotebookOrder :order="order" /></li>
            </ul>
        </section>

        <section v-if="report.differing.length" class="notebook-report__section notebook-report__section--warning" aria-labelledby="notebook-differing">
            <h2 id="notebook-differing" class="notebook-report__title">{{ t('notebook.report.differingTitle') }}</h2>
            <p class="notebook-report__hint">{{ t('notebook.report.differingHint') }}</p>
            <ul class="notebook-report__items">
                <li v-for="pairing in report.differing" :key="pairing.entry.number" class="notebook-report__item notebook-report__item--pair">
                    <NotebookSale :entry="pairing.entry" />
                    <NotebookOrder :order="pairing.order" />
                    <dl class="notebook-report__gaps">
                        <template v-if="pairing.onlyNoted.length">
                            <dt>{{ t('notebook.report.onlyNoted') }}</dt>
                            <dd v-for="gap in pairing.onlyNoted" :key="`noted-${gap.label}`">{{ gap.quantity }} × {{ gap.label }}</dd>
                        </template>
                        <template v-if="pairing.onlySold.length">
                            <dt>{{ t('notebook.report.onlySold') }}</dt>
                            <dd v-for="gap in pairing.onlySold" :key="`sold-${gap.label}`">{{ gap.quantity }} × {{ gap.label }}</dd>
                        </template>
                    </dl>
                </li>
            </ul>
        </section>

        <CollapsibleRoot v-if="report.matching.length" class="notebook-report__section">
            <CollapsibleTrigger class="notebook-report__toggle">
                <ChevronRight class="notebook-report__chevron" size="1rem" aria-hidden="true" />
                {{ t('notebook.report.matchingTitle') }} ({{ report.matching.length }})
            </CollapsibleTrigger>
            <CollapsibleContent>
                <ul class="notebook-report__items">
                    <li v-for="pairing in report.matching" :key="pairing.entry.number" class="notebook-report__item notebook-report__item--pair">
                        <NotebookSale :entry="pairing.entry" />
                        <NotebookOrder :order="pairing.order" />
                    </li>
                </ul>
            </CollapsibleContent>
        </CollapsibleRoot>
    </div>
</template>

<style scoped>
.notebook-report { display: flex; flex-direction: column; gap: var(--space-5); }

.notebook-report__tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(8.5rem, 1fr)); gap: var(--space-3); margin: 0; }
.notebook-report__tile { padding: var(--space-3) var(--space-4); border: 0.0625rem solid var(--color-border); border-radius: var(--radius); background: var(--color-surface); }
.notebook-report__tile-label { color: var(--color-muted); font-size: 0.72rem; font-weight: 600; letter-spacing: 0.04rem; text-transform: uppercase; }
.notebook-report__tile-value { margin: var(--space-1) 0 0; color: var(--color-ink); font-family: var(--font-display); font-size: 1.6rem; }
.notebook-report__tile--success .notebook-report__tile-value { color: var(--color-accent-strong); }
.notebook-report__tile--warning .notebook-report__tile-value { color: var(--color-warning); }
.notebook-report__tile--danger .notebook-report__tile-value { color: var(--color-danger); }

.notebook-report__all-matching { margin: 0; padding: var(--space-3) var(--space-4); border-radius: var(--radius); background: var(--color-accent-soft); color: var(--color-accent-strong); }

.notebook-report__section { padding: var(--space-4) var(--space-5); border: 0.0625rem solid var(--color-border); border-radius: var(--radius); background: var(--color-surface); }
.notebook-report__section--danger { border-left: 0.25rem solid var(--color-danger); }
.notebook-report__section--warning { border-left: 0.25rem solid var(--color-warning); }
.notebook-report__title { margin: 0; font-size: 1.1rem; }
.notebook-report__hint { margin: var(--space-1) 0 var(--space-3); color: var(--color-muted); font-size: 0.9rem; }

.notebook-report__items { display: flex; flex-direction: column; margin: 0; padding: 0; list-style: none; }
.notebook-report__item { padding: var(--space-3) 0; border-top: 0.0625rem solid var(--color-border); }
.notebook-report__item:first-child { border-top: none; }
.notebook-report__item--pair { display: grid; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr)); gap: var(--space-3) var(--space-5); }

.notebook-report__gaps { display: grid; grid-template-columns: auto 1fr; align-content: start; gap: var(--space-1) var(--space-3); margin: 0; font-size: 0.9rem; }
.notebook-report__gaps dt { grid-column: 1; color: var(--color-muted); font-size: 0.72rem; font-weight: 600; letter-spacing: 0.04rem; text-transform: uppercase; }
.notebook-report__gaps dd { grid-column: 2; margin: 0; }

.notebook-report__toggle { display: flex; align-items: center; gap: var(--space-2); width: 100%; padding: 0; border: none; background: none; color: var(--color-ink); font-size: 1.1rem; font-weight: 600; text-align: left; cursor: pointer; }
.notebook-report__chevron { color: var(--color-muted); transition: transform var(--transition); }
.notebook-report__toggle[data-state='open'] .notebook-report__chevron { transform: rotate(90deg); }
.notebook-report__toggle[data-state='open'] { margin-bottom: var(--space-3); }
</style>
