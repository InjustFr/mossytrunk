<template>
    <span class="field-error" role="alert"><slot /></span>
</template>

<style scoped>
.field-error { display: block; color: var(--color-danger); font-size: 0.85rem; }
</style>
