<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import VueApexCharts from 'vue3-apexcharts';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import MoneyAmount from '@/components/ui/MoneyAmount.vue';
import {
    CircleDollarSign,
    ShoppingCart,
    CreditCard,
    RotateCcw,
    Package,
    Users,
    TrendingUp,
    Eye,
    EyeOff,
    XCircle,
    Download,
    BarChart3,
} from 'lucide-vue-next';
import { ChevronDown } from 'lucide-vue-next';

defineOptions({ layout: LayoutInfoprodutor });

const valuesVisible = ref(true);
const isDarkMode = ref(false);
const chartReceitaMode = ref('bruto');

onMounted(() => {
    isDarkMode.value = document.documentElement.classList.contains('dark');
});

const props = defineProps({
    period: { type: String, default: 'hoje' },
    date_from: { type: String, default: '' },
    date_to: { type: String, default: '' },
    receita_total: { type: Number, default: 0 },
    receita_bruta: { type: Number, default: 0 },
    taxas_gateway: { type: Number, default: 0 },
    receita_liquida: { type: Number, default: 0 },
    quantidade_vendas: { type: Number, default: 0 },
    ticket_medio: { type: Number, default: 0 },
    total_alunos: { type: Number, default: 0 },
    total_produtos: { type: Number, default: 0 },
    formas_pagamento: { type: Array, default: () => [] },
    grafico_receita: { type: Array, default: () => [] },
    grafico_receita_liquida: { type: Array, default: () => [] },
    receita_por_produto: { type: Array, default: () => [] },
    abandonados_visit: { type: Number, default: 0 },
    abandonados_form: { type: Number, default: 0 },
    abandonados_total: { type: Number, default: 0 },
    taxa_conversao: { type: Number, default: 0 },
    abandonados_com_email: { type: Array, default: () => [] },
    reembolsos_count: { type: Number, default: 0 },
    reembolsos_total: { type: Number, default: 0 },
    meta_export_products: { type: Array, default: () => [] },
    order_bump_report: {
        type: Object,
        default: () => ({ products: [], selected_product_id: null, eligible_orders: 0, accepted_items: 0, bumps: [] }),
    },
});

const META_HEADER =
    'email,email,email,phone,phone,phone,madid,fn,ln,zip,ct,st,country,dob,doby,gen,age,uid,value';

const showModalCompradores = ref(false);
const showModalAbandonos = ref(false);
const productCompradores = ref('');
const productAbandonos = ref('');
const customDateFrom = ref(props.date_from || new Date().toISOString().slice(0, 10));
const customDateTo = ref(props.date_to || new Date().toISOString().slice(0, 10));
const orderBumpProductId = ref(props.order_bump_report.selected_product_id || '');

function openModalCompradores() {
    productCompradores.value = props.meta_export_products[0]?.id ?? '';
    showModalCompradores.value = true;
}

function openModalAbandonos() {
    productAbandonos.value = props.meta_export_products[0]?.id ?? '';
    showModalAbandonos.value = true;
}

function downloadMetaCompradores() {
    if (!productCompradores.value) return;
    window.location.href = `/relatorios/export/meta-compradores?product_id=${encodeURIComponent(productCompradores.value)}`;
}

function downloadMetaAbandonos() {
    if (!productAbandonos.value) return;
    window.location.href = `/relatorios/export/meta-abandonos?product_id=${encodeURIComponent(productAbandonos.value)}`;
}

const periodOptions = [
    { value: 'hoje', label: 'Hoje' },
    { value: 'ontem', label: 'Ontem' },
    { value: '7dias', label: '7 dias' },
    { value: 'mes', label: 'Mês' },
    { value: 'ano', label: 'Ano' },
    { value: 'total', label: 'Total' },
    { value: 'personalizado', label: 'Personalizado' },
];

function setPeriod(value) {
    const params = { period: value };
    if (orderBumpProductId.value) params.order_bump_product_id = orderBumpProductId.value;
    if (value === 'personalizado') {
        params.date_from = customDateFrom.value;
        params.date_to = customDateTo.value;
    }
    router.get('/relatorios', params, { preserveState: false });
}

function applyCustomPeriod() {
    if (!customDateFrom.value || !customDateTo.value) return;
    setPeriod('personalizado');
}

function setOrderBumpProduct() {
    const params = {
        period: props.period,
        order_bump_product_id: orderBumpProductId.value,
    };
    if (props.period === 'personalizado') {
        params.date_from = customDateFrom.value;
        params.date_to = customDateTo.value;
    }
    router.get('/relatorios', params, { preserveState: false });
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
    if (!iso) return '–';
    const d = new Date(iso);
    return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

const chartReceitaData = computed(() =>
    chartReceitaMode.value === 'liquido' ? props.grafico_receita_liquida : props.grafico_receita
);

const chartSeriesReceita = computed(() => [
    {
        name: chartReceitaMode.value === 'liquido' ? 'Receita líquida' : 'Faturamento bruto',
        data: valuesVisible.value ? chartReceitaData.value.map((d) => d.total) : chartReceitaData.value.map(() => 0),
    },
]);

const brlCompactRel = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL', notation: 'compact', maximumFractionDigits: 1 });
/** Com 1–2 dias não existe "curva": mostra colunas em vez de um ponto solto. */
const chartReceitaType = computed(() => (chartReceitaData.value.length <= 2 ? 'bar' : 'area'));

const chartOptionsReceita = computed(() => ({
    chart: { type: chartReceitaType.value, toolbar: { show: false }, zoom: { enabled: false }, fontFamily: 'inherit', background: 'transparent', animations: { enabled: false } },
    colors: [isDarkMode.value ? '#6ba4ff' : '#2f6fe4'],
    dataLabels: { enabled: false },
    stroke: { curve: 'monotoneCubic', width: chartReceitaType.value === 'bar' ? 0 : 2.25 },
    plotOptions: { bar: { columnWidth: '18%', borderRadius: 6, borderRadiusApplication: 'end' } },
    fill: chartReceitaType.value === 'bar'
        ? { type: 'gradient', gradient: { type: 'vertical', shadeIntensity: 0, gradientToColors: [isDarkMode.value ? '#2b5fd9' : '#4f8cff'], opacityFrom: 1, opacityTo: 0.85, stops: [0, 100] } }
        : { type: 'gradient', gradient: { shadeIntensity: 0, opacityFrom: isDarkMode.value ? 0.42 : 0.3, opacityTo: 0, stops: [0, 95] } },
    xaxis: {
        categories: chartReceitaData.value.map((d) => {
            const [y, m, day] = (d.data || '').split('-');
            return day && m ? `${day}/${m}` : d.data;
        }),
        labels: { style: { colors: '#71717a' } },
    },
    yaxis: { min: 0, tickAmount: 4, forceNiceScale: true, labels: { style: { colors: '#71717a' }, formatter: (v) => (valuesVisible.value ? brlCompactRel.format(v ?? 0) : '') } },
    grid: { borderColor: isDarkMode.value ? 'rgba(255,255,255,0.07)' : 'rgba(15,21,48,0.08)', strokeDashArray: 3, xaxis: { lines: { show: false } } },
    tooltip: {
        theme: isDarkMode.value ? 'dark' : 'light',
        y: { formatter: (v) => (valuesVisible.value ? formatBRL(v) : '••••••') },
    },
}));

const chartSeriesProduto = computed(() => [
    {
        name: 'Receita',
        data: valuesVisible.value ? props.receita_por_produto.map((d) => d.total) : props.receita_por_produto.map(() => 0),
    },
]);

const chartOptionsProduto = computed(() => ({
    chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'inherit' },
    colors: ['var(--color-primary)'],
    dataLabels: { enabled: false },
    plotOptions: { bar: { horizontal: true } },
    xaxis: {
        categories: props.receita_por_produto.map((d) => (d.product_name || 'Produto').slice(0, 35)),
        labels: { style: { colors: '#71717a' }, formatter: (v) => formatBRL(v) },
    },
    yaxis: { labels: { style: { colors: '#71717a' }, maxWidth: 140 } },
    grid: { borderColor: 'var(--chart-grid, #e4e4e7)', strokeDashArray: 4 },
    tooltip: {
        theme: isDarkMode.value ? 'dark' : 'light',
        y: { formatter: (v) => (valuesVisible.value ? formatBRL(v) : '••••••') },
    },
}));

const formasFiltradas = computed(() => props.formas_pagamento.filter((fp) => fp.total > 0));

const chartSeriesFormas = computed(() =>
    valuesVisible.value ? formasFiltradas.value.map((fp) => fp.total) : formasFiltradas.value.map(() => 0)
);

const chartOptionsFormas = computed(() => ({
    chart: { type: 'donut', fontFamily: 'inherit' },
    labels: formasFiltradas.value.map((fp) => fp.label),
    colors: ['#10b981', '#6366f1', '#f59e0b', '#ef4444', '#8b5cf6'].slice(0, formasFiltradas.value.length) || ['#6366f1'],
    dataLabels: { enabled: false },
    legend: { position: 'bottom' },
    tooltip: { theme: isDarkMode.value ? 'dark' : 'light' },
}));
</script>

<template>
    <div class="space-y-5">
        <!-- Período + ações -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <nav class="ep-tabs max-w-full overflow-x-auto no-scrollbar" aria-label="Período">
                <button
                    v-for="opt in periodOptions"
                    :key="opt.value"
                    type="button"
                    :aria-current="period === opt.value ? 'true' : undefined"
                    class="ep-tab"
                    :class="period === opt.value ? 'ep-tab--active' : ''"
                    @click="setPeriod(opt.value)"
                >
                    {{ opt.label }}
                </button>
            </nav>
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    class="ep-btn-secondary"
                    :disabled="!meta_export_products.length"
                    @click="openModalCompradores"
                >
                    <Download class="h-4 w-4 shrink-0 text-[var(--ep-text-3)]" :stroke-width="1.75" aria-hidden="true" />
                    CSV clientes existentes
                </button>
                <button
                    type="button"
                    class="ep-btn-secondary"
                    :disabled="!meta_export_products.length"
                    @click="openModalAbandonos"
                >
                    <Download class="h-4 w-4 shrink-0 text-[var(--ep-text-3)]" :stroke-width="1.75" aria-hidden="true" />
                    CSV clientes engajados
                </button>
                <button
                    type="button"
                    :aria-label="valuesVisible ? 'Ocultar valores' : 'Mostrar valores'"
                    class="ep-btn-secondary ep-btn-icon text-[var(--ep-text-3)] hover:text-[var(--ep-text)]"
                    @click="valuesVisible = !valuesVisible"
                >
                    <Eye v-if="valuesVisible" class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                    <EyeOff v-else class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                </button>
            </div>
        </div>

        <form
            v-if="period === 'personalizado'"
            class="panel-card flex flex-wrap items-end gap-3 p-4"
            @submit.prevent="applyCustomPeriod"
        >
            <label class="ep-label mb-0">
                Data inicial
                <input
                    v-model="customDateFrom"
                    type="date"
                    required
                    :max="customDateTo || undefined"
                    class="ep-input mt-1.5 w-auto tabular-nums"
                />
            </label>
            <label class="ep-label mb-0">
                Data final
                <input
                    v-model="customDateTo"
                    type="date"
                    required
                    :min="customDateFrom || undefined"
                    class="ep-input mt-1.5 w-auto tabular-nums"
                />
            </label>
            <button
                type="submit"
                class="ep-btn h-[38px]"
            >
                Aplicar período
            </button>
        </form>

        <p
            v-if="!meta_export_products.length"
            class="flex items-start gap-2 rounded-xl border border-[color-mix(in_oklab,var(--ep-warn)_30%,transparent)] bg-[var(--ep-warn-bg)] px-4 py-2.5 text-[13px] text-[var(--ep-text-2)]"
        >
            <span class="mt-[7px] h-1.5 w-1.5 shrink-0 rounded-full bg-[var(--ep-warn)]" aria-hidden="true" />
            Não há produtos disponíveis para exportação (verifique permissões da equipe ou cadastre um produto).
        </p>

        <div
            v-if="showModalCompradores"
            class="ep-scrim fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="meta-modal-compradores-title"
            @click.self="showModalCompradores = false"
        >
            <div
                class="ep-modal max-h-[90vh] w-full max-w-lg overflow-y-auto p-6"
                @click.stop
            >
                <span class="ep-chip ep-chip--accent">
                    <Download class="h-3.5 w-3.5" :stroke-width="1.75" aria-hidden="true" />
                    Meta Ads
                </span>
                <h2 id="meta-modal-compradores-title" class="mt-3 text-[17px] font-semibold tracking-[-0.02em] text-[var(--ep-text)]">
                    Baixar CSV — clientes existentes (Meta Ads)
                </h2>
                <p class="mt-3 text-[13px] leading-relaxed text-[var(--ep-text-3)] [&_strong]:font-medium [&_strong]:text-[var(--ep-text)]">
                    Será gerado um arquivo no formato de lista de clientes do Meta, com <strong>compradores que concluíram o
                        pagamento</strong> do produto selecionado nos <strong>últimos 180 dias</strong>. Em caso de mais de um
                    pedido no período, usa-se o <strong>pedido mais recente</strong> por e-mail (valor na coluna
                    <code class="rounded-md border border-[var(--ep-line)] bg-[var(--ep-card-2)] px-1.5 py-px font-mono text-[11.5px] text-[var(--ep-text-2)]">value</code>).
                </p>
                <label class="ep-label mt-5" for="meta-product-compradores"
                    >Produto</label
                >
                <div class="relative">
                    <select
                        id="meta-product-compradores"
                        v-model="productCompradores"
                        class="ep-input appearance-none pr-9"
                    >
                        <option v-for="p in meta_export_products" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                    <ChevronDown class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--ep-text-4)]" :stroke-width="1.75" aria-hidden="true" />
                </div>
                <p class="ep-help break-all font-mono text-[11px]">
                    Cabeçalho do arquivo: {{ META_HEADER }}
                </p>
                <div class="mt-6 flex flex-wrap justify-end gap-2 border-t border-[var(--ep-line)] pt-5">
                    <button
                        type="button"
                        class="ep-btn-ghost"
                        @click="showModalCompradores = false"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        class="ep-btn"
                        :disabled="!productCompradores"
                        @click="downloadMetaCompradores"
                    >
                        <Download class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                        Baixar CSV
                    </button>
                </div>
            </div>
        </div>

        <div
            v-if="showModalAbandonos"
            class="ep-scrim fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="meta-modal-abandonos-title"
            @click.self="showModalAbandonos = false"
        >
            <div
                class="ep-modal max-h-[90vh] w-full max-w-lg overflow-y-auto p-6"
                @click.stop
            >
                <span class="ep-chip ep-chip--accent">
                    <Download class="h-3.5 w-3.5" :stroke-width="1.75" aria-hidden="true" />
                    Meta Ads
                </span>
                <h2 id="meta-modal-abandonos-title" class="mt-3 text-[17px] font-semibold tracking-[-0.02em] text-[var(--ep-text)]">
                    Baixar CSV — clientes engajados (Meta Ads)
                </h2>
                <p class="mt-3 text-[13px] leading-relaxed text-[var(--ep-text-3)] [&_strong]:font-medium [&_strong]:text-[var(--ep-text)]">
                    Lista de quem <strong>iniciou o checkout</strong> (formulário), <strong>não concluiu a compra</strong> no
                    fluxo da sessão, com sessão criada nos <strong>últimos 180 dias</strong>, após o período de graça de
                    abandono. <strong>Não entram</strong> e-mails que tenham <strong>pedido concluído do mesmo produto
                        depois</strong> do momento do abandono (última interação no formulário).
                </p>
                <label class="ep-label mt-5" for="meta-product-abandonos"
                    >Produto</label
                >
                <div class="relative">
                    <select
                        id="meta-product-abandonos"
                        v-model="productAbandonos"
                        class="ep-input appearance-none pr-9"
                    >
                        <option v-for="p in meta_export_products" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                    <ChevronDown class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--ep-text-4)]" :stroke-width="1.75" aria-hidden="true" />
                </div>
                <p class="ep-help break-all font-mono text-[11px]">
                    Cabeçalho do arquivo: {{ META_HEADER }}
                </p>
                <div class="mt-6 flex flex-wrap justify-end gap-2 border-t border-[var(--ep-line)] pt-5">
                    <button
                        type="button"
                        class="ep-btn-ghost"
                        @click="showModalAbandonos = false"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        class="ep-btn"
                        :disabled="!productAbandonos"
                        @click="downloadMetaAbandonos"
                    >
                        <Download class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                        Baixar CSV
                    </button>
                </div>
            </div>
        </div>

        <!-- Linha 1: faturamento (herói) + receita por período -->
        <div class="grid gap-4 lg:grid-cols-12">
            <section class="panel-card ep-glow-card flex flex-col p-6 lg:col-span-4" aria-labelledby="rel-faturamento">
                <div class="flex items-center justify-between gap-3">
                    <h2 id="rel-faturamento" class="text-[13px] font-medium text-[var(--ep-text-2)]" title="Valor pago pelos clientes">Faturamento bruto</h2>
                    <span class="ep-chip">
                        <span class="h-1.5 w-1.5 rounded-full bg-[var(--ep-pos)]" aria-hidden="true" />
                        {{ periodOptions.find((o) => o.value === period)?.label || 'Período' }}
                    </span>
                </div>

                <MoneyAmount :value="receita_bruta || receita_total" :hidden="!valuesVisible" size="hero" class="mt-5 block" />
                <p class="mt-3 text-[12.5px] text-[var(--ep-text-3)]">Valor pago pelos clientes, antes das taxas do gateway</p>

                <dl class="mt-6 grid grid-cols-2 gap-3 border-t border-[var(--ep-line)] pt-5 lg:mt-auto">
                    <div class="min-w-0">
                        <dt class="text-[11.5px] text-[var(--ep-text-3)]" title="Taxas do gateway (reais ou estimadas)">Taxas gateway</dt>
                        <dd class="mt-1 truncate text-[17px] font-semibold tabular-nums tracking-[-0.02em] text-[var(--ep-text)]">{{ displayCurrency(taxas_gateway) }}</dd>
                    </div>
                    <div class="min-w-0">
                        <dt class="flex items-center gap-1.5 text-[11.5px] text-[var(--ep-text-3)]" title="Bruto menos taxas do gateway">
                            <TrendingUp class="h-3.5 w-3.5 text-[var(--ep-pos)]" :stroke-width="1.75" aria-hidden="true" />
                            Receita líquida
                        </dt>
                        <dd class="mt-1 truncate text-[17px] font-semibold tabular-nums tracking-[-0.02em] text-[var(--ep-text)]">{{ displayCurrency(receita_liquida) }}</dd>
                    </div>
                </dl>
            </section>

            <section class="panel-card flex min-w-0 flex-col p-5 pb-3 lg:col-span-8" aria-labelledby="rel-receita-periodo">
                <div class="flex flex-wrap items-center justify-between gap-3 px-1">
                    <h2 id="rel-receita-periodo" class="ep-section-title">Receita por período</h2>
                    <div class="ep-tabs !p-[2px]" role="group" aria-label="Tipo de receita">
                        <button
                            type="button"
                            :class="[
                                'ep-tab !h-7 !px-2.5 !text-[12px]',
                                chartReceitaMode === 'bruto' ? 'ep-tab--active' : '',
                            ]"
                            @click="chartReceitaMode = 'bruto'"
                        >
                            Bruto
                        </button>
                        <button
                            type="button"
                            :class="[
                                'ep-tab !h-7 !px-2.5 !text-[12px]',
                                chartReceitaMode === 'liquido' ? 'ep-tab--active' : '',
                            ]"
                            @click="chartReceitaMode = 'liquido'"
                        >
                            Líquido
                        </button>
                    </div>
                </div>
                <div class="rel-chart rel-chart--area mt-3 min-h-[260px] flex-1 [--rel-grid:rgba(15,21,48,0.08)] dark:[--rel-grid:rgba(255,255,255,0.07)]">
                    <VueApexCharts
                        v-if="chartReceitaData.length"
                        :key="chartReceitaType"
                        :type="chartReceitaType"
                        height="260"
                        :options="chartOptionsReceita"
                        :series="chartSeriesReceita"
                    />
                    <div v-else class="ep-empty h-[260px]">
                        <svg class="h-10 w-28 text-[var(--ep-line-strong)]" viewBox="0 0 112 40" fill="none" aria-hidden="true">
                            <path d="M2 34 C 18 34, 22 22, 36 24 S 58 34, 70 20 S 94 8, 110 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="3 5" />
                        </svg>
                        <p class="ep-empty__title mt-2">Nenhum dado no período</p>
                        <p class="ep-empty__text">Escolha outro período acima para ver a evolução da receita.</p>
                    </div>
                </div>
            </section>
        </div>

        <!-- Linha 2: KPIs -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div class="panel-card ep-kpi">
                <div class="flex items-start justify-between gap-3">
                    <span class="ep-kpi__label">Vendas</span>
                    <span class="ep-kpi__icon" aria-hidden="true"><ShoppingCart class="h-4 w-4" :stroke-width="1.75" /></span>
                </div>
                <p class="ep-kpi__value mt-auto">{{ displayNumber(quantidade_vendas) }}</p>
            </div>
            <div class="panel-card ep-kpi">
                <div class="flex items-start justify-between gap-3">
                    <span class="ep-kpi__label">Ticket médio</span>
                    <span class="ep-kpi__icon" aria-hidden="true"><TrendingUp class="h-4 w-4" :stroke-width="1.75" /></span>
                </div>
                <p class="ep-kpi__value mt-auto truncate">{{ displayCurrency(ticket_medio) }}</p>
            </div>
            <div class="panel-card ep-kpi">
                <div class="flex items-start justify-between gap-3">
                    <span class="ep-kpi__label">Alunos</span>
                    <span class="ep-kpi__icon" aria-hidden="true"><Users class="h-4 w-4" :stroke-width="1.75" /></span>
                </div>
                <p class="ep-kpi__value mt-auto">{{ displayNumber(total_alunos) }}</p>
            </div>
            <div class="panel-card ep-kpi">
                <div class="flex items-start justify-between gap-3">
                    <span class="ep-kpi__label">Produtos</span>
                    <span class="ep-kpi__icon" aria-hidden="true"><Package class="h-4 w-4" :stroke-width="1.75" /></span>
                </div>
                <p class="ep-kpi__value mt-auto">{{ displayNumber(total_produtos) }}</p>
            </div>
            <div class="panel-card ep-kpi sm:col-span-2 lg:col-span-1">
                <div class="flex items-start justify-between gap-3">
                    <span class="ep-kpi__label">Vendas abandonadas</span>
                    <span class="ep-kpi__icon" aria-hidden="true"><XCircle class="h-4 w-4" :stroke-width="1.75" /></span>
                </div>
                <p class="ep-kpi__value mt-auto">{{ displayNumber(abandonados_total) }}</p>
                <p class="ep-kpi__meta">Taxa: {{ valuesVisible ? `${taxa_conversao}%` : '—' }} conversão</p>
            </div>
        </div>

        <!-- Linha 3: receita por produto + distribuição -->
        <div class="grid gap-4 lg:grid-cols-12">
            <section class="panel-card min-w-0 p-5 lg:col-span-7" aria-labelledby="rel-produto">
                <div class="flex items-center justify-between gap-3 px-1">
                    <h2 id="rel-produto" class="ep-section-title">Receita por produto (top 10)</h2>
                    <Package class="h-4 w-4 text-[var(--ep-text-4)]" :stroke-width="1.75" aria-hidden="true" />
                </div>
                <div class="rel-chart rel-chart--bar mt-3 min-h-[260px] [--rel-grid:rgba(15,21,48,0.08)] dark:[--rel-grid:rgba(255,255,255,0.07)]">
                    <VueApexCharts
                        v-if="receita_por_produto.length"
                        type="bar"
                        height="260"
                        :options="chartOptionsProduto"
                        :series="chartSeriesProduto"
                    />
                    <div v-else class="ep-empty h-[260px]">
                        <p class="ep-empty__title">Nenhum dado no período</p>
                        <p class="ep-empty__text">Os produtos aparecem aqui assim que houver vendas aprovadas no período.</p>
                    </div>
                </div>
            </section>
            <section class="panel-card min-w-0 p-5 lg:col-span-5" aria-labelledby="rel-distribuicao">
                <div class="flex items-center justify-between gap-3 px-1">
                    <h2 id="rel-distribuicao" class="ep-section-title">Distribuição</h2>
                    <span class="text-[12px] text-[var(--ep-text-4)]">por forma de pagamento</span>
                </div>
                <div class="rel-chart rel-chart--donut mt-4 flex min-h-[220px] items-center justify-center">
                    <VueApexCharts
                        v-if="formasFiltradas.length"
                        type="donut"
                        height="220"
                        class="w-full"
                        :options="chartOptionsFormas"
                        :series="chartSeriesFormas"
                    />
                    <div v-else class="ep-empty">
                        <p class="ep-empty__title">Sem dados</p>
                        <p class="ep-empty__text">A distribuição aparece após o primeiro pagamento no período.</p>
                    </div>
                </div>
            </section>
        </div>

        <!-- Linha 4: formas de pagamento + reembolsos -->
        <div class="grid gap-4 lg:grid-cols-12">
            <section class="panel-card ep-data min-w-0 overflow-hidden lg:col-span-8" aria-labelledby="rel-formas">
                <div class="flex items-center gap-2 px-5 pb-3 pt-5">
                    <CreditCard class="h-4 w-4 text-[var(--ep-text-4)]" :stroke-width="1.75" aria-hidden="true" />
                    <h2 id="rel-formas" class="ep-section-title">Formas de pagamento</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="ep-table min-w-[520px]">
                        <thead>
                            <tr>
                                <th>Método</th>
                                <th class="ep-num">Bruto</th>
                                <th class="ep-num">Taxas</th>
                                <th class="ep-num">Líquido</th>
                                <th class="ep-num">Vendas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="fp in formas_pagamento"
                                :key="fp.metodo"
                            >
                                <td class="font-medium">{{ fp.label }}</td>
                                <td class="ep-num">{{ displayCurrency(fp.gross ?? fp.total) }}</td>
                                <td class="ep-num text-[var(--ep-text-3)]" title="Taxas">−{{ displayCurrency(fp.fees ?? 0) }}</td>
                                <td class="ep-num font-semibold text-[var(--ep-pos)]">{{ displayCurrency(fp.net ?? fp.total) }}</td>
                                <td class="ep-num text-[var(--ep-text-3)]">{{ displayNumber(fp.quantidade) }}</td>
                            </tr>
                            <tr v-if="!formas_pagamento.length" class="hover:!bg-transparent">
                                <td colspan="5">
                                    <div class="ep-empty !py-8">
                                        <p class="ep-empty__title">Nenhum pagamento no período</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="panel-card ep-kpi lg:col-span-4">
                <div class="flex items-start justify-between gap-3">
                    <span class="ep-kpi__label">Reembolsos</span>
                    <span class="ep-kpi__icon" aria-hidden="true"><RotateCcw class="h-4 w-4" :stroke-width="1.75" /></span>
                </div>
                <div class="mt-auto pt-6">
                    <MoneyAmount :value="reembolsos_total" :hidden="!valuesVisible" size="lg" class="block" />
                    <p class="ep-kpi__meta mt-1.5">{{ displayNumber(reembolsos_count) }} pedido(s)</p>
                </div>
            </div>
        </div>

        <!-- Order Bumps -->
        <section class="panel-card ep-data overflow-hidden" aria-labelledby="rel-order-bumps">
            <div class="flex flex-wrap items-start justify-between gap-4 p-5">
                <div class="min-w-0">
                    <h2 id="rel-order-bumps" class="flex items-center gap-2 text-[15px] font-semibold tracking-[-0.015em] text-[var(--ep-text)]">
                        <BarChart3 class="h-4 w-4 text-[var(--ep-text-4)]" :stroke-width="1.75" aria-hidden="true" />
                        Desempenho dos Order Bumps
                    </h2>
                    <p class="mt-1 text-[12.5px] text-[var(--ep-text-3)]">
                        Vendas concluídas no período selecionado, incluindo ofertas sem nenhuma aceitação.
                    </p>
                </div>
                <label v-if="order_bump_report.products.length" class="ep-label mb-0 min-w-64">
                    Produto principal
                    <span class="relative mt-1.5 block">
                        <select
                            v-model="orderBumpProductId"
                            class="ep-input appearance-none pr-9"
                            @change="setOrderBumpProduct"
                        >
                            <option v-for="product in order_bump_report.products" :key="product.id" :value="product.id">
                                {{ product.name }}
                            </option>
                        </select>
                        <ChevronDown class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--ep-text-4)]" :stroke-width="1.75" aria-hidden="true" />
                    </span>
                </label>
            </div>

            <div v-if="order_bump_report.products.length" class="grid gap-3 px-5 pb-5 sm:grid-cols-2">
                <div class="rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4">
                    <p class="ep-kpi__label">Pedidos concluídos</p>
                    <p class="ep-kpi__value mt-1.5">
                        {{ displayNumber(order_bump_report.eligible_orders) }}
                    </p>
                </div>
                <div class="rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4">
                    <p class="ep-kpi__label">Order Bumps vendidos</p>
                    <p class="ep-kpi__value mt-1.5">
                        {{ displayNumber(order_bump_report.accepted_items) }}
                    </p>
                </div>
            </div>

            <div v-if="order_bump_report.products.length" class="overflow-x-auto border-t border-[var(--ep-line)]">
                <table class="ep-table min-w-[520px]">
                    <thead>
                        <tr>
                            <th>Order Bump</th>
                            <th>Produto ofertado</th>
                            <th class="ep-num">Vendas</th>
                            <th class="ep-num">Aceitação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="bump in order_bump_report.bumps" :key="bump.id">
                            <td class="font-medium">{{ bump.title }}</td>
                            <td class="text-[var(--ep-text-2)]">{{ bump.target_name }}</td>
                            <td class="ep-num font-semibold">
                                {{ displayNumber(bump.sales_count) }}
                            </td>
                            <td class="ep-num">
                                <span class="ep-chip ep-chip--accent tabular-nums">{{ valuesVisible ? `${bump.acceptance_rate}%` : '—' }}</span>
                            </td>
                        </tr>
                        <tr v-if="!order_bump_report.bumps.length" class="hover:!bg-transparent">
                            <td colspan="4">
                                <div class="ep-empty !py-8">
                                    <p class="ep-empty__title">Este produto não possui Order Bumps configurados.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="ep-empty border-t border-[var(--ep-line)]">
                <p class="ep-empty__title">Nenhum produto possui Order Bumps configurados.</p>
                <p class="ep-empty__text">Configure um Order Bump no checkout de um produto para acompanhar a aceitação aqui.</p>
            </div>
        </section>

        <!-- Abandonos com e-mail -->
        <section class="panel-card ep-data overflow-hidden" aria-labelledby="rel-abandonos">
            <div class="flex items-center gap-2 px-5 pb-3 pt-5">
                <XCircle class="h-4 w-4 text-[var(--ep-text-4)]" :stroke-width="1.75" aria-hidden="true" />
                <h2 id="rel-abandonos" class="ep-section-title">Vendas abandonadas com e-mail (para recuperação)</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="ep-table min-w-[640px]">
                    <thead>
                        <tr>
                            <th>E-mail</th>
                            <th>Nome</th>
                            <th>Produto</th>
                            <th class="ep-num">Atualizado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="a in abandonados_com_email"
                            :key="a.id"
                        >
                            <td class="font-medium">{{ a.email }}</td>
                            <td class="text-[var(--ep-text-2)]">{{ a.name || '–' }}</td>
                            <td class="text-[var(--ep-text-2)]">{{ a.product_name }}</td>
                            <td class="ep-num text-[12.5px] text-[var(--ep-text-3)]">{{ formatDate(a.updated_at) }}</td>
                        </tr>
                        <tr v-if="!abandonados_com_email.length" class="hover:!bg-transparent">
                            <td colspan="4">
                                <div class="ep-empty">
                                    <p class="ep-empty__title">Nenhum abandono com e-mail no período</p>
                                    <p class="ep-empty__text">Quem preencher o e-mail no checkout e não concluir a compra aparece aqui para recuperação.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>

<style scoped>
/* ApexCharts sobre vidro: fundo transparente, grid hairline, rótulos em --ep-text-4 e cor da marca (--ep-accent). */
.rel-chart :deep(.apexcharts-canvas),
.rel-chart :deep(.apexcharts-svg) {
    background: transparent !important;
}
.rel-chart :deep(.apexcharts-gridline),
.rel-chart :deep(.apexcharts-grid-borders line),
.rel-chart :deep(.apexcharts-xaxis line),
.rel-chart :deep(.apexcharts-yaxis line) {
    stroke: var(--rel-grid, var(--ep-line));
}
.rel-chart :deep(.apexcharts-xaxis-label),
.rel-chart :deep(.apexcharts-yaxis-label) {
    fill: var(--ep-text-4);
    font-size: 11px;
    font-variant-numeric: tabular-nums;
}
.rel-chart :deep(.apexcharts-xaxistooltip) {
    display: none;
}

/* Área: linha 2.25px com brilho e gradiente da marca */
.rel-chart--area :deep(.apexcharts-series path[fill='none']) {
    stroke: var(--ep-accent);
    stroke-width: 2.25px;
    filter: drop-shadow(0 0 4px color-mix(in oklab, var(--ep-accent) 80%, transparent))
        drop-shadow(0 0 12px color-mix(in oklab, var(--ep-accent) 45%, transparent));
}
.rel-chart--area :deep(linearGradient stop) {
    stop-color: var(--ep-accent);
}
.rel-chart--area :deep(.apexcharts-marker) {
    fill: var(--ep-accent);
    stroke: transparent;
    filter: drop-shadow(0 0 5px color-mix(in oklab, var(--ep-accent) 80%, transparent));
}

/* Barras horizontais na cor da marca */
.rel-chart--bar :deep(.apexcharts-bar-area) {
    fill: var(--ep-accent);
    fill-opacity: 0.82;
    stroke: transparent;
    transition: fill-opacity 150ms var(--ep-ease-out);
}
.rel-chart--bar :deep(.apexcharts-bar-area:hover) {
    fill-opacity: 1;
}

/* Rosca: ordem fixa de cores (Pix, Cartão, Boleto…), sem contorno branco */
.rel-chart--donut :deep(.apexcharts-pie-area) {
    stroke: transparent;
}
.rel-chart--donut :deep(.apexcharts-pie-area[j='0']),
.rel-chart--donut :deep(.apexcharts-legend-series[rel='1'] path) { fill: var(--ep-pix); }
.rel-chart--donut :deep(.apexcharts-pie-area[j='1']),
.rel-chart--donut :deep(.apexcharts-legend-series[rel='2'] path) { fill: var(--ep-cartao); }
.rel-chart--donut :deep(.apexcharts-pie-area[j='2']),
.rel-chart--donut :deep(.apexcharts-legend-series[rel='3'] path) { fill: var(--ep-boleto); }
.rel-chart--donut :deep(.apexcharts-pie-area[j='3']),
.rel-chart--donut :deep(.apexcharts-legend-series[rel='4'] path) { fill: var(--ep-neg); }
.rel-chart--donut :deep(.apexcharts-pie-area[j='4']),
.rel-chart--donut :deep(.apexcharts-legend-series[rel='5'] path) { fill: var(--ep-accent-2); }
.rel-chart--donut :deep(.apexcharts-legend-text) {
    color: var(--ep-text-3) !important;
    font-family: inherit !important;
    font-size: 12px !important;
}

/* Tooltip em vidro */
.rel-chart :deep(.apexcharts-tooltip) {
    border: 1px solid var(--ep-glass-border) !important;
    border-radius: 12px !important;
    background: var(--ep-drawer) !important;
    color: var(--ep-text) !important;
    box-shadow: var(--ep-shadow-pop) !important;
    -webkit-backdrop-filter: blur(18px) saturate(160%);
    backdrop-filter: blur(18px) saturate(160%);
    font-family: inherit !important;
}
.rel-chart :deep(.apexcharts-tooltip-title) {
    border-bottom: 1px solid var(--ep-line) !important;
    background: transparent !important;
    color: var(--ep-text-3);
    font-family: inherit !important;
    font-size: 11.5px !important;
}
.rel-chart :deep(.apexcharts-tooltip-text-y-value) {
    font-weight: 600;
    font-variant-numeric: tabular-nums;
}
.rel-chart--area :deep(.apexcharts-tooltip-marker),
.rel-chart--bar :deep(.apexcharts-tooltip-marker) {
    background-color: var(--ep-accent) !important;
    color: var(--ep-accent) !important;
}
</style>
