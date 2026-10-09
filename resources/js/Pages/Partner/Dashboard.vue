<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import VueApexCharts from 'vue3-apexcharts';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import DashboardPeriodFilter from '@/components/dashboard/DashboardPeriodFilter.vue';
import MoneyAmount from '@/components/ui/MoneyAmount.vue';
import {
    CircleDollarSign,
    ShoppingCart,
    Wallet,
    Clock,
    Package,
    Eye,
    EyeOff,
    ArrowRight,
} from 'lucide-vue-next';

defineOptions({ layout: LayoutInfoprodutor });

const page = usePage();
const valuesVisible = ref(true);
const isDarkMode = ref(false);

onMounted(() => {
    isDarkMode.value = document.documentElement.classList.contains('dark');
});

const props = defineProps({
    period: { type: String, default: 'hoje' },
    comissao_total: { type: Number, default: 0 },
    quantidade_vendas: { type: Number, default: 0 },
    ticket_medio_comissao: { type: Number, default: 0 },
    saldo_pendente: { type: Number, default: 0 },
    saldo_disponivel: { type: Number, default: 0 },
    quantidade_produtos: { type: Number, default: 0 },
    grafico_comissoes: { type: Array, default: () => [] },
    vendas_recentes: { type: Array, default: () => [] },
});

function setPeriod(value) {
    router.get('/parceiro', { period: value }, { preserveState: false });
}

function formatBRL(value) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value ?? 0);
}

function displayCurrency(value) {
    return valuesVisible.value ? formatBRL(value) : '••••••';
}

function displayNumber(value) {
    return valuesVisible.value ? String(value) : '—';
}

function formatDate(iso) {
    if (!iso) return '—';
    try {
        return new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(iso));
    } catch {
        return iso;
    }
}

function commissionStatusLabel(status) {
    if (status === 'pending') return 'Pendente';
    if (status === 'available') return 'Disponível';
    if (status === 'paid') return 'Pago';
    return status || '';
}

const chartPrimaryColor = computed(() => {
    const fromSettings = page.props.appSettings?.theme_primary;
    if (typeof fromSettings === 'string' && fromSettings.trim() !== '') {
        return fromSettings.trim();
    }
    if (typeof document !== 'undefined') {
        const css = getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim();
        if (css) return css;
    }
    return '#0ea5e9';
});

const chartSeries = computed(() => [
    {
        name: 'Comissões',
        data: valuesVisible.value
            ? props.grafico_comissoes.map((d) => d.total)
            : props.grafico_comissoes.map(() => 0),
    },
]);

const chartOptions = computed(() => {
    const primary = chartPrimaryColor.value;
    const isHourly = props.period === 'hoje' || props.period === 'ontem';

    return {
        chart: {
            type: 'area',
            toolbar: { show: false },
            zoom: { enabled: false },
            fontFamily: 'inherit',
            animations: { enabled: true, speed: 600 },
        },
        colors: [primary],
        dataLabels: {
            enabled: true,
            formatter: (v) => (valuesVisible.value && v > 0 ? formatBRL(v) : ''),
            style: { fontSize: '10px', colors: [primary] },
            offsetY: -4,
        },
        stroke: { curve: 'smooth', width: 2 },
        fill: {
            type: 'gradient',
            gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0, stops: [0, 100] },
        },
        xaxis: {
            categories: isHourly
                ? props.grafico_comissoes.map((d) => `${Number(d.data)}h`)
                : props.grafico_comissoes.map((d) => {
                      const [, m, day] = (d.data || '').split('-');
                      return day && m ? `${day}/${m}` : d.data;
                  }),
            labels: { style: { colors: '#71717a', fontSize: '11px' } },
        },
        yaxis: {
            labels: {
                style: { colors: '#71717a', fontSize: '11px' },
                formatter: (v) => formatBRL(v),
            },
        },
        grid: {
            borderColor: isDarkMode.value ? '#27272a' : '#e4e4e7',
            strokeDashArray: 4,
        },
        tooltip: {
            theme: isDarkMode.value ? 'dark' : 'light',
            y: { formatter: (v) => (valuesVisible.value ? formatBRL(v) : '••••••') },
        },
    };
});
</script>

<template>
    <div class="space-y-5">
        <header class="min-w-0">
            <h1 class="text-[22px] font-semibold tracking-[-0.025em] text-[var(--ep-text)]">Dashboard</h1>
            <p class="mt-1 text-[13px] text-[var(--ep-text-3)]">
                Resumo das suas comissões e vendas como parceiro.
            </p>
        </header>

        <DashboardPeriodFilter class="min-w-0" :model-value="period" @update:model-value="setPeriod">
            <template #trailing>
                <button
                    type="button"
                    :aria-label="valuesVisible ? 'Ocultar valores' : 'Mostrar valores'"
                    class="ep-glass flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-[var(--ep-text-3)] transition-colors duration-150 hover:text-[var(--ep-text)] active:scale-[0.97]"
                    @click="valuesVisible = !valuesVisible"
                >
                    <Eye v-if="valuesVisible" class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                    <EyeOff v-else class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                </button>
            </template>
        </DashboardPeriodFilter>

        <!-- Linha 1: comissões (herói) + gráfico -->
        <div class="grid gap-4 lg:grid-cols-12">
            <section class="panel-card ep-glow-card flex flex-col p-6 lg:col-span-4" aria-labelledby="parceiro-comissoes">
                <div class="flex items-center justify-between gap-3">
                    <h2 id="parceiro-comissoes" class="text-[13px] font-medium text-[var(--ep-text-2)]">Comissões no período</h2>
                    <span class="ep-kpi__icon" aria-hidden="true">
                        <CircleDollarSign class="h-4 w-4" :stroke-width="1.75" />
                    </span>
                </div>

                <MoneyAmount :value="comissao_total" :hidden="!valuesVisible" size="hero" class="mt-5 block" />

                <p class="mt-4 text-[12.5px] text-[var(--ep-text-3)]">
                    Ticket médio · <span class="tabular-nums text-[var(--ep-text-2)]">{{ displayCurrency(ticket_medio_comissao) }}</span>
                </p>

                <dl class="mt-6 grid grid-cols-2 gap-3 border-t border-[var(--ep-line)] pt-5 lg:mt-auto">
                    <div>
                        <dt class="text-[11.5px] text-[var(--ep-text-3)]">Vendas</dt>
                        <dd class="mt-1 text-[17px] font-semibold tabular-nums tracking-[-0.02em] text-[var(--ep-text)]">{{ displayNumber(quantidade_vendas) }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11.5px] text-[var(--ep-text-3)]">Produtos</dt>
                        <dd class="mt-1 text-[17px] font-semibold tabular-nums tracking-[-0.02em] text-[var(--ep-text)]">{{ quantidade_produtos }}</dd>
                    </div>
                </dl>
            </section>

            <section class="panel-card ep-chart flex min-w-0 flex-col p-5 pb-3 lg:col-span-8" aria-labelledby="parceiro-grafico">
                <div class="flex flex-wrap items-center justify-between gap-3 px-1">
                    <h2 id="parceiro-grafico" class="text-[13px] font-medium text-[var(--ep-text-2)]">Evolução das comissões</h2>
                    <span class="flex items-center gap-1.5 text-[12px] text-[var(--ep-text-3)]">
                        <span class="h-2 w-2 rounded-full bg-[var(--ep-accent)]" aria-hidden="true" />
                        Comissões
                    </span>
                </div>
                <div v-if="grafico_comissoes.length" class="-mx-1 mt-3 min-h-[260px] flex-1">
                    <VueApexCharts type="area" height="260" :options="chartOptions" :series="chartSeries" />
                </div>
                <div v-else class="ep-empty flex-1">
                    <svg class="h-10 w-28 text-[var(--ep-line-strong)]" viewBox="0 0 112 40" fill="none" aria-hidden="true">
                        <path d="M2 34 C 18 34, 22 22, 36 24 S 58 34, 70 20 S 94 8, 110 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="3 5" />
                    </svg>
                    <p class="ep-empty__title mt-2">Nenhuma comissão neste período.</p>
                    <p class="ep-empty__text">O gráfico aparece a partir da primeira comissão gerada pelas suas vendas.</p>
                </div>
            </section>
        </div>

        <!-- Linha 2: KPIs subordinados -->
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="panel-card ep-kpi">
                <div class="flex items-start justify-between gap-3">
                    <span class="ep-kpi__label">Vendas no período</span>
                    <span class="ep-kpi__icon" aria-hidden="true"><ShoppingCart class="h-4 w-4" :stroke-width="1.75" /></span>
                </div>
                <p class="ep-kpi__value">{{ displayNumber(quantidade_vendas) }}</p>
                <Link href="/parceiro/vendas" class="ep-kpi__meta inline-flex w-fit items-center gap-1 font-medium text-[var(--ep-text-3)] transition-colors duration-150 hover:text-[var(--ep-text)]">
                    Ver todas <ArrowRight class="h-3 w-3" :stroke-width="1.75" />
                </Link>
            </div>
            <div class="panel-card ep-kpi">
                <div class="flex items-start justify-between gap-3">
                    <span class="ep-kpi__label">Saldo pendente</span>
                    <span
                        class="ep-kpi__icon"
                        style="color: var(--ep-warn); background: var(--ep-warn-bg); border-color: color-mix(in oklab, var(--ep-warn) 28%, transparent); box-shadow: none"
                        aria-hidden="true"
                    ><Clock class="h-4 w-4" :stroke-width="1.75" /></span>
                </div>
                <MoneyAmount :value="saldo_pendente" :hidden="!valuesVisible" size="lg" class="block" />
                <span class="ep-kpi__meta">Aguardando liberação</span>
            </div>
            <div class="panel-card ep-kpi">
                <div class="flex items-start justify-between gap-3">
                    <span class="ep-kpi__label">Saldo disponível</span>
                    <span
                        class="ep-kpi__icon"
                        style="color: var(--ep-pos); background: var(--ep-pos-bg); border-color: color-mix(in oklab, var(--ep-pos) 28%, transparent); box-shadow: none"
                        aria-hidden="true"
                    ><Wallet class="h-4 w-4" :stroke-width="1.75" /></span>
                </div>
                <MoneyAmount :value="saldo_disponivel" :hidden="!valuesVisible" size="lg" class="block" />
                <Link href="/parceiro/financeiro" class="ep-kpi__meta inline-flex w-fit items-center gap-1 font-medium text-[var(--ep-accent)] transition-opacity duration-150 hover:opacity-80">
                    Sacar <ArrowRight class="h-3 w-3" :stroke-width="1.75" />
                </Link>
            </div>
        </div>

        <!-- Linha 3: vendas recentes + atalhos -->
        <div class="grid gap-4 lg:grid-cols-12">
            <section class="panel-card ep-data flex min-w-0 flex-col overflow-hidden lg:col-span-8" aria-labelledby="parceiro-recentes">
                <div class="flex items-center justify-between gap-3 px-5 py-4">
                    <h2 id="parceiro-recentes" class="text-[13px] font-medium text-[var(--ep-text-2)]">Vendas recentes</h2>
                    <Link href="/parceiro/vendas" class="flex items-center gap-1 text-[12px] font-medium text-[var(--ep-text-3)] transition-colors duration-150 hover:text-[var(--ep-text)]">
                        Ver todas <ArrowRight class="h-3.5 w-3.5" :stroke-width="1.75" />
                    </Link>
                </div>
                <div v-if="vendas_recentes.length" class="overflow-x-auto">
                    <table class="ep-table">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th class="hidden md:table-cell">Data</th>
                                <th>Status</th>
                                <th class="ep-num">Comissão</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="v in vendas_recentes" :key="v.id">
                                <td class="max-w-[280px]">
                                    <p class="truncate font-medium text-[var(--ep-text)]">{{ v.product_name || 'Produto' }}</p>
                                    <p v-if="v.buyer_name || v.buyer_email" class="truncate text-[12px] text-[var(--ep-text-3)]">
                                        {{ v.buyer_name || v.buyer_email }}
                                        <span v-if="v.buyer_masked" class="text-[var(--ep-text-4)]"> (mascarado)</span>
                                    </p>
                                    <p class="text-[11.5px] tabular-nums text-[var(--ep-text-4)] md:hidden">{{ formatDate(v.created_at) }}</p>
                                </td>
                                <td class="hidden whitespace-nowrap text-[12.5px] tabular-nums text-[var(--ep-text-3)] md:table-cell">{{ formatDate(v.created_at) }}</td>
                                <td>
                                    <span
                                        class="ep-chip"
                                        :class="v.commission_status === 'paid' ? 'ep-chip--pos' : v.commission_status === 'available' ? 'ep-chip--accent' : v.commission_status === 'pending' ? 'ep-chip--warn' : ''"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                                        {{ commissionStatusLabel(v.commission_status) }}
                                    </span>
                                </td>
                                <td class="ep-num font-medium tabular-nums text-[var(--ep-text)]">{{ displayCurrency(v.commission_amount) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="ep-empty flex-1 border-t border-[var(--ep-line)]">
                    <p class="ep-empty__title">Nenhuma venda registrada ainda.</p>
                    <p class="ep-empty__text">Suas vendas como parceiro aparecem aqui assim que o primeiro pedido for aprovado.</p>
                </div>
            </section>

            <section class="panel-card flex flex-col p-5 lg:col-span-4" aria-labelledby="parceiro-atalhos">
                <h2 id="parceiro-atalhos" class="text-[13px] font-medium text-[var(--ep-text-2)]">Atalhos</h2>
                <ul class="-mx-2 mt-3 space-y-0.5">
                    <li>
                        <Link
                            href="/parceiro/produtos"
                            class="group flex items-center justify-between gap-3 rounded-xl px-3 py-2.5 text-[13px] transition-colors duration-150 hover:bg-[var(--ep-hover)]"
                        >
                            <span class="flex items-center gap-2.5 font-medium text-[var(--ep-text)]">
                                <Package class="h-4 w-4 text-[var(--ep-accent)]" :stroke-width="1.75" />
                                Meus produtos
                            </span>
                            <span class="ep-chip tabular-nums">{{ quantidade_produtos }}</span>
                        </Link>
                    </li>
                    <li>
                        <Link
                            href="/parceiro/vendas"
                            class="group flex items-center justify-between gap-3 rounded-xl px-3 py-2.5 text-[13px] transition-colors duration-150 hover:bg-[var(--ep-hover)]"
                        >
                            <span class="flex items-center gap-2.5 font-medium text-[var(--ep-text)]">
                                <ShoppingCart class="h-4 w-4 text-[var(--ep-accent)]" :stroke-width="1.75" />
                                Vendas
                            </span>
                            <ArrowRight class="h-4 w-4 text-[var(--ep-text-4)] transition-transform duration-150 group-hover:translate-x-0.5 group-hover:text-[var(--ep-text-2)]" :stroke-width="1.75" />
                        </Link>
                    </li>
                    <li>
                        <Link
                            href="/parceiro/financeiro"
                            class="group flex items-center justify-between gap-3 rounded-xl px-3 py-2.5 text-[13px] transition-colors duration-150 hover:bg-[var(--ep-hover)]"
                        >
                            <span class="flex items-center gap-2.5 font-medium text-[var(--ep-text)]">
                                <Wallet class="h-4 w-4 text-[var(--ep-accent)]" :stroke-width="1.75" />
                                Financeiro
                            </span>
                            <ArrowRight class="h-4 w-4 text-[var(--ep-text-4)] transition-transform duration-150 group-hover:translate-x-0.5 group-hover:text-[var(--ep-text-2)]" :stroke-width="1.75" />
                        </Link>
                    </li>
                </ul>
            </section>
        </div>
    </div>
</template>
