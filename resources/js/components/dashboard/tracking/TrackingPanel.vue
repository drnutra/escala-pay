<script setup>
import { ArrowLeft, Loader2, Radar } from 'lucide-vue-next';
import DashboardPeriodFilter from '@/components/dashboard/DashboardPeriodFilter.vue';
import TrackingKpiRow from './TrackingKpiRow.vue';
import TrackingWorldMap from './TrackingWorldMap.vue';
import TrackingCountryLeader from './TrackingCountryLeader.vue';
import TrackingVisitsByCountry from './TrackingVisitsByCountry.vue';
import TrackingPaymentMethods from './TrackingPaymentMethods.vue';
import TrackingFieldDropoff from './TrackingFieldDropoff.vue';
import TrackingFunnel from './TrackingFunnel.vue';
import TrackingRecentSales from './TrackingRecentSales.vue';
import TrackingUtmSources from './TrackingUtmSources.vue';
import TrackingRevenueChart from './TrackingRevenueChart.vue';

defineProps({
    data: { type: Object, default: null },
    loading: { type: Boolean, default: false },
    error: { type: String, default: null },
    period: { type: String, default: 'hoje' },
    valuesVisible: { type: Boolean, default: true },
});

const emit = defineEmits([
    'close',
    'update:period',
    'save-daily',
    'save-period',
    'clear-period',
    'retry',
]);
</script>

<template>
    <div class="min-w-0 max-w-full space-y-5 overflow-x-hidden">
        <header class="flex flex-col gap-4">
            <div class="flex min-w-0 flex-wrap items-center gap-3">
                <button
                    type="button"
                    aria-label="Voltar para Dashboard"
                    class="ep-btn-secondary shrink-0 px-3"
                    @click="emit('close')"
                >
                    <ArrowLeft class="h-4 w-4 shrink-0" :stroke-width="1.75" aria-hidden="true" />
                    <span class="whitespace-nowrap">Dashboard</span>
                </button>
                <div class="mx-1 hidden h-6 w-px bg-[var(--ep-line-strong)] sm:block" aria-hidden="true" />
                <div class="flex min-w-0 flex-1 items-center gap-3">
                    <span class="ep-kpi__icon shrink-0" aria-hidden="true">
                        <Radar class="h-4 w-4" :stroke-width="1.75" />
                    </span>
                    <div class="min-w-0">
                        <h1 class="ep-page-heading truncate leading-tight">
                            Tracking avançado
                        </h1>
                        <p class="mt-0.5 truncate text-[12.5px] text-[var(--ep-text-3)]">
                            Visão completa de vendas, checkout, geo e ROI
                        </p>
                    </div>
                </div>
            </div>
            <div class="min-w-0">
                <DashboardPeriodFilter
                    :model-value="period"
                    @update:model-value="emit('update:period', $event)"
                />
            </div>
        </header>

        <div v-if="loading && !data" class="panel-card ep-empty min-h-[320px]">
            <Loader2 class="h-6 w-6 animate-spin text-[var(--ep-accent)]" :stroke-width="1.75" aria-hidden="true" />
            <p class="ep-empty__title mt-2">Carregando métricas...</p>
            <p class="ep-empty__text">Reunindo vendas, checkout, países e origem do tráfego.</p>
        </div>

        <div v-else-if="error" class="panel-card ep-empty">
            <span class="h-2 w-2 rounded-full bg-[var(--ep-neg)]" aria-hidden="true" />
            <p class="ep-empty__title mt-1 text-[var(--ep-neg)]">{{ error }}</p>
            <button
                type="button"
                class="ep-btn mt-3"
                @click="emit('retry')"
            >
                Tentar novamente
            </button>
        </div>

        <template v-else-if="data">
            <TrackingKpiRow
                :financial="data.financial"
                :ad-spend="data.ad_spend"
                :period="period"
                :values-visible="valuesVisible"
                @save-daily="emit('save-daily', $event)"
                @save-period="emit('save-period', $event)"
                @clear-period="emit('clear-period')"
            />

            <div class="grid items-stretch gap-4 lg:grid-cols-3">
                <section class="panel-card flex min-w-0 flex-col overflow-hidden p-6 lg:col-span-2" aria-labelledby="trk-mapa">
                    <div class="flex shrink-0 flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 id="trk-mapa" class="text-[13px] font-medium text-[var(--ep-text-2)]">Vendas por país</h2>
                            <p class="mt-1 text-[12px] text-[var(--ep-text-4)]">Mapa mundial — países com vendas destacados</p>
                        </div>
                        <div class="flex items-center gap-3 text-[11.5px] text-[var(--ep-text-3)]">
                            <span class="flex items-center gap-1.5">
                                <span class="h-2 w-2 rounded-full bg-[var(--ep-accent)]" aria-hidden="true" />
                                Com atividade
                            </span>
                            <span class="flex items-center gap-1.5">
                                <span class="h-2 w-2 rounded-full bg-[var(--ep-line-strong)]" aria-hidden="true" />
                                Sem dados
                            </span>
                        </div>
                    </div>
                    <div class="relative -mx-6 -mb-6 mt-3 h-[240px] border-t border-[var(--ep-line)] bg-[radial-gradient(60%_80%_at_50%_55%,color-mix(in_oklab,var(--ep-accent)_10%,transparent),transparent_72%)] sm:h-[268px]">
                        <TrackingWorldMap
                            class="absolute inset-0"
                            :countries="data.sales_by_country?.length ? data.sales_by_country : data.visits_by_country"
                            :highlight-code="data.top_country?.country_code"
                        />
                    </div>
                </section>
                <TrackingCountryLeader
                    :top-country="data.top_country"
                    :values-visible="valuesVisible"
                />
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <TrackingVisitsByCountry :visits="data.visits_by_country" />
                <TrackingPaymentMethods
                    :methods="data.payment_methods"
                    :values-visible="valuesVisible"
                />
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <TrackingFieldDropoff :fields="data.field_dropoff" />
                <TrackingFunnel :funnel="data.funnel" />
            </div>

            <div class="grid min-w-0 gap-4 lg:grid-cols-3">
                <TrackingRecentSales
                    class="min-w-0 lg:col-span-1"
                    :sales="data.recent_sales"
                    :values-visible="valuesVisible"
                />
                <div class="min-w-0 lg:col-span-2">
                    <div class="grid h-full min-w-0 grid-cols-1 gap-4 md:grid-cols-2">
                        <TrackingUtmSources class="min-w-0" :sources="data.utm_sources" />
                        <TrackingRevenueChart
                            class="min-w-0"
                            :chart="data.chart_revenue"
                            :period="period"
                            :values-visible="valuesVisible"
                        />
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
