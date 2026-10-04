import { computed, ref } from 'vue';

export function useModalTarget() {
    const target = ref(null);
    const open = computed({
        get: () => target.value !== null,
        set: (isOpen) => { if (!isOpen) target.value = null; },
    });
    return { target, open };
}
