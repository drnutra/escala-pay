<script setup>
import { computed, onMounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import VueApexCharts from 'vue3-apexcharts';
import { LineChart } from 'lucide-vue-next';
import { formatBRL } from '@/composables/useTrackingPanel';

const props = defineProps({
    chart: { type: Array, default: () => [] },
    period: { type: String, default: 'hoje' },
    valuesVisible: { type: Boolean, default: true },
});

const page = usePage();
const isDarkMode = ref(false);

onMounted(() => {
    isDarkMode.value = document.documentElement.classList.contains('dark');
});

const chartPrimaryColor = computed(() => {
    const fromSettings = page.props.appSettings?.theme_primary;
    if (typeof fromSettings === 'string' && fromSettings.trim()) return fromSettings.trim();
    return '#0ea5e9';
});

const totalRevenue = computed(() =>
    props.chart.reduce((s, d) => s + (Number(d.total) || 0), 0)
);

const chartSeries = computed(() => [{
    name: 'Receita',
    data: props.valuesVisible ? props.chart.map((d) => d.total) : props.chart.map(() => 0),
}]);

function compactBrl(value) {
    const n = Number(value) || 0;
    if (n >= 1_000_000) {
        return `R$ ${(n / 1_000_000).toLocaleString('pt-BR', { maximumFractionDigits: 1 })} mi`;
    }
    if (n >= 10_000) {
        return `R$ ${(n / 1000).toLocaleString('pt-BR', { maximumFractionDigits: 0 })} mil`;
    }
    return formatBRL(n);
}

const chartOptions = computed(() => ({
    chart: {
        type: 'area',
        toolbar: { show: false },
        fontFamily: 'inherit',
        sparkline: { enabled: false },
        redrawOnParentResize: true,
    },
    colors: [chartPrimaryColor.value],
    stroke: { curve: 'smooth', width: 2.5 },
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.45,
            opacityTo: 0.04,
            stops: [0, 90, 100],
        },
    },
    xaxis: {
        categories: (props.period === 'hoje' || props.period === 'ontem')
            ? props.chart.map((d) => `${Number(d.data)}h`)
            : props.chart.map((d) => {
                const [, m, day] = (d.data || '').split('-');
                return day && m ? `${day}/${m}` : d.data;
            }),
        labels: { style: { colors: '#71717a', fontSize: '11px' } },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: {
        labels: {
            formatter: (v) => compactBrl(v),
            style: { colors: '#71717a', fontSize: '10px' },
            maxWidth: 64,
        },
    },
    grid: {
        borderColor: isDarkMode.value ? '#27272a' : '#e4e4e7',
        strokeDashArray: 4,
        padding: { top: 4, right: 4, bottom: 0, left: 0 },
    },
    tooltip: {
        theme: isDarkMode.value ? 'dark' : 'light',
        y: { formatter: (v) => (props.valuesVisible ? formatBRL(v) : '••••') },
    },
    dataLabels: { enabled: false },
    markers: { size: 0, hover: { size: 4 } },
}));

function displayTotal() {
    return props.valuesVisible ? formatBRL(totalRevenue.value) : '••••••';
}
</script>

<template>
    <section class="panel-card trk-chart flex h-full min-w-0 max-w-full flex-col overflow-hidden p-5 pb-3" aria-labelledby="trk-receita">
        <div class="flex min-w-0 flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 id="trk-receita" class="flex min-w-0 flex-wrap items-center gap-2 text-[13px] font-medium text-[var(--ep-text-2)]">
                    <LineChart class="h-4 w-4 text-[var(--ep-text-3)]" :stroke-width="1.75" aria-hidden="true" />
                    Receita no período
                </h2>
                <p v-if="chart.length" class="mt-1.5 flex items-baseline gap-2 text-[12px] text-[var(--ep-text-4)]">
                    Total
                    <span class="text-[18px] font-semibold tabular-nums tracking-[-0.025em] text-[var(--ep-text)] [text-shadow:0_2px_18px_color-mix(in_oklab,var(--ep-glow)_55%,transparent)]">{{ displayTotal() }}</span>
                </p>
            </div>
            <span class="flex items-center gap-1.5 text-[12px] text-[var(--ep-text-3)]">
                <span class="h-2 w-2 rounded-full bg-[var(--ep-accent)]" aria-hidden="true" />
                Receita
            </span>
        </div>

        <div class="relative -mx-1 mt-3 min-h-[220px] min-w-0 max-w-full flex-1 overflow-hidden">
            <div v-if="chart.length" class="min-w-0 w-full max-w-full overflow-hidden">
                <VueApexCharts
                    type="area"
                    height="220"
                    width="100%"
                    :options="chartOptions"
                    :series="chartSeries"
                />
            </div>
            <div v-else class="flex h-[220px] flex-col items-center justify-center px-6 text-center">
                <svg class="h-10 w-28 text-[var(--ep-line-strong)]" viewBox="0 0 112 40" fill="none" aria-hidden="true">
                    <path d="M2 34 C 18 34, 22 22, 36 24 S 58 34, 70 20 S 94 8, 110 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="3 5" />
                </svg>
                <p class="mt-3 text-[13px] font-medium text-[var(--ep-text-2)]">Sem dados de receita no período</p>
                <p class="mt-1 max-w-[260px] text-[12.5px] text-[var(--ep-text-4)]">A curva aparece a partir do primeiro pagamento confirmado.</p>
            </div>
        </div>
    </section>
</template>

<style scoped>
/* Gráfico na cor da marca com brilho, grade hairline e tooltip de vidro (mesmo acabamento do Dashboard). */
.trk-chart :deep(.apexcharts-series path[fill='none']),
.trk-chart :deep(.apexcharts-area-series path[fill='none']) {
    stroke: var(--ep-accent);
    filter: drop-shadow(0 0 4px color-mix(in oklab, var(--ep-accent) 90%, transparent))
        drop-shadow(0 0 14px color-mix(in oklab, var(--ep-accent) 55%, transparent));
}
.trk-chart :deep(.apexcharts-svg linearGradient stop) {
    stop-color: var(--ep-accent);
}
.trk-chart :deep(.apexcharts-marker) {
    fill: var(--ep-accent);
    stroke: transparent;
    filter: drop-shadow(0 0 5px color-mix(in oklab, var(--ep-accent) 80%, transparent));
}
.trk-chart :deep(.apexcharts-gridline) {
    stroke: var(--ep-line);
}
.trk-chart :deep(.apexcharts-xaxis-label),
.trk-chart :deep(.apexcharts-yaxis-label) {
    fill: var(--ep-text-4);
    font-variant-numeric: tabular-nums;
}
.trk-chart :deep(.apexcharts-xcrosshairs) {
    stroke: var(--ep-line-strong);
}
.trk-chart :deep(.apexcharts-xaxistooltip) {
    display: none;
}
.trk-chart :deep(.apexcharts-tooltip) {
    overflow: hidden;
    border-radius: 12px;
    border: 1px solid var(--ep-glass-border) !important;
    background: var(--ep-drawer) !important;
    color: var(--ep-text) !important;
    box-shadow: var(--ep-shadow-pop) !important;
    -webkit-backdrop-filter: blur(18px) saturate(160%);
    backdrop-filter: blur(18px) saturate(160%);
}
.trk-chart :deep(.apexcharts-tooltip-title) {
    margin-bottom: 0 !important;
    padding: 7px 11px !important;
    background: transparent !important;
    border-bottom: 1px solid var(--ep-line) !important;
    color: var(--ep-text-3);
    font-size: 11.5px !important;
}
.trk-chart :deep(.apexcharts-tooltip-marker) {
    background-color: var(--ep-accent) !important;
    box-shadow: 0 0 8px var(--ep-accent);
}
.trk-chart :deep(.apexcharts-tooltip-text-y-value) {
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    color: var(--ep-text);
}
.trk-chart :deep(.apexcharts-tooltip-text-y-label) {
    color: var(--ep-text-3);
}
</style>
