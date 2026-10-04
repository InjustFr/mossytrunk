import { onScopeDispose, ref, watch } from 'vue';

export function useDebouncedQuery(source, fetcher, { delay, deep = false, immediate = false, enabled = () => true, initial = null, onSettled = () => {} } = {}) {
    const result = ref(initial);
    const error = ref(null);
    let timer = null;
    let latest = 0;

    function settle(call, value, failure) {
        if (call !== latest) return;
        result.value = value;
        error.value = failure;
        onSettled({ result: value, error: failure });
    }

    async function refresh() {
        const call = ++latest;
        let value = null;
        let failure = null;
        try {
            value = await fetcher();
        } catch (caught) {
            failure = caught;
        }
        settle(call, value, failure);
    }

    function invalidate() {
        latest++;
    }

    watch(source, () => {
        clearTimeout(timer);
        if (enabled()) timer = setTimeout(refresh, delay);
    }, { deep, immediate });

    onScopeDispose(() => clearTimeout(timer));

    return { result, error, refresh, invalidate };
}
