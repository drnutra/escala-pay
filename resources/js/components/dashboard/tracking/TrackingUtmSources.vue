<script setup>
import { computed } from 'vue';
import { Link2, Megaphone } from 'lucide-vue-next';

const props = defineProps({
    sources: { type: Array, default: () => [] },
});

const summary = computed(() => {
    const total = props.sources.reduce((s, src) => s + (src.count ?? 0), 0);
    return {
        total,
        items: props.sources.map((src, idx) => ({
            ...src,
            rank: idx + 1,
            percent: total > 0 ? Math.round(((src.count ?? 0) / total) * 1000) / 10 : 0,
        })),
    };
});

const leader = computed(() => summary.value.items[0] ?? null);

const RANK_CHIP = [
    'bg-amber-500/15 text-amber-700 dark:text-amber-300',
    'bg-zinc-400/15 text-zinc-700 dark:text-zinc-300',
    'bg-orange-600/15 text-orange-800 dark:text-orange-300',
];
</script>

<template>
    <section class="panel-card flex h-full min-w-0 max-w-full flex-col overflow-hidden p-5" aria-labelledby="trk-utm">
        <div class="flex min-w-0 items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 id="trk-utm" class="flex min-w-0 flex-wrap items-center gap-2 text-[13px] font-medium text-[var(--ep-text-2)]">
                    <Link2 class="h-4 w-4 text-[var(--ep-text-3)]" :stroke-width="1.75" aria-hidden="true" />
                    Top fontes (UTM)
                </h2>
                <p v-if="summary.items.length" class="mt-1 text-[12px] tabular-nums text-[var(--ep-text-4)]">
                    {{ summary.total }} sessões rastreadas
                </p>
            </div>
        </div>

        <div v-if="summary.items.length" class="mt-4 flex min-w-0 flex-1 flex-col gap-4 overflow-hidden">
            <div
                v-if="leader"
                class="relative min-w-0 overflow-hidden rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4 shadow-[var(--ep-glass-highlight)]"
            >
                <div class="pointer-events-none absolute -right-6 -top-10 h-28 w-28 rounded-full bg-[radial-gradient(closest-side,color-mix(in_oklab,var(--ep-accent)_40%,transparent),transparent)] blur-xl" aria-hidden="true" />
                <div class="relative flex min-w-0 items-center gap-3">
                    <div class="ep-kpi__icon h-10 w-10 shrink-0 rounded-xl">
                        <Megaphone class="h-[18px] w-[18px]" :stroke-width="1.75" aria-hidden="true" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-[13.5px] font-semibold leading-snug text-[var(--ep-text)]">
                            {{ leader.source }} <span class="font-normal text-[var(--ep-text-4)]">/</span> {{ leader.medium }}
                        </p>
                        <p class="text-[12px] tabular-nums text-[var(--ep-text-3)]">{{ leader.count }} sessões</p>
                    </div>
                    <p class="shrink-0 text-[22px] font-semibold tabular-nums tracking-[-0.03em] text-[var(--ep-text)]">
                        {{ leader.percent }}<span class="ml-0.5 text-[13px] font-medium text-[var(--ep-text-3)]">%</span>
                    </p>
                </div>
            </div>

            <ul class="min-w-0 space-y-3.5 overflow-hidden">
                <li
                    v-for="src in summary.items"
                    :key="`${src.source}-${src.medium}`"
                    class="min-w-0"
                >
                    <div class="flex min-w-0 items-center gap-2.5 text-[13px]">
                        <span
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border text-[11px] font-semibold tabular-nums"
                            :class="src.rank === 1
                                ? 'border-[color-mix(in_oklab,var(--ep-accent)_35%,transparent)] bg-[color-mix(in_oklab,var(--ep-accent)_14%,transparent)] text-[var(--ep-accent)]'
                                : 'border-[var(--ep-line)] bg-[var(--ep-card-2)] text-[var(--ep-text-3)]'"
                        >
                            {{ src.rank }}
                        </span>
                        <span class="min-w-0 flex-1 truncate font-medium text-[var(--ep-text)]">
                            {{ src.source }} <span class="font-normal text-[var(--ep-text-4)]">/</span> {{ src.medium }}
                        </span>
                        <span class="shrink-0 text-[12px] tabular-nums text-[var(--ep-text-4)]">{{ src.percent }}%</span>
                        <span class="w-10 shrink-0 text-right font-medium tabular-nums text-[var(--ep-text)]">
                            {{ src.count }}
                        </span>
                    </div>
                    <div class="ml-[34px] mt-1.5 h-1.5 overflow-hidden rounded-full bg-[var(--ep-active)]">
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-[var(--ep-accent)] to-[var(--ep-accent-2)] transition-[width] duration-500 ease-[cubic-bezier(0.23,1,0.32,1)]"
                            :style="{ width: `${Math.max(src.percent, src.count ? 4 : 0)}%` }"
                        />
                    </div>
                </li>
            </ul>
        </div>

        <div v-else class="ep-empty flex-1">
            <p class="ep-empty__title">Sem dados de UTM no período</p>
            <p class="ep-empty__text">Use links com utm_source e utm_medium nos anúncios para ver as fontes aqui.</p>
        </div>
    </section>
</template>
