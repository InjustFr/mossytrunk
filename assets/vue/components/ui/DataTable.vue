<script setup>
// Thin table wrapper giving consistent styling; rows/cells come from slots.
</script>

<template>
    <div class="data-table">
        <table class="data-table__table">
            <thead class="data-table__head"><slot name="head" /></thead>
            <tbody class="data-table__body"><slot /></tbody>
            <tfoot v-if="$slots.foot" class="data-table__foot"><slot name="foot" /></tfoot>
        </table>
    </div>
</template>

<style scoped>
.data-table { overflow-x: auto; }

.data-table__table { width: 100%; border-collapse: collapse; }

.data-table :deep(th),
.data-table :deep(td) {
    padding: var(--space-2) var(--space-3);
    text-align: left;
    border-bottom: 1px solid var(--color-border);
    vertical-align: middle;
}

.data-table :deep(th) {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: var(--color-muted);
    border-bottom-color: var(--color-border-strong);
}

.data-table__body :deep(tr) { transition: background var(--transition); }
.data-table__body :deep(tr:hover) { background: #fafaf8; }

.data-table :deep(.data-table__cell--number) { text-align: right; font-variant-numeric: tabular-nums; }

.data-table__foot :deep(td) { font-weight: 700; border-bottom: none; }
</style>
