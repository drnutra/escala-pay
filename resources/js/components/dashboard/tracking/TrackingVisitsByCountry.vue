<script setup>
import { computed } from 'vue';
import { Globe } from 'lucide-vue-next';
import { countryFlag } from '@/composables/useTrackingPanel';

const props = defineProps({
    visits: { type: Array, default: () => [] },
    maxItems: { type: Number, default: 8 },
});

const sorted = computed(() =>
    [...props.visits].sort((a, b) => (b.count ?? 0) - (a.count ?? 0)).slice(0, props.maxItems)
);

const summary = computed(() => {
    const total = sorted.value.reduce((s, v) => s + (v.count ?? 0), 0);
    return {
        total,
        items: sorted.value.map((v) => ({
            ...v,
            percent: total > 0 ? Math.round(((v.count ?? 0) / total) * 1000) / 10 : 0,
        })),
    };
});

const leader = computed(() => summary.value.items[0] ?? null);
</script>

<template>
    <section class="panel-card flex h-full min-w-0 flex-col p-5" aria-labelledby="trk-visitas">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 id="trk-visitas" class="flex items-center gap-2 text-[13px] font-medium text-[var(--ep-text-2)]">
                    <Globe class="h-4 w-4 text-[var(--ep-text-3)]" :stroke-width="1.75" aria-hidden="true" />
                    Visitas por país
                </h2>
                <p v-if="summary.items.length" class="mt-1 text-[12px] tabular-nums text-[var(--ep-text-4)]">
                    {{ summary.total }} visitas · {{ summary.items.length }} países
                </p>
            </div>
            <div
                v-if="leader"
                class="ep-chip ep-chip--accent hidden shrink-0 tabular-nums sm:inline-flex"
            >
                <span class="text-[10.5px] font-medium opacity-80">Top</span>
                <span class="font-semibold">{{ leader.percent }}%</span>
            </div>
        </div>

        <div v-if="summary.items.length" class="mt-4 flex flex-1 flex-col gap-4">
            <div
                v-if="leader"
                class="relative overflow-hidden rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4 shadow-[var(--ep-glass-highlight)]"
            >
                <div class="pointer-events-none absolute -right-6 -top-10 h-28 w-28 rounded-full bg-[radial-gradient(closest-side,color-mix(in_oklab,var(--ep-accent)_40%,transparent),transparent)] blur-xl" aria-hidden="true" />
                <div class="relative flex items-center gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-[var(--ep-glass-border)] bg-[var(--ep-glass-strong)] text-2xl leading-none shadow-[var(--ep-glass-highlight)]">{{ countryFlag(leader.country_code) }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-[13.5px] font-semibold text-[var(--ep-text)]">{{ leader.country_name }}</p>
                        <p class="text-[12px] tabular-nums text-[var(--ep-text-3)]">{{ leader.count }} visitas</p>
                    </div>
                    <p class="shrink-0 text-[22px] font-semibold tabular-nums tracking-[-0.03em] text-[var(--ep-text)]">
                        {{ leader.percent }}<span class="ml-0.5 text-[13px] font-medium text-[var(--ep-text-3)]">%</span>
                    </p>
                </div>
            </div>

            <ul class="space-y-3.5">
                <li
                    v-for="v in summary.items"
                    :key="v.country_code ?? v.country_name"
                >
                    <div class="flex items-center gap-2.5 text-[13px]">
                        <span class="w-5 shrink-0 text-center text-base leading-none">
                            {{ countryFlag(v.country_code) }}
                        </span>
                        <span class="min-w-0 flex-1 truncate font-medium text-[var(--ep-text)]">
                            {{ v.country_name }}
                        </span>
                        <span class="shrink-0 text-[12px] tabular-nums text-[var(--ep-text-4)]">{{ v.count }} visitas</span>
                        <span class="w-12 shrink-0 text-right font-medium tabular-nums text-[var(--ep-text)]">
                            {{ v.percent }}%
                        </span>
                    </div>
                    <div class="ml-[30px] mt-1.5 h-1.5 overflow-hidden rounded-full bg-[var(--ep-active)]">
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-[var(--ep-accent)] to-[var(--ep-accent-2)] transition-[width] duration-500 ease-[cubic-bezier(0.23,1,0.32,1)]"
                            :style="{ width: `${Math.max(v.percent, v.count ? 4 : 0)}%` }"
                        />
                    </div>
                </li>
            </ul>
        </div>

        <div v-else class="ep-empty flex-1">
            <p class="ep-empty__title">Sem visitas no período</p>
            <p class="ep-empty__text">As visitas aparecem conforme os checkouts recebem acessos com país identificado.</p>
        </div>
    </section>
</template>
