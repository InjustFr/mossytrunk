import { reactive } from 'vue';

const state = reactive({ toasts: [] });
let nextId = 1;

const DURATION = { success: 3500, error: 7000, info: 4000 };

function push(type, message) {
    state.toasts.push({ id: nextId++, type, message, duration: DURATION[type] });
}

function dismiss(id) {
    const index = state.toasts.findIndex((toast) => toast.id === id);
    if (index !== -1) {
        state.toasts.splice(index, 1);
    }
}

export function useToast() {
    return {
        toasts: state.toasts,
        success: (message) => push('success', message),
        error: (message) => push('error', message),
        info: (message) => push('info', message),
        dismiss,
    };
}
