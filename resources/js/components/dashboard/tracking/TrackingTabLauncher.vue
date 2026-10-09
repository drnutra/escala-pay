<script setup>
import { Radar } from 'lucide-vue-next';

defineProps({
    active: { type: Boolean, default: false },
});

const emit = defineEmits(['click']);
</script>

<template>
    <button
        type="button"
        :aria-label="active ? 'Painel de Tracking aberto' : 'Abrir painel de Tracking'"
        :aria-pressed="active"
        class="relative flex h-9 shrink-0 items-center justify-center gap-1.5 rounded-lg border border-[var(--ep-line)] bg-[var(--ep-card)] text-[var(--ep-text-2)] shadow-[var(--ep-highlight)] transition-colors duration-150 hover:border-[var(--ep-line-strong)] hover:text-[var(--ep-text)] active:scale-[0.97] lg:px-3"
        :class="[
            active ? 'border-[var(--color-primary)]/40 bg-[var(--color-primary)]/10' : '',
            'h-9 w-9 lg:w-auto',
        ]"
        @click="emit('click')"
    >
        <Radar class="h-4 w-4 shrink-0 text-[var(--color-primary)]" :stroke-width="1.75" aria-hidden="true" />
        <span class="hidden text-[13px] font-medium lg:inline">Tracking</span>
        <span
            v-if="!active"
            class="absolute -right-0.5 -top-0.5 h-2 w-2 rounded-full bg-[var(--color-primary)] ring-2 ring-[var(--ep-surface)]"
            aria-hidden="true"
        />
    </button>
</template>
