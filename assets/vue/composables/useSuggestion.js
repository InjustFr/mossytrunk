import { ref, watch } from 'vue';

const DELAY = 250;

export function useSuggestion(target, source, fetchSuggestion, { follow = true } = {}) {
    const following = ref(follow);
    let timer = null;
    let latest = 0;

    async function refresh() {
        const call = ++latest;
        const suggestion = await fetchSuggestion(source()).catch(() => null);
        if (call === latest && following.value && suggestion !== null) {
            target.value = suggestion;
        }
    }

    watch(source, () => {
        clearTimeout(timer);
        if (following.value) timer = setTimeout(refresh, DELAY);
    }, { immediate: follow });

    function edited() {
        following.value = target.value.trim() === '';
        if (following.value) refresh();
    }

    function reset(shouldFollow) {
        following.value = shouldFollow;
        if (shouldFollow) refresh();
    }

    return { edited, reset };
}
