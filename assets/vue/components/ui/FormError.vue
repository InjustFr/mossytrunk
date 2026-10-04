<template>
    <p class="form-error" role="alert"><slot /></p>
</template>

<style scoped>
.form-error {
    margin: 0;
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius);
    background: var(--color-danger-soft);
    color: var(--color-danger);
}
</style>
