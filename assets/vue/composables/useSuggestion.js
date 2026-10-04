import { ref } from 'vue';
import { useDebouncedQuery } from './useDebouncedQuery.js';

const DELAY = 250;

export function useSuggestion(target, source, fetchSuggestion, { follow = true } = {}) {
    const following = ref(follow);

    async function suggest() {
        const before = target.value;
        const suggestion = await fetchSuggestion(source()).catch(() => null);
        return { before, suggestion };
    }

    function apply({ result: { before, suggestion } }) {
        if (following.value && suggestion !== null && target.value === before) {
            target.value = suggestion;
        }
    }

    const { refresh, invalidate } = useDebouncedQuery(source, suggest, {
        delay: DELAY,
        immediate: follow,
        enabled: () => following.value,
        onSettled: apply,
    });

    function edited(value = target.value) {
        invalidate();
        following.value = value.trim() === '';
    }

    function reset(shouldFollow) {
        following.value = shouldFollow;
        if (shouldFollow) refresh();
    }

    return { edited, reset };
}
