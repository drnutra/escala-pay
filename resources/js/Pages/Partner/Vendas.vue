<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import { useInertiaPagination } from '@/composables/useInertiaPagination';
import HorizontalScrollTabs from '@/components/ui/HorizontalScrollTabs.vue';
import MoneyAmount from '@/components/ui/MoneyAmount.vue';
import {
    Eye,
    EyeOff,
    ShoppingCart,
    CircleDollarSign,
    Search,
    X,
    ChevronLeft,
    ChevronRight,
    Lock,
} from 'lucide-vue-next';

defineOptions({ layout: LayoutInfoprodutor });

const props = defineProps({
    vendas: { type: Object, default: () => ({ data: [], links: [] }) },
    stats: { type: Object, default: () => ({}) },
    status_filter: { type: String, default: 'todas' },
    filters: { type: Object, default: () => ({}) },
    products: { type: Array, default: () => [] },
});

const valuesVisible = ref(true);
const vendasList = computed(() => props.vendas?.data ?? []);

const {
    paginationPrev,
    paginationNext,
    paginationPages,
    paginationSummary,
    hasPagination,
    isEllipsisLink,
    visitPaginationPage,
    paginationLinkClass,
} = useInertiaPagination(() => props.vendas);

const filterOptions = [
    { value: 'aprovadas', label: 'Aprovadas' },
    { value: 'med', label: 'MED' },
    { value: 'todas', label: 'Todas' },
];

const periodOptions = [
    { value: 'all', label: 'Todo período' },
    { value: 'today', label: 'Hoje' },
    { value: '7d', label: 'Últimos 7 dias' },
    { value: '30d', label: 'Últimos 30 dias' },
    { value: 'this_month', label: 'Este mês' },
    { value: 'last_month', label: 'Mês passado' },
    { value: 'custom', label: 'Personalizado' },
];

const paymentStatusOptions = [
    { value: 'all', label: 'Todos status' },
    { value: 'completed', label: 'Pago' },
    { value: 'pending', label: 'Pendente' },
    { value: 'disputed', label: 'MED' },
    { value: 'cancelled', label: 'Cancelado' },
    { value: 'refunded', label: 'Reembolsado' },
];

const filterForm = ref({
    q: props.filters?.q ?? '',
    period: props.filters?.period ?? 'all',
    date_from: props.filters?.date_from ?? '',
    date_to: props.filters?.date_to ?? '',
    product_id: props.filters?.product_id ?? '',
    payment_status: props.filters?.payment_status ?? 'all',
});

let searchTimer = null;

function buildQuery() {
    const params = { status_filter: props.status_filter };
    if (filterForm.value.q?.trim()) params.q = filterForm.value.q.trim();
    if (filterForm.value.period && filterForm.value.period !== 'all') params.period = filterForm.value.period;
    if (filterForm.value.date_from) params.date_from = filterForm.value.date_from;
    if (filterForm.value.date_to) params.date_to = filterForm.value.date_to;
    if (filterForm.value.product_id) params.product_id = filterForm.value.product_id;
    if (filterForm.value.payment_status && filterForm.value.payment_status !== 'all') {
        params.payment_status = filterForm.value.payment_status;
    }
    return params;
}

function applyFilters() {
    router.get('/parceiro/vendas', buildQuery(), { preserveState: false, replace: true });
}

function setStatusFilter(value) {
    router.get('/parceiro/vendas', { ...buildQuery(), status_filter: value }, { preserveState: false });
}

function onSearchInput() {
    const q = (filterForm.value.q ?? '').trim();
    if (q !== '' && q.length < 3) return;
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        applyFilters();
        searchTimer = null;
    }, 600);
}

function formatBRL(value) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value ?? 0);
}

function formatMoney(value, currency = 'BRL') {
    try {
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: currency || 'BRL' }).format(value ?? 0);
    } catch {
        return formatBRL(value);
    }
}

function displayMoney(value, currency = 'BRL') {
    return valuesVisible.value ? formatMoney(value, currency) : '••••••';
}

function displayNumber(value) {
    return valuesVisible.value ? String(value) : '—';
}

function statusBadgeLabel(status) {
    const map = {
        completed: 'Pago',
        pending: 'Pendente',
        disputed: 'MED',
        cancelled: 'Cancelado',
        refunded: 'Reembolsado',
    };
    return map[status] || status || '—';
}

function statusBadgeClass(status) {
    if (status === 'completed') return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300';
    if (status === 'pending') return 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300';
    if (status === 'disputed') return 'bg-orange-100 text-orange-800 dark:bg-orange-950/50 dark:text-orange-300';
    if (status === 'refunded' || status === 'cancelled') return 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300';
    return 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400';
}

function commissionStatusLabel(status) {
    if (status === 'awaiting_payment') return 'Aguardando pagamento';
    if (status === 'allocating') return 'Calculando comissão';
    if (status === 'pending') return 'Comissão pendente';
    if (status === 'available') return 'Comissão disponível';
    if (status === 'paid') return 'Comissão paga';
    return status || '';
}
</script>

<template>
    <div class="space-y-5">
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-[22px] font-semibold tracking-[-0.025em] text-[var(--ep-text)]">Minhas vendas</h1>
                <p class="mt-1 text-[13px] text-[var(--ep-text-3)]">
                    Vendas atribuídas a você como parceiro. Dados do comprador dependem da configuração do produtor.
                </p>
            </div>
            <button
                type="button"
                class="ep-btn-secondary ep-btn-icon text-[var(--ep-text-3)] hover:text-[var(--ep-text)]"
                :aria-label="valuesVisible ? 'Ocultar valores' : 'Mostrar valores'"
                @click="valuesVisible = !valuesVisible"
            >
                <Eye v-if="valuesVisible" class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                <EyeOff v-else class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
            </button>
        </header>

        <!-- Métricas: comissões (herói) + vendas encontradas -->
        <div class="grid gap-4 lg:grid-cols-12">
            <section class="panel-card ep-glow-card flex flex-col p-6 lg:col-span-7" aria-labelledby="parceiro-vendas-comissao">
                <div class="flex items-center justify-between gap-3">
                    <h2 id="parceiro-vendas-comissao" class="text-[13px] font-medium text-[var(--ep-text-2)]">Suas comissões (filtro)</h2>
                    <span class="ep-kpi__icon" aria-hidden="true">
                        <CircleDollarSign class="h-4 w-4" :stroke-width="1.75" />
                    </span>
                </div>
                <MoneyAmount :value="Number(stats.comissao_total ?? 0)" :hidden="!valuesVisible" size="hero" class="mt-5 block" />
                <p class="mt-4 text-[12.5px] text-[var(--ep-text-3)]">Soma das comissões das vendas que atendem aos filtros abaixo.</p>
            </section>
            <div class="panel-card ep-kpi justify-between lg:col-span-5">
                <div class="flex items-start justify-between gap-3">
                    <span class="ep-kpi__label">Vendas encontradas</span>
                    <span class="ep-kpi__icon" aria-hidden="true">
                        <ShoppingCart class="h-4 w-4" :stroke-width="1.75" />
                    </span>
                </div>
                <p class="ep-kpi__value !text-[28px]">{{ displayNumber(stats.vendas_encontradas ?? 0) }}</p>
                <span class="ep-kpi__meta">pedidos atribuídos a você no filtro atual</span>
            </div>
        </div>

        <!-- Filtros -->
        <div class="panel-card space-y-4 p-4">
            <HorizontalScrollTabs aria-label="Filtrar vendas" nav-class="ep-tabs">
                <button
                    v-for="opt in filterOptions"
                    :key="opt.value"
                    type="button"
                    :class="['ep-tab shrink-0', status_filter === opt.value ? 'ep-tab--active' : '']"
                    @click="setStatusFilter(opt.value)"
                >
                    {{ opt.label }}
                </button>
            </HorizontalScrollTabs>

            <div class="flex flex-wrap items-end gap-3">
                <div class="relative min-w-[200px] max-w-xl flex-1">
                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--ep-text-4)]" :stroke-width="1.75" />
                    <input
                        v-model="filterForm.q"
                        type="text"
                        placeholder="Buscar pedido, e-mail, produto..."
                        class="ep-input !pl-9 !pr-9"
                        @input="onSearchInput"
                    />
                    <button
                        v-if="filterForm.q"
                        type="button"
                        aria-label="Limpar busca"
                        class="absolute right-2 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-md text-[var(--ep-text-4)] transition-colors duration-150 hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)]"
                        @click="filterForm.q = ''; applyFilters()"
                    >
                        <X class="h-4 w-4" :stroke-width="1.75" />
                    </button>
                </div>
                <select
                    v-model="filterForm.period"
                    class="ep-input !w-auto pr-8"
                    aria-label="Período"
                    @change="applyFilters"
                >
                    <option v-for="o in periodOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
                </select>
                <select
                    v-model="filterForm.product_id"
                    class="ep-input !w-auto max-w-[220px] pr-8"
                    aria-label="Produto"
                    @change="applyFilters"
                >
                    <option value="">Todos produtos</option>
                    <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
                <select
                    v-model="filterForm.payment_status"
                    class="ep-input !w-auto pr-8"
                    aria-label="Status do pagamento"
                    @change="applyFilters"
                >
                    <option v-for="o in paymentStatusOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
                </select>
            </div>

            <div v-if="filterForm.period === 'custom'" class="flex flex-wrap items-center gap-3 border-t border-[var(--ep-line)] pt-4">
                <input v-model="filterForm.date_from" type="date" aria-label="Data inicial" class="ep-input !w-auto" @change="applyFilters" />
                <span class="text-[12.5px] text-[var(--ep-text-4)]">até</span>
                <input v-model="filterForm.date_to" type="date" aria-label="Data final" class="ep-input !w-auto" @change="applyFilters" />
            </div>
        </div>

        <!-- Tabela -->
        <div class="panel-card ep-data overflow-hidden">
            <div class="overflow-x-auto">
                <table class="ep-table min-w-[860px]">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Produto</th>
                            <th>Comprador</th>
                            <th>Status</th>
                            <th class="ep-num">Valor venda</th>
                            <th class="ep-num">Sua comissão</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="v in vendasList" :key="v.id">
                            <td class="whitespace-nowrap text-[12.5px] tabular-nums text-[var(--ep-text-3)]">
                                {{ v.created_at ? new Date(v.created_at).toLocaleString('pt-BR') : '—' }}
                            </td>
                            <td class="max-w-[220px] font-medium text-[var(--ep-text)]">
                                <span class="line-clamp-2">{{ v.product_display_name ?? '—' }}</span>
                            </td>
                            <td>
                                <div class="flex items-start gap-1.5">
                                    <Lock v-if="v.buyer_masked" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-[var(--ep-text-4)]" :stroke-width="1.75" title="Dados mascarados" />
                                    <div class="min-w-0">
                                        <p class="font-medium text-[var(--ep-text)]">{{ v.buyer_name ?? '—' }}</p>
                                        <p class="text-[12px] text-[var(--ep-text-3)]">{{ v.buyer_email ?? '—' }}</p>
                                        <p v-if="v.buyer_phone" class="text-[12px] tabular-nums text-[var(--ep-text-3)]">{{ v.buyer_phone }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span
                                    class="ep-chip"
                                    :class="v.status === 'completed' ? 'ep-chip--pos' : v.status === 'pending' ? 'ep-chip--warn' : v.status === 'disputed' ? 'ep-chip--neg' : ''"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                                    {{ statusBadgeLabel(v.status) }}
                                </span>
                                <p class="mt-1 text-[11.5px] text-[var(--ep-text-4)]">{{ v.gateway_label }}</p>
                            </td>
                            <td class="ep-num tabular-nums text-[var(--ep-text-2)]">{{ displayMoney(v.amount_total ?? v.amount, v.currency) }}</td>
                            <td class="ep-num">
                                <p class="font-semibold tabular-nums text-[var(--ep-text)]">
                                    {{ displayMoney(v.commission_amount) }}
                                    <span
                                        v-if="v.commission_is_estimated"
                                        class="ml-0.5 text-[12px] font-normal text-[var(--ep-warn)]"
                                        title="Valor estimado até confirmação do pagamento"
                                    >*</span>
                                </p>
                                <p class="text-[11.5px] text-[var(--ep-text-4)]">
                                    {{ commissionStatusLabel(v.commission_status) }}
                                    <template v-if="v.commission_percent != null"> · <span class="tabular-nums">{{ v.commission_percent }}%</span></template>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="!vendasList.length" class="ep-empty border-t border-[var(--ep-line)]">
                <p class="ep-empty__title">Nenhuma venda encontrada.</p>
                <p class="ep-empty__text">Ajuste a busca, o período ou o status para ver outras vendas atribuídas a você.</p>
            </div>
        </div>

        <nav
            v-if="hasPagination"
            class="relative z-10 flex w-full items-center gap-2 py-2"
            aria-label="Paginação"
        >
            <button
                type="button"
                :disabled="!paginationPrev?.url"
                :class="paginationLinkClass(paginationPrev, { iconOnly: true })"
                aria-label="Página anterior"
                @click="visitPaginationPage(paginationPrev?.url)"
            >
                <ChevronLeft class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
            </button>

            <span class="min-w-0 flex-1 text-center text-[13px] font-medium tabular-nums text-[var(--ep-text-3)] sm:hidden">
                {{ paginationSummary }}
            </span>

            <div class="hidden min-w-0 flex-1 items-center justify-center gap-1 overflow-x-auto no-scrollbar sm:flex">
                <template v-for="(link, index) in paginationPages" :key="`page-${index}-${link.label}`">
                    <span
                        v-if="isEllipsisLink(link)"
                        class="inline-flex min-w-9 items-center justify-center px-2 py-2 text-[13px] text-[var(--ep-text-4)]"
                        aria-hidden="true"
                    >…</span>
                    <button
                        v-else
                        type="button"
                        :disabled="!link.url"
                        :aria-current="link.active ? 'page' : undefined"
                        :class="paginationLinkClass(link)"
                        @click="visitPaginationPage(link.url)"
                    >
                        {{ link.label }}
                    </button>
                </template>
            </div>

            <button
                type="button"
                :disabled="!paginationNext?.url"
                :class="paginationLinkClass(paginationNext, { iconOnly: true })"
                aria-label="Próxima página"
                @click="visitPaginationPage(paginationNext?.url)"
            >
                <ChevronRight class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
            </button>
        </nav>
    </div>
</template>
