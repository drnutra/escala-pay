<script setup>
defineProps({
    /** sm = tabs/sidebar; xs = tighter inline */
    size: { type: String, default: 'sm' },
});
</script>

<template>
    <span
        class="inline-flex shrink-0 items-center rounded-[5px] font-semibold uppercase tracking-wide text-[var(--ep-accent)] ring-1 ring-[color-mix(in_oklab,var(--ep-accent)_35%,transparent)] bg-[color-mix(in_oklab,var(--ep-accent)_10%,transparent)]"
        :class="size === 'xs' ? 'px-0.5 py-px text-[8px] leading-none' : 'px-1 py-px text-[9px] leading-tight'"
        title="Funcionalidade em beta"
    >
        Beta
    </span>
</template>
