import { ref } from 'vue';

export function formErrors(error, fallbackField = 'form') {
    const fields = error.fieldErrors ?? {};
    return Object.keys(fields).length > 0 ? fields : { [fallbackField]: error.message };
}

export function useFormSubmit(fallbackField = 'form', { byField = true } = {}) {
    const saving = ref(false);
    const errors = ref({});

    async function run(action) {
        saving.value = true;
        errors.value = {};
        try {
            await action();
            return true;
        } catch (error) {
            errors.value = byField ? formErrors(error, fallbackField) : { [fallbackField]: error.message };
            return false;
        } finally {
            saving.value = false;
        }
    }

    return { saving, errors, run };
}
