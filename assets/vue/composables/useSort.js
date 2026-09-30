import { computed, ref } from 'vue';
import { perLocale } from '../i18n/locale.js';

const collator = perLocale((locale) => new Intl.Collator(locale, { numeric: true, sensitivity: 'base' }));

export function useSort(items, columns, initialKey, initialDirection = 'descending') {
    const key = ref(initialKey);
    const direction = ref(initialDirection);

    const sorted = computed(() => {
        const value = columns[key.value];
        const factor = direction.value === 'ascending' ? 1 : -1;
        return [...items.value].sort((a, b) => {
            const left = value(a);
            const right = value(b);
            const order = typeof left === 'string' ? collator().compare(left, right) : (left ?? -Infinity) - (right ?? -Infinity);
            return order * factor;
        });
    });

    function sortBy(column) {
        if (key.value === column) {
            direction.value = direction.value === 'ascending' ? 'descending' : 'ascending';
            return;
        }
        key.value = column;
        direction.value = typeof columns[column](items.value[0] ?? {}) === 'string' ? 'ascending' : 'descending';
    }

    const ariaSort = (column) => (key.value === column ? direction.value : 'none');

    return { sorted, sortKey: key, direction, sortBy, ariaSort };
}
