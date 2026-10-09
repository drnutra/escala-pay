<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';
import VueApexCharts from 'vue3-apexcharts';
import ConquistasWidget from '@/components/layout/ConquistasWidget.vue';
import DashboardPeriodFilter from '@/components/dashboard/DashboardPeriodFilter.vue';
import TrackingTabLauncher from '@/components/dashboard/tracking/TrackingTabLauncher.vue';
import MoneyAmount from '@/components/ui/MoneyAmount.vue';
import { ArrowUpRight, ArrowDownRight, ChevronRight, Eye, EyeOff } from 'lucide-vue-next';

const page = usePage();
const hasAchievementsProgress = computed(() => !!(page.props.achievementsProgress ?? null));

const valuesVisible = defineModel('valuesVisible', { type: Boolean, default: true });

const props = defineProps({
    period: { type: String, default: 'hoje' },
    comparacao: { type: Object, default: null },
    vendas_totais: { type: Number, default: 0 },
    vendas_totais_por_moeda: { type: Array, default: () => [] },
    vendas_pendentes: { type: Number, default: 0 },
    pendentes_count: { type: Number, default: 0 },
    quantidade_vendas: { type: Number, default: 0 },
    ticket_medio: { type: Number, default: 0 },
    formas_pagamento: { type: Array, default: () => [] },
    taxa_conversao: { type: Number, default: 0 },
    abandono_carrinho: { type: Number, default: 0 },
    reembolsos_count: { type: Number, default: 0 },
    reembolsos_total: { type: Number, default: 0 },
    quantidade_produtos: { type: Number, default: 0 },
    grafico_vendas: { type: Array, default: () => [] },
    grafico_vendas_anterior: { type: Array, default: null },
    top_produtos: { type: Array, default: () => [] },
    vendas_recentes: { type: Array, default: () => [] },
    ultimos_7_dias: { type: Array, default: () => [] },
    funil_checkout: { type: Object, default: null },
});

const emit = defineEmits(['update:period', 'open-tracking']);

function setPeriod(value) {
    emit('update:period', value);
    router.get('/dashboard', { period: value }, { preserveState: false });
}

/* ---------- Tema e relógio (gráfico, "agora" e tempos relativos) ---------- */
const isDarkMode = ref(false);
const now = ref(new Date());
let themeObserver;
let clock;
onMounted(() => {
    const root = document.documentElement;
    isDarkMode.value = root.classList.contains('dark');
    themeObserver = new MutationObserver(() => {
        isDarkMode.value = root.classList.contains('dark');
    });
    themeObserver.observe(root, { attributes: true, attributeFilter: ['class'] });
    clock = setInterval(() => (now.value = new Date()), 30_000);
});
onBeforeUnmount(() => {
    themeObserver?.disconnect();
    clearInterval(clock);
});

/* ---------- Formatação ---------- */
const brl = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
const brlCompact = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL', notation: 'compact', maximumFractionDigits: 1 });
const intFmt = new Intl.NumberFormat('pt-BR');
const pctFmt = new Intl.NumberFormat('pt-BR', { maximumFractionDigits: 1, minimumFractionDigits: 1 });
const timeFmt = new Intl.DateTimeFormat('pt-BR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });

const money = (v) => (valuesVisible.value ? brl.format(v ?? 0) : 'R$ ••••');
const count = (v) => (valuesVisible.value ? intFmt.format(v ?? 0) : '—');

function relative(iso) {
    if (!iso) return '';
    const diff = Math.max(0, (now.value.getTime() - new Date(iso).getTime()) / 1000);
    if (diff < 60) return 'agora';
    if (diff < 3600) return `há ${Math.floor(diff / 60)} min`;
    if (diff < 86400) return `há ${Math.floor(diff / 3600)} h`;
    return `há ${Math.floor(diff / 86400)} d`;
}
const absolute = (iso) => (iso ? timeFmt.format(new Date(iso)) : '');

const initials = (name) => {
    const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
    if (!parts.length) return '?';
    return ((parts[0][0] || '') + (parts.length > 1 ? parts[parts.length - 1][0] : '')).toUpperCase();
};

const periodLabels = { hoje: 'Hoje', ontem: 'Ontem', '7dias': 'Últimos 7 dias', mes: 'Este mês', ano: 'Este ano', total: 'Todo o período' };
const periodLabel = computed(() => periodLabels[props.period] ?? 'Hoje');
const isHourly = computed(() => props.period === 'hoje' || props.period === 'ontem');
const previousLabel = computed(() => {
    switch (props.period) {
        case 'hoje': return 'Ontem';
        case 'ontem': return 'Anteontem';
        case '7dias': return '7 dias anteriores';
        case 'mes': return 'Mês passado';
        case 'ano': return 'Ano passado';
        default: return 'Período anterior';
    }
});

/* ---------- Faturamento + comparação ---------- */
const revenueRows = computed(() => {
    const rows = props.vendas_totais_por_moeda ?? [];
    if (!rows.length) return [{ currency: 'BRL', total: 0 }];
    return [...rows].sort((a, b) => (a.currency === 'BRL' ? -1 : b.currency === 'BRL' ? 1 : 0));
});

function deltaOf(current, previous) {
    if (previous === null || previous === undefined) return null;
    if (previous === 0) return current > 0 ? { kind: 'up', text: 'Novo' } : { kind: 'flat', text: '0,0%' };
    const pct = ((current - previous) / previous) * 100;
    if (Math.abs(pct) < 0.05) return { kind: 'flat', text: '0,0%' };
    const sign = pct > 0 ? '+' : '−';
    return { kind: pct > 0 ? 'up' : 'down', text: `${sign}${pctFmt.format(Math.abs(pct))}%` };
}
const revenueDelta = computed(() => (props.comparacao ? deltaOf(props.vendas_totais, props.comparacao.vendas_totais) : null));
const salesDelta = computed(() => (props.comparacao ? deltaOf(props.quantidade_vendas, props.comparacao.quantidade_vendas) : null));
const chipClass = (d) => (d?.kind === 'up' ? 'ep-chip--pos' : d?.kind === 'down' ? 'ep-chip--neg' : '');

/* ---------- Formas de pagamento (ordem fixa: Pix, Cartão, Boleto) ---------- */
const methodColor = { Pix: 'var(--ep-pix)', Cartão: 'var(--ep-cartao)', Boleto: 'var(--ep-boleto)' };
const methodOrder = ['Pix', 'Cartão', 'Boleto'];
const paymentRows = computed(() => {
    const rows = props.formas_pagamento ?? [];
    const total = rows.reduce((acc, r) => acc + (r.total ?? 0), 0);
    return [...rows]
        .sort((a, b) => {
            const ia = methodOrder.indexOf(a.label), ib = methodOrder.indexOf(b.label);
            return (ia === -1 ? 99 : ia) - (ib === -1 ? 99 : ib) || (b.total ?? 0) - (a.total ?? 0);
        })
        .map((r) => ({ ...r, share: total > 0 ? ((r.total ?? 0) / total) * 100 : 0, color: methodColor[r.label] ?? 'var(--ep-text-4)' }));
});

/* ---------- Top produtos ---------- */
const topRows = computed(() => {
    const rows = props.top_produtos ?? [];
    const max = Math.max(1, ...rows.map((r) => r.total ?? 0));
    const sum = rows.reduce((a, r) => a + (r.total ?? 0), 0);
    return rows.map((r) => ({ ...r, width: Math.max(2, ((r.total ?? 0) / max) * 100), share: sum > 0 ? ((r.total ?? 0) / sum) * 100 : 0 }));
});

/* ---------- Últimas vendas ---------- */
const statusMap = {
    completed: { label: 'Aprovada', cls: 'ep-chip--pos' },
    paid: { label: 'Aprovada', cls: 'ep-chip--pos' },
    pending: { label: 'Aguardando', cls: 'ep-chip--warn' },
    refunded: { label: 'Reembolsada', cls: '' },
    cancelled: { label: 'Cancelada', cls: '' },
    canceled: { label: 'Cancelada', cls: '' },
    failed: { label: 'Recusada', cls: 'ep-chip--neg' },
    refused: { label: 'Recusada', cls: 'ep-chip--neg' },
    declined: { label: 'Recusada', cls: 'ep-chip--neg' },
    chargeback: { label: 'Contestada', cls: 'ep-chip--neg' },
    disputed: { label: 'Contestada', cls: 'ep-chip--neg' },
};
const statusOf = (s) => statusMap[s] ?? { label: s || '—', cls: '' };
const methodText = (v) => (v.parcelas && v.parcelas > 1 ? `${v.metodo} ${v.parcelas}x` : v.metodo);

/* ---------- Gráfico: período atual (área) vs anterior (linha tracejada) ---------- */
const currentHour = computed(() => now.value.getHours());
const labels = computed(() =>
    (props.grafico_vendas ?? []).map((d) => {
        if (isHourly.value) return `${String(Number(d.data)).padStart(2, '0')}h`;
        const [, m, day] = (d.data || '').split('-');
        return day && m ? `${day}/${m}` : d.data;
    }),
);
const currentData = computed(() =>
    (props.grafico_vendas ?? []).map((d, i) => {
        if (props.period === 'hoje' && isHourly.value && i > currentHour.value) return null;
        return valuesVisible.value ? d.total : 0;
    }),
);
const previousData = computed(() => (props.grafico_vendas_anterior ?? []).map((d) => (valuesVisible.value ? d.total : 0)));
const hasPrevious = computed(() => previousData.value.length === labels.value.length && previousData.value.some((v) => v > 0));
const hasSales = computed(() => currentData.value.some((v) => (v ?? 0) > 0) || hasPrevious.value);

const chartSeries = computed(() => {
    const s = [{ name: periodLabel.value, type: 'area', data: currentData.value }];
    if (hasPrevious.value) s.push({ name: previousLabel.value, type: 'line', data: previousData.value });
    return s;
});

const chartOptions = computed(() => {
    const dark = isDarkMode.value;
    const accent = dark ? '#6ba4ff' : '#2f6fe4';
    const prev = dark ? 'rgba(243,245,255,0.42)' : 'rgba(15,21,48,0.38)';
    const axis = dark ? 'rgba(243,245,255,0.42)' : 'rgba(15,21,48,0.45)';
    const lbs = labels.value;
    const nowLabel = props.period === 'hoje' ? lbs[currentHour.value] : null;
    return {
        chart: { type: 'line', toolbar: { show: false }, zoom: { enabled: false }, fontFamily: 'inherit', animations: { enabled: false }, parentHeightOffset: 0, background: 'transparent' },
        colors: [accent, prev],
        dataLabels: { enabled: false },
        stroke: { curve: 'monotoneCubic', width: [2.25, 1.5], dashArray: [0, 5], lineCap: 'round' },
        fill: {
            type: ['gradient', 'solid'],
            opacity: [1, 0],
            gradient: { shadeIntensity: 0, opacityFrom: dark ? 0.42 : 0.3, opacityTo: 0, stops: [0, 95] },
        },
        markers: { size: [3, 0], colors: [accent, prev], strokeWidth: 0, hover: { size: 6 } },
        xaxis: {
            categories: lbs,
            tickAmount: isHourly.value ? 8 : Math.min(lbs.length, 8),
            labels: { rotate: 0, hideOverlappingLabels: true, style: { colors: axis, fontSize: '11px' } },
            axisBorder: { show: false },
            axisTicks: { show: false },
            crosshairs: { show: true, stroke: { color: dark ? 'rgba(255,255,255,0.22)' : 'rgba(15,21,48,0.2)', width: 1, dashArray: 0 } },
            tooltip: { enabled: false },
        },
        yaxis: { tickAmount: 4, labels: { style: { colors: axis, fontSize: '11px' }, formatter: (v) => (valuesVisible.value ? brlCompact.format(v ?? 0) : '') } },
        grid: {
            borderColor: dark ? 'rgba(255,255,255,0.07)' : 'rgba(15,21,48,0.08)',
            strokeDashArray: 3,
            xaxis: { lines: { show: false } },
            yaxis: { lines: { show: true } },
            padding: { top: 4, right: 10, bottom: 0, left: 6 },
        },
        legend: { show: false },
        annotations: nowLabel
            ? {
                  xaxis: [
                      {
                          x: nowLabel,
                          strokeDashArray: 0,
                          borderColor: dark ? 'rgba(107,164,255,0.55)' : 'rgba(47,111,228,0.5)',
                          label: {
                              text: 'agora',
                              orientation: 'horizontal',
                              borderWidth: 0,
                              offsetY: -6,
                              textAnchor: currentHour.value >= 20 ? 'end' : 'middle',
                              offsetX: currentHour.value >= 20 ? -4 : 0,
                              style: { background: 'transparent', color: accent, fontSize: '10.5px', fontWeight: 600, fontFamily: 'inherit', padding: { left: 4, right: 4, top: 1, bottom: 1 } },
                          },
                      },
                  ],
              }
            : {},
        tooltip: {
            shared: true,
            intersect: false,
            custom: ({ series, dataPointIndex }) => {
                const cur = series?.[0]?.[dataPointIndex];
                const prv = series?.[1]?.[dataPointIndex];
                const when = lbs[dataPointIndex] ?? '';
                const head = isHourly.value ? `${periodLabel.value}, ${when}` : when;
                const prevLine = prv !== undefined && prv !== null ? `<span class="ep-chart-tip__prev">${previousLabel.value}: ${money(prv)}</span>` : '';
                return `<div class="ep-chart-tip"><span class="ep-chart-tip__label">${head}</span><span class="ep-chart-tip__value">${cur === null || cur === undefined ? '—' : money(cur)}</span>${prevLine}</div>`;
            },
        },
        states: { hover: { filter: { type: 'none' } }, active: { filter: { type: 'none' } } },
    };
});

/* ---------- Últimos 7 dias (barras com brilho no herói) ---------- */
const weekdayFmt = new Intl.DateTimeFormat('pt-BR', { weekday: 'short' });
const weekBars = computed(() => {
    const rows = props.ultimos_7_dias ?? [];
    const max = Math.max(1, ...rows.map((r) => r.total ?? 0));
    return rows.map((r, i) => {
        const [y, m, d] = String(r.data).split('-').map(Number);
        const label = weekdayFmt.format(new Date(y, (m || 1) - 1, d || 1)).replace('.', '');
        return {
            key: r.data,
            label: label.charAt(0).toUpperCase() + label.slice(1, 3),
            height: valuesVisible.value ? Math.max(4, ((r.total ?? 0) / max) * 100) : 4,
            title: `${label} ${d}/${String(m).padStart(2, '0')}: ${money(r.total)}`,
            today: i === rows.length - 1,
        };
    });
});
const weekTotal = computed(() => (props.ultimos_7_dias ?? []).reduce((a, r) => a + (r.total ?? 0), 0));

/* ---------- Funil do checkout e taxa de reembolso ---------- */
const funnelSteps = computed(() => {
    const f = props.funil_checkout;
    if (!f || !f.abertos) return [];
    const base = f.abertos;
    return [
        { key: 'abertos', label: 'Checkouts abertos', value: f.abertos, pct: 100 },
        { key: 'dados', label: 'Preencheram os dados', value: f.dados, pct: (f.dados / base) * 100 },
        { key: 'pagos', label: 'Pagos', value: f.pagos, pct: (f.pagos / base) * 100 },
    ];
});
const refundRate = computed(() => {
    const total = (props.quantidade_vendas ?? 0) + (props.reembolsos_count ?? 0);
    return total > 0 ? ((props.reembolsos_count ?? 0) / total) * 100 : 0;
});

/* ---------- Anel de conversão ---------- */
const RING_R = 44;
const RING_C = 2 * Math.PI * RING_R;
const ringOffset = computed(() => RING_C * (1 - Math.min(100, Math.max(0, valuesVisible.value ? props.taxa_conversao ?? 0 : 0)) / 100));
</script>

<template>
    <div class="space-y-5">
        <div v-if="hasAchievementsProgress" class="lg:hidden">
            <ConquistasWidget variant="dashboard" />
        </div>

        <!-- Período + ações -->
        <DashboardPeriodFilter class="min-w-0" :model-value="period" @update:model-value="setPeriod">
            <template #trailing>
                <button
                    type="button"
                    :aria-label="valuesVisible ? 'Ocultar valores' : 'Mostrar valores'"
                    :title="valuesVisible ? 'Ocultar valores' : 'Mostrar valores'"
                    class="ep-glass flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-[var(--ep-text-3)] transition-colors duration-150 hover:text-[var(--ep-text)] active:scale-[0.97]"
                    @click="valuesVisible = !valuesVisible"
                >
                    <Eye v-if="valuesVisible" class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                    <EyeOff v-else class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                </button>
                <TrackingTabLauncher @click="emit('open-tracking')" />
            </template>
        </DashboardPeriodFilter>

        <!-- Linha 1: faturamento (herói) + gráfico -->
        <div class="grid gap-4 lg:grid-cols-12">
            <section class="panel-card ep-glow-card flex flex-col p-6 lg:col-span-4" aria-labelledby="dash-faturamento">
                <div class="flex items-center justify-between gap-3">
                    <h2 id="dash-faturamento" class="text-[13px] font-medium text-[var(--ep-text-2)]">Faturamento</h2>
                    <span class="ep-chip">
                        <span class="h-1.5 w-1.5 rounded-full bg-[var(--ep-pos)]" aria-hidden="true" />
                        {{ periodLabel }}
                    </span>
                </div>

                <div class="mt-5 space-y-2">
                    <MoneyAmount
                        v-for="(row, i) in revenueRows"
                        :key="row.currency"
                        :value="row.total"
                        :currency="row.currency"
                        :hidden="!valuesVisible"
                        :size="i === 0 ? 'hero' : 'lg'"
                        class="block"
                    />
                </div>

                <div v-if="revenueDelta" class="mt-4 flex flex-wrap items-center gap-x-2 gap-y-1 text-[12.5px]">
                    <span class="ep-chip tabular-nums" :class="chipClass(revenueDelta)">
                        <ArrowUpRight v-if="revenueDelta.kind === 'up'" class="h-3.5 w-3.5" :stroke-width="2" aria-hidden="true" />
                        <ArrowDownRight v-else-if="revenueDelta.kind === 'down'" class="h-3.5 w-3.5" :stroke-width="2" aria-hidden="true" />
                        {{ revenueDelta.text }}
                    </span>
                    <span class="text-[var(--ep-text-3)]">
                        {{ comparacao.label }} · <span class="tabular-nums text-[var(--ep-text-2)]">{{ money(comparacao.vendas_totais) }}</span>
                    </span>
                </div>
                <p v-else class="mt-4 text-[12.5px] text-[var(--ep-text-3)]">Acumulado desde a primeira venda</p>

                <div v-if="weekBars.length" class="mt-6">
                    <div class="mb-3 flex items-baseline justify-between text-[11.5px]">
                        <span class="text-[var(--ep-text-3)]">Últimos 7 dias</span>
                        <span class="font-medium tabular-nums text-[var(--ep-text-2)]">{{ money(weekTotal) }}</span>
                    </div>
                    <div class="ep-bars" role="img" :aria-label="`Faturamento dos últimos 7 dias: ${weekBars.map((b) => b.title).join('; ')}`">
                        <div v-for="b in weekBars" :key="b.key" class="ep-bar" :class="{ 'ep-bar--today': b.today }" :title="b.title">
                            <div class="ep-bar__track"><div class="ep-bar__fill" :style="{ height: `${b.height}%` }" /></div>
                            <span class="ep-bar__label">{{ b.label }}</span>
                        </div>
                    </div>
                </div>

                <dl class="mt-6 grid grid-cols-3 gap-3 border-t border-[var(--ep-line)] pt-5 lg:mt-auto">
                    <div>
                        <dt class="text-[11.5px] text-[var(--ep-text-3)]">Vendas</dt>
                        <dd class="mt-1 flex items-baseline gap-1.5">
                            <span class="text-[17px] font-semibold tabular-nums tracking-[-0.02em] text-[var(--ep-text)]">{{ count(quantidade_vendas) }}</span>
                            <span
                                v-if="salesDelta && salesDelta.kind !== 'flat' && valuesVisible"
                                class="text-[11px] font-medium tabular-nums"
                                :class="salesDelta.kind === 'up' ? 'text-[var(--ep-pos)]' : 'text-[var(--ep-neg)]'"
                            >{{ salesDelta.text }}</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-[11.5px] text-[var(--ep-text-3)]">Ticket médio</dt>
                        <dd class="mt-1 truncate text-[17px] font-semibold tabular-nums tracking-[-0.02em] text-[var(--ep-text)]">{{ money(ticket_medio) }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11.5px] text-[var(--ep-text-3)]" title="Pix gerados e boletos emitidos ainda não pagos">Aguardando</dt>
                        <dd class="mt-1 truncate text-[17px] font-semibold tabular-nums tracking-[-0.02em] text-[var(--ep-text)]">
                            {{ money(vendas_pendentes) }}
                            <span v-if="pendentes_count" class="text-[11px] font-medium text-[var(--ep-text-4)]">({{ count(pendentes_count) }})</span>
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="panel-card ep-chart flex min-w-0 flex-col p-5 pb-3 lg:col-span-8" aria-labelledby="dash-grafico">
                <div class="flex flex-wrap items-center justify-between gap-3 px-1">
                    <h2 id="dash-grafico" class="text-[13px] font-medium text-[var(--ep-text-2)]">
                        Vendas aprovadas {{ isHourly ? 'por hora' : 'por dia' }}
                    </h2>
                    <div class="flex items-center gap-4 text-[12px] text-[var(--ep-text-3)]">
                        <span class="flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-[var(--ep-accent)]" aria-hidden="true" />
                            {{ periodLabel }}
                        </span>
                        <span v-if="hasPrevious" class="flex items-center gap-1.5">
                            <span class="h-0 w-3 border-t border-dashed border-[var(--ep-text-3)]" aria-hidden="true" />
                            {{ previousLabel }}
                        </span>
                    </div>
                </div>
                <div class="relative mt-3 min-h-[256px] flex-1">
                    <VueApexCharts
                        v-if="hasSales"
                        :key="`${isDarkMode}-${valuesVisible}-${period}`"
                        class="absolute inset-0"
                        type="line"
                        height="100%"
                        :options="chartOptions"
                        :series="chartSeries"
                    />
                    <div v-else class="flex h-full flex-col items-center justify-center px-6 text-center">
                        <svg class="h-10 w-28 text-[var(--ep-line-strong)]" viewBox="0 0 112 40" fill="none" aria-hidden="true">
                            <path d="M2 34 C 18 34, 22 22, 36 24 S 58 34, 70 20 S 94 8, 110 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="3 5" />
                        </svg>
                        <p class="mt-3 text-[13px] font-medium text-[var(--ep-text-2)]">Sem vendas aprovadas {{ periodLabel.toLowerCase() }}.</p>
                        <p class="mt-1 max-w-[280px] text-[12.5px] text-[var(--ep-text-4)]">O gráfico aparece a partir do primeiro pagamento confirmado.</p>
                    </div>
                </div>
            </section>
        </div>

        <!-- Linha 2: últimas vendas + formas de pagamento -->
        <div class="grid gap-4 lg:grid-cols-12">
            <section class="panel-card ep-data flex flex-col lg:col-span-7" aria-labelledby="dash-recentes">
                <div class="flex items-center justify-between gap-3 px-5 pt-5">
                    <h2 id="dash-recentes" class="text-[13px] font-medium text-[var(--ep-text-2)]">Últimas vendas</h2>
                    <Link href="/vendas" class="flex items-center gap-1 text-[12px] font-medium text-[var(--ep-text-3)] transition-colors duration-150 hover:text-[var(--ep-text)]">
                        Ver todas <ChevronRight class="h-3.5 w-3.5" :stroke-width="1.75" aria-hidden="true" />
                    </Link>
                </div>
                <ul v-if="vendas_recentes.length" class="mt-3 flex-1 px-2 pb-2">
                    <li
                        v-for="v in vendas_recentes"
                        :key="v.id"
                        class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition-colors duration-150 hover:bg-[var(--ep-hover)]"
                    >
                        <span v-avatar="v.cliente" class="ep-avatar shrink-0" aria-hidden="true">{{ initials(v.cliente) }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[13px] font-medium text-[var(--ep-text)]">{{ v.cliente }}</p>
                            <p class="truncate text-[12px] text-[var(--ep-text-3)]">{{ v.produto }}</p>
                        </div>
                        <span class="ep-chip hidden shrink-0 sm:inline-flex">
                            <span class="h-1.5 w-1.5 rounded-full" :style="{ background: methodColor[v.metodo] ?? 'var(--ep-text-4)' }" aria-hidden="true" />
                            {{ methodText(v) }}
                        </span>
                        <span class="ep-chip shrink-0" :class="statusOf(v.status).cls">{{ statusOf(v.status).label }}</span>
                        <div class="w-[108px] shrink-0 text-right">
                            <MoneyAmount :value="v.valor" :currency="v.moeda" :hidden="!valuesVisible" size="md" />
                            <p class="text-[11px] tabular-nums text-[var(--ep-text-4)]" :title="absolute(v.criado_em)">{{ relative(v.criado_em) }}</p>
                        </div>
                    </li>
                </ul>
                <div v-else class="flex flex-1 flex-col items-center justify-center px-6 py-10 text-center">
                    <p class="text-[13px] font-medium text-[var(--ep-text-2)]">Nenhuma venda ainda.</p>
                    <p class="mt-1 text-[12.5px] text-[var(--ep-text-4)]">As vendas aparecem aqui assim que o primeiro checkout for concluído.</p>
                </div>
            </section>

            <div class="flex flex-col gap-4 lg:col-span-5">
                <section class="panel-card flex flex-col p-5" aria-labelledby="dash-formas">
                    <div class="flex items-center justify-between gap-3">
                        <h2 id="dash-formas" class="text-[13px] font-medium text-[var(--ep-text-2)]">Formas de pagamento</h2>
                        <span class="text-[12px] tabular-nums text-[var(--ep-text-4)]">{{ count(quantidade_vendas) }} vendas</span>
                    </div>
                    <div
                        class="mt-4 flex h-2.5 w-full gap-[3px] overflow-hidden rounded-full bg-[var(--ep-active)]"
                        role="img"
                        :aria-label="paymentRows.length ? paymentRows.map((r) => `${r.label} ${Math.round(r.share)}%`).join(', ') : 'Sem pagamentos no período'"
                    >
                        <span
                            v-for="row in paymentRows"
                            :key="row.metodo"
                            class="h-full rounded-full"
                            :style="{ width: `${Math.max(row.share, 1.5)}%`, background: row.color, boxShadow: `0 0 12px ${row.color}` }"
                        />
                    </div>
                    <ul v-if="paymentRows.length" class="mt-3">
                        <li v-for="row in paymentRows" :key="row.metodo" class="flex items-center gap-2.5 py-1.5 text-[13px]">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="{ background: row.color, boxShadow: `0 0 10px ${row.color}` }" aria-hidden="true" />
                            <span class="min-w-0 flex-1 truncate font-medium text-[var(--ep-text)]">{{ row.label }}</span>
                            <span class="w-12 text-right text-[12px] tabular-nums text-[var(--ep-text-4)]">{{ valuesVisible ? `${Math.round(row.share)}%` : '—' }}</span>
                            <span class="w-16 whitespace-nowrap text-right text-[12px] tabular-nums text-[var(--ep-text-3)]">{{ count(row.quantidade) }} {{ row.quantidade === 1 ? 'venda' : 'vendas' }}</span>
                            <span class="w-28 text-right font-medium tabular-nums text-[var(--ep-text)]">{{ money(row.total) }}</span>
                        </li>
                    </ul>
                    <p v-else class="mt-4 text-[12.5px] text-[var(--ep-text-4)]">Nenhum pagamento aprovado {{ periodLabel.toLowerCase() }}.</p>
                </section>

                <section class="panel-card flex flex-1 items-center gap-5 p-5" aria-labelledby="dash-conversao">
                    <div class="relative h-[96px] w-[96px] shrink-0" aria-hidden="true">
                        <svg viewBox="0 0 104 104" class="h-full w-full -rotate-90">
                            <defs>
                                <linearGradient id="ep-ring" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0%" stop-color="var(--ep-accent)" />
                                    <stop offset="100%" stop-color="var(--ep-accent-2)" />
                                </linearGradient>
                            </defs>
                            <circle cx="52" cy="52" :r="RING_R" fill="none" stroke="var(--ep-active)" stroke-width="8" />
                            <circle
                                cx="52" cy="52" :r="RING_R" fill="none" stroke="url(#ep-ring)" stroke-width="8" stroke-linecap="round"
                                :stroke-dasharray="RING_C" :stroke-dashoffset="ringOffset"
                                style="filter: drop-shadow(0 0 8px var(--ep-glow)); transition: stroke-dashoffset 700ms cubic-bezier(0.23, 1, 0.32, 1);"
                            />
                        </svg>
                        <span class="absolute inset-0 flex items-center justify-center text-[18px] font-semibold tabular-nums tracking-[-0.03em] text-[var(--ep-text)]">
                            {{ valuesVisible ? `${pctFmt.format(taxa_conversao ?? 0)}%` : '—' }}
                        </span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 id="dash-conversao" class="text-[13px] font-medium text-[var(--ep-text-2)]">Conversão do checkout</h2>
                        <p class="mt-0.5 text-[12px] text-[var(--ep-text-3)]">dos checkouts abertos viraram venda</p>
                        <ul v-if="funnelSteps.length" class="mt-3.5 space-y-2.5">
                            <li v-for="(st, i) in funnelSteps" :key="st.key">
                                <div class="flex items-baseline justify-between gap-3 text-[12px]">
                                    <span class="text-[var(--ep-text-3)]">{{ st.label }}</span>
                                    <span class="tabular-nums">
                                        <span class="font-semibold text-[var(--ep-text)]">{{ count(st.value) }}</span>
                                        <span v-if="i > 0" class="ml-1.5 text-[var(--ep-text-4)]">{{ valuesVisible ? `${pctFmt.format(st.pct)}%` : '' }}</span>
                                    </span>
                                </div>
                                <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-[var(--ep-active)]">
                                    <div
                                        class="h-full rounded-full bg-gradient-to-r from-[var(--ep-accent)] to-[var(--ep-accent-2)]"
                                        :style="{ width: `${valuesVisible ? Math.max(st.pct, 1.5) : 0}%`, opacity: 1 - i * 0.12 }"
                                    />
                                </div>
                            </li>
                        </ul>
                        <div class="mt-3 flex items-center justify-between border-t border-[var(--ep-line)] pt-3 text-[12px]">
                            <span class="text-[var(--ep-text-3)]">Carrinhos abandonados</span>
                            <span class="font-semibold tabular-nums text-[var(--ep-text)]">{{ count(abandono_carrinho) }}</span>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <!-- Linha 3: top produtos + reembolsos -->
        <div class="grid gap-4 lg:grid-cols-12">
            <section class="panel-card flex flex-col p-5 lg:col-span-7" aria-labelledby="dash-top">
                <div class="flex items-center justify-between gap-3">
                    <h2 id="dash-top" class="text-[13px] font-medium text-[var(--ep-text-2)]">
                        Top produtos <span class="ml-1 text-[12px] font-normal tabular-nums text-[var(--ep-text-4)]">de {{ count(quantidade_produtos) }} cadastrados</span>
                    </h2>
                    <Link href="/produtos" class="flex items-center gap-1 text-[12px] font-medium text-[var(--ep-text-3)] transition-colors duration-150 hover:text-[var(--ep-text)]">
                        Ver todos <ChevronRight class="h-3.5 w-3.5" :stroke-width="1.75" aria-hidden="true" />
                    </Link>
                </div>
                <ul v-if="topRows.length" class="mt-4 space-y-3.5">
                    <li v-for="p in topRows" :key="p.nome">
                        <div class="flex items-baseline justify-between gap-3 text-[13px]">
                            <span class="min-w-0 truncate font-medium text-[var(--ep-text)]">{{ p.nome }}</span>
                            <span class="shrink-0 font-medium tabular-nums text-[var(--ep-text)]">{{ money(p.total) }}</span>
                        </div>
                        <div class="mt-1.5 flex items-center gap-3">
                            <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-[var(--ep-active)]">
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-[var(--ep-accent)] to-[var(--ep-accent-2)]"
                                    :style="{ width: `${valuesVisible ? p.width : 0}%` }"
                                />
                            </div>
                            <span class="w-[72px] shrink-0 whitespace-nowrap text-right text-[11.5px] tabular-nums text-[var(--ep-text-4)]">{{ count(p.quantidade) }} {{ p.quantidade === 1 ? 'venda' : 'vendas' }}</span>
                        </div>
                    </li>
                </ul>
                <p v-else class="mt-4 text-[12.5px] text-[var(--ep-text-4)]">Nenhum produto vendido {{ periodLabel.toLowerCase() }}.</p>
            </section>

            <Link href="/reembolsos" class="panel-card group flex flex-col p-5 transition-colors duration-150 hover:border-[var(--ep-line-strong)] lg:col-span-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-[13px] font-medium text-[var(--ep-text-2)]">Reembolsos</h2>
                    <span class="flex items-center gap-1 text-[12px] font-medium text-[var(--ep-text-3)] transition-colors duration-150 group-hover:text-[var(--ep-text)]">
                        Ver reembolsos <ChevronRight class="h-3.5 w-3.5 transition-transform duration-150 group-hover:translate-x-0.5" :stroke-width="1.75" aria-hidden="true" />
                    </span>
                </div>
                <div class="mt-4 flex items-baseline justify-between gap-3">
                    <MoneyAmount :value="reembolsos_total" :hidden="!valuesVisible" size="lg" />
                    <span class="text-[12px] tabular-nums text-[var(--ep-text-4)]">{{ count(reembolsos_count) }} {{ reembolsos_count === 1 ? 'pedido' : 'pedidos' }} {{ periodLabel.toLowerCase() }}</span>
                </div>
                <div class="mt-auto pt-5">
                    <div class="flex items-baseline justify-between text-[12px]">
                        <span class="text-[var(--ep-text-3)]">Taxa de reembolso</span>
                        <span class="font-semibold tabular-nums text-[var(--ep-text)]">{{ valuesVisible ? `${pctFmt.format(refundRate)}%` : '—' }}</span>
                    </div>
                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-[var(--ep-active)]">
                        <div class="h-full rounded-full bg-[var(--ep-neg)]" :style="{ width: `${valuesVisible ? Math.min(100, Math.max(refundRate, 1)) : 0}%` }" />
                    </div>
                    <p class="mt-1.5 text-[11.5px] text-[var(--ep-text-4)]">pedidos reembolsados sobre aprovados + reembolsados no período</p>
                </div>
            </Link>
        </div>
    </div>
</template>
