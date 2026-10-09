<script setup>
import { computed } from 'vue';
import { Filter, Users, FileText, CheckCircle2 } from 'lucide-vue-next';

const props = defineProps({
    funnel: { type: Object, default: () => ({}) },
});

const STEP_META = [
    { key: 'visitas', label: 'Visitas', icon: Users, bar: 'from-zinc-400 to-zinc-500', chip: 'bg-zinc-500/12 text-zinc-600 dark:text-zinc-300' },
    { key: 'form_started', label: 'Formulário iniciado', icon: FileText, bar: 'from-blue-500 to-indigo-400', chip: 'bg-blue-500/12 text-blue-700 dark:text-blue-300' },
    { key: 'form_filled', label: 'Formulário preenchido', icon: FileText, bar: 'from-violet-500 to-purple-400', chip: 'bg-violet-500/12 text-violet-700 dark:text-violet-300' },
    { key: 'convertidos', label: 'Convertidos', icon: CheckCircle2, bar: 'from-[var(--color-primary)] to-[var(--color-primary)]/70', chip: 'bg-[var(--color-primary)]/12 text-[var(--color-primary)]' },
];

const steps = computed(() => {
    const f = props.funnel ?? {};
    const values = STEP_META.map((m) => f[m.key] ?? 0);
    const max = Math.max(...values, 1);
    return STEP_META.map((meta, idx) => ({
        ...meta,
        value: values[idx],
        percent: Math.round((values[idx] / max) * 1000) / 10,
        convFromPrev: idx > 0 && values[idx - 1] > 0
            ? Math.round((values[idx] / values[idx - 1]) * 1000) / 10
            : null,
    }));
});

const conversionRate = computed(() => props.funnel?.taxa_conversao ?? 0);
const abandonment = computed(() => props.funnel?.abandono ?? 0);
</script>

<template>
    <section class="panel-card flex h-full min-w-0 flex-col p-5" aria-labelledby="trk-funil">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 id="trk-funil" class="flex items-center gap-2 text-[13px] font-medium text-[var(--ep-text-2)]">
                    <Filter class="h-4 w-4 text-[var(--ep-text-3)]" :stroke-width="1.75" aria-hidden="true" />
                    Funil de checkout
                </h2>
                <p class="mt-1 text-[12px] text-[var(--ep-text-4)]">Jornada da visita até a conversão</p>
            </div>
            <dl class="flex gap-2">
                <div class="rounded-xl border border-[color-mix(in_oklab,var(--ep-accent)_30%,transparent)] bg-[color-mix(in_oklab,var(--ep-accent)_10%,transparent)] px-3 py-1.5 text-right">
                    <dt class="text-[11px] font-medium text-[var(--ep-text-3)]">Conversão</dt>
                    <dd class="text-[15px] font-semibold tabular-nums tracking-[-0.02em] text-[var(--ep-accent)]">{{ conversionRate }}%</dd>
                </div>
                <div class="rounded-xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] px-3 py-1.5 text-right">
                    <dt class="text-[11px] font-medium text-[var(--ep-text-3)]">Abandono</dt>
                    <dd class="text-[15px] font-semibold tabular-nums tracking-[-0.02em] text-[var(--ep-neg)]">{{ abandonment }}</dd>
                </div>
            </dl>
        </div>

        <ol class="mt-5 flex flex-1 flex-col gap-4">
            <li
                v-for="step in steps"
                :key="step.key"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-[10px] border"
                        :class="step.key === 'convertidos'
                            ? 'border-[color-mix(in_oklab,var(--ep-pos)_35%,transparent)] bg-[var(--ep-pos-bg)] text-[var(--ep-pos)]'
                            : 'border-[var(--ep-line)] bg-[var(--ep-card-2)] text-[var(--ep-text-3)]'"
                    >
                        <component :is="step.icon" class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="truncate text-[13px] font-medium text-[var(--ep-text)]">{{ step.label }}</span>
                            <span class="shrink-0 text-[15px] font-semibold tabular-nums tracking-[-0.02em] text-[var(--ep-text)]">
                                {{ step.value }}
                            </span>
                        </div>
                        <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-[var(--ep-active)]">
                            <div
                                class="h-full rounded-full transition-[width] duration-700 ease-[cubic-bezier(0.23,1,0.32,1)]"
                                :class="step.key === 'convertidos'
                                    ? 'bg-[linear-gradient(90deg,var(--ep-accent),var(--ep-pos))]'
                                    : 'bg-gradient-to-r from-[var(--ep-accent)] to-[var(--ep-accent-2)]'"
                                :style="{ width: `${Math.max(step.percent, step.value ? 8 : 0)}%` }"
                            />
                        </div>
                        <p v-if="step.convFromPrev != null" class="mt-1 text-[11.5px] tabular-nums text-[var(--ep-text-4)]">
                            {{ step.convFromPrev }}% da etapa anterior
                        </p>
                    </div>
                </div>
            </li>
        </ol>
    </section>
</template>
