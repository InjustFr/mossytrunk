<template>
    <p class="empty-state"><slot /></p>
</template>

<style scoped>
.empty-state {
    margin: 0;
    padding: var(--space-5);
    text-align: center;
    color: var(--color-muted);
    border: 1px dashed var(--color-border);
    border-radius: var(--radius);
}
</style>
