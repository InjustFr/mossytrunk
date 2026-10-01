import { computed } from 'vue';
import { formatDay, formatNumericDay, formatTime } from './useDate.js';
import { queryFlag, queryText } from './useQueryState.js';

const normalize = (text) => text.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase();

function searchableText(order) {
    const time = formatTime(order.placedAt);
    return normalize([
        order.reference,
        ...order.externalReferences,
        order.placedAt.slice(0, 10),
        formatNumericDay(order.placedAt),
        formatDay(order.placedAt),
        time,
        time.replace(':', 'h'),
    ].join(' '));
}

export function useOrderSearch(orders) {
    const search = queryText('search');
    const unassigned = queryFlag('unassigned', 'yes');

    const unassignedCount = computed(() => orders.value.filter((order) => order.unidentifiedLines > 0).length);

    const visible = computed(() => {
        const terms = normalize(search.value).split(/\s+/).filter(Boolean);
        return orders.value.filter((order) => (!unassigned.value || order.unidentifiedLines > 0)
            && (terms.length === 0 || terms.every((term) => searchableText(order).includes(term))));
    });

    const filtering = computed(() => search.value !== '' || unassigned.value);

    return { search, unassigned, unassignedCount, visible, filtering };
}
