<script setup>
import { computed } from 'vue';
import { MousePointerClick, AlertTriangle } from 'lucide-vue-next';

const props = defineProps({
    fields: { type: Array, default: () => [] },
});

const enriched = computed(() => {
    const items = [...props.fields].sort((a, b) => (b.dropoff_percent ?? 0) - (a.dropoff_percent ?? 0));
    const worst = items[0] ?? null;
    return { items, worst };
});

function dropoffStyle(percent) {
    if (percent >= 40) {
        return {
            bar: 'from-red-500 to-orange-400',
            chip: 'bg-red-500/12 text-red-700 dark:text-red-300',
        };
    }
    if (percent >= 20) {
        return {
            bar: 'from-amber-500 to-yellow-400',
            chip: 'bg-amber-500/12 text-amber-800 dark:text-amber-300',
        };
    }
    return {
        bar: 'from-[var(--color-primary)]/80 to-[var(--color-primary)]',
        chip: 'bg-[var(--color-primary)]/12 text-[var(--color-primary)]',
    };
}
</script>

<template>
    <section class="panel-card flex h-full min-w-0 flex-col p-5" aria-labelledby="trk-campos">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 id="trk-campos" class="flex items-center gap-2 text-[13px] font-medium text-[var(--ep-text-2)]">
                    <MousePointerClick class="h-4 w-4 text-[var(--ep-text-3)]" :stroke-width="1.75" aria-hidden="true" />
                    Abandono por campo
                </h2>
                <p class="mt-1 text-[12px] text-[var(--ep-text-4)]">Onde os compradores desistem no checkout</p>
            </div>
            <div
                v-if="enriched.worst && enriched.worst.dropoff_percent > 0"
                class="ep-chip ep-chip--neg hidden shrink-0 tabular-nums sm:inline-flex"
            >
                <span class="text-[10.5px] font-medium opacity-80">Pior campo</span>
                <span class="font-semibold">{{ enriched.worst.dropoff_percent }}%</span>
            </div>
        </div>

        <div v-if="enriched.items.length" class="mt-4 space-y-4">
            <div
                v-if="enriched.worst && enriched.worst.dropoff_percent > 0"
                class="relative overflow-hidden rounded-2xl border border-[color-mix(in_oklab,var(--ep-neg)_28%,transparent)] bg-[var(--ep-neg-bg)] p-3.5"
            >
                <div class="pointer-events-none absolute -left-6 -top-8 h-24 w-24 rounded-full bg-[radial-gradient(closest-side,color-mix(in_oklab,var(--ep-neg)_35%,transparent),transparent)] blur-xl" aria-hidden="true" />
                <div class="relative flex items-start gap-3">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border border-[color-mix(in_oklab,var(--ep-neg)_35%,transparent)] bg-[color-mix(in_oklab,var(--ep-neg)_14%,transparent)] text-[var(--ep-neg)]">
                        <AlertTriangle class="h-3.5 w-3.5" :stroke-width="1.75" aria-hidden="true" />
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-[13px] font-semibold text-[var(--ep-text)]">{{ enriched.worst.label }}</p>
                        <p class="mt-0.5 text-[12px] tabular-nums text-[var(--ep-text-3)]">
                            {{ enriched.worst.reached }} alcançaram · <span class="font-medium text-[var(--ep-neg)]">{{ enriched.worst.dropoff_percent }}% abandonaram</span>
                        </p>
                    </div>
                </div>
            </div>

            <ul class="space-y-3.5">
                <li
                    v-for="field in enriched.items"
                    :key="field.field_key"
                    :style="{ '--dc': field.dropoff_percent >= 40 ? 'var(--ep-neg)' : field.dropoff_percent >= 20 ? 'var(--ep-warn)' : 'var(--ep-accent)' }"
                >
                    <div class="flex items-center justify-between gap-2 text-[13px]">
                        <span class="min-w-0 truncate font-medium text-[var(--ep-text)]">
                            {{ field.label }}
                        </span>
                        <span
                            class="ep-chip shrink-0 tabular-nums"
                            :class="field.dropoff_percent >= 40 ? 'ep-chip--neg' : field.dropoff_percent >= 20 ? 'ep-chip--warn' : 'ep-chip--accent'"
                        >
                            {{ field.dropoff_percent }}% drop
                        </span>
                    </div>
                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-[var(--ep-active)]">
                        <div
                            class="h-full rounded-full bg-[linear-gradient(90deg,color-mix(in_oklab,var(--dc)_60%,transparent),var(--dc))] transition-[width] duration-700 ease-[cubic-bezier(0.23,1,0.32,1)]"
                            :style="{ width: `${Math.max(field.completed_percent, field.reached ? 4 : 0)}%` }"
                        />
                    </div>
                    <div class="mt-1 flex justify-between text-[11.5px] tabular-nums text-[var(--ep-text-4)]">
                        <span>{{ field.reached }} alcançaram</span>
                        <span>{{ field.completed_percent }}% concluíram</span>
                    </div>
                </li>
            </ul>
        </div>

        <div v-else class="ep-empty flex-1">
            <p class="ep-empty__title">Dados serão coletados conforme novos checkouts</p>
            <p class="ep-empty__text">Cada campo preenchido ou abandonado entra aqui automaticamente.</p>
        </div>
    </section>
</template>
