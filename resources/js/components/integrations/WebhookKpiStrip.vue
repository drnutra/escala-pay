<script setup>
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps({
    summary: {
        type: Object,
        default: () => ({ sent: 0, delivered: 0, failed: 0, delivery_rate: 0 }),
    },
    sparkline: {
        type: Object,
        default: () => ({ sent: [], delivered: [], failed: [] }),
    },
    loading: { type: Boolean, default: false },
});

const cards = computed(() => [
    {
        key: 'sent',
        label: 'Enviados',
        value: props.summary.sent ?? 0,
        color: '#0ea5e9',
        data: props.sparkline.sent ?? [],
    },
    {
        key: 'delivered',
        label: 'Entregues',
        value: props.summary.delivered ?? 0,
        sub: `${props.summary.delivery_rate ?? 0}%`,
        color: '#10b981',
        data: props.sparkline.delivered ?? [],
    },
    {
        key: 'failed',
        label: 'Falharam',
        value: props.summary.failed ?? 0,
        color: '#ef4444',
        data: props.sparkline.failed ?? [],
    },
]);

function sparkOptions(color, data) {
    return {
        chart: {
            type: 'area',
            sparkline: { enabled: true },
            animations: { enabled: false },
        },
        stroke: { curve: 'smooth', width: 2, colors: [color] },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 0.4,
                opacityFrom: 0.35,
                opacityTo: 0.05,
                stops: [0, 100],
            },
            colors: [color],
        },
        tooltip: { enabled: false },
        grid: { padding: { top: 4, bottom: 0, left: 0, right: 0 } },
    };
}

function sparkSeries(data) {
    return [{ data: data.length ? data : [0, 0, 0, 0] }];
}
</script>

<template>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div
            v-for="card in cards"
            :key="card.key"
            class="panel-card ep-kpi relative overflow-hidden !p-4"
        >
            <div v-if="loading" class="space-y-2.5 animate-pulse" aria-hidden="true">
                <div class="h-3 w-20 rounded-full bg-[var(--ep-active)]" />
                <div class="h-7 w-14 rounded-lg bg-[var(--ep-active)]" />
            </div>
            <template v-else>
                <p class="ep-kpi__label flex items-center gap-1.5">
                    <span class="h-1.5 w-1.5 rounded-full" :style="{ background: card.color, boxShadow: `0 0 8px ${card.color}` }" aria-hidden="true" />
                    {{ card.label }}
                </p>
                <div class="flex items-end justify-between gap-2">
                    <div class="min-w-0">
                        <p class="ep-kpi__value">
                            {{ card.value }}
                        </p>
                        <p
                            v-if="card.sub"
                            class="ep-chip ep-chip--pos mt-1.5 !h-5 !px-1.5 text-[11px] tabular-nums"
                        >
                            {{ card.sub }}
                        </p>
                    </div>
                    <div class="h-10 w-24 shrink-0 opacity-90">
                        <VueApexCharts
                            type="area"
                            height="40"
                            width="96"
                            :options="sparkOptions(card.color, card.data)"
                            :series="sparkSeries(card.data)"
                        />
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>
