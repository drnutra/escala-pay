<script setup>
import { Trophy, MapPin } from 'lucide-vue-next';
import { formatBRL, countryFlag } from '@/composables/useTrackingPanel';
import MoneyAmount from '@/components/ui/MoneyAmount.vue';

defineProps({
    topCountry: { type: Object, default: null },
    valuesVisible: { type: Boolean, default: true },
});
</script>

<template>
    <section class="panel-card flex h-full min-w-0 flex-col p-6" aria-labelledby="trk-lider">
        <div class="flex items-center justify-between gap-3">
            <h2 id="trk-lider" class="flex items-center gap-2 text-[13px] font-medium text-[var(--ep-text-2)]">
                <Trophy class="h-4 w-4 text-[var(--ep-warn)]" :stroke-width="1.75" aria-hidden="true" />
                País líder em vendas
            </h2>
        </div>

        <div
            v-if="topCountry"
            class="relative mt-5 flex flex-1 flex-col items-center justify-center overflow-hidden rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] px-5 py-6 text-center shadow-[var(--ep-glass-highlight)]"
        >
            <div
                class="pointer-events-none absolute left-1/2 top-0 h-48 w-48 -translate-x-1/2 -translate-y-1/3 rounded-full bg-[radial-gradient(closest-side,color-mix(in_oklab,var(--ep-accent)_38%,transparent),transparent)] blur-2xl"
                aria-hidden="true"
            />

            <div class="relative flex h-16 w-16 items-center justify-center rounded-2xl border border-[var(--ep-glass-border)] border-t-[var(--ep-glass-border-top)] bg-[linear-gradient(180deg,var(--ep-glass-strong),var(--ep-glass))] text-4xl leading-none shadow-[var(--ep-glass-highlight),0_10px_30px_-12px_var(--ep-glow)]">
                {{ countryFlag(topCountry.country_code) }}
            </div>

            <p class="relative mt-4 text-[15px] font-semibold tracking-[-0.015em] text-[var(--ep-text)]">
                {{ topCountry.country_name }}
            </p>

            <MoneyAmount
                class="relative mt-2"
                :value="Number(topCountry.total) || 0"
                :hidden="!valuesVisible"
                size="lg"
            />

            <div class="relative mt-4 flex flex-wrap items-center justify-center gap-2">
                <span class="ep-chip ep-chip--accent tabular-nums">
                    {{ topCountry.percent }}% do total
                </span>
                <span class="ep-chip tabular-nums">
                    <MapPin class="h-3 w-3" :stroke-width="1.75" aria-hidden="true" />
                    {{ topCountry.count }} {{ topCountry.count === 1 ? 'venda' : 'vendas' }}
                </span>
            </div>
        </div>

        <div v-else class="ep-empty flex-1">
            <p class="ep-empty__title">Nenhuma venda geolocalizada</p>
            <p class="ep-empty__text">O país líder aparece quando uma venda do período tiver localização identificada.</p>
        </div>
    </section>
</template>
