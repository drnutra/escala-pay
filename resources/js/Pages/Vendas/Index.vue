<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import { useInertiaPagination } from '@/composables/useInertiaPagination';
import { whatsappUrlForPhone } from '@/lib/utils';
import VendasTabs from '@/components/vendas/VendasTabs.vue';
import HorizontalScrollTabs from '@/components/ui/HorizontalScrollTabs.vue';
import VendaDetailSidebar from '@/components/vendas/VendaDetailSidebar.vue';
import PluginRuntimeMount from '@/components/plugins/PluginRuntimeMount.vue';
import PluginRenderZone from '@/components/plugins/PluginRenderZone.vue';
import MoneyAmount from '@/components/ui/MoneyAmount.vue';
import {
    Eye,
    EyeOff,
    CircleDollarSign,
    CreditCard,
    Banknote,
    ShoppingCart,
    MoreVertical,
    FileText,
    Mail,
    Download,
    CheckCircle,
    RotateCcw,
    Search,
    X,
    ChevronLeft,
    ChevronRight,
} from 'lucide-vue-next';

defineOptions({ layout: LayoutInfoprodutor });

const props = defineProps({
    vendas: { type: Object, default: () => ({ data: [], links: [] }) },
    stats: { type: Object, default: () => ({}) },
    status_filter: { type: String, default: 'todas' },
    filters: { type: Object, default: () => ({}) },
    products: { type: Array, default: () => [] },
    offers: { type: Array, default: () => [] },
    plugin_fulfillment_providers: { type: Array, default: () => [] },
    plugin_vendas_row_actions: { type: Array, default: () => [] },
    plugin_order_detail_panels: { type: Array, default: () => [] },
});

const vendasList = computed(() => props.vendas?.data ?? props.vendas ?? []);

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

const valuesVisible = ref(true);
const sidebarOpen = ref(false);
const selectedVenda = ref(null);
const openMenuId = ref(null);
const menuAnchorEl = ref(null);
const menuEl = ref(null);
const menuPos = ref({ top: 0, left: 0 });
const page = usePage();
const resendingId = ref(null);
const approvingId = ref(null);
const refundingId = ref(null);
const refundModalOpen = ref(false);
const refundTarget = ref(null);
const refundAdminNotes = ref('');
const toast = ref({ message: null, type: null });

const canManageRefund = computed(() => {
    const role = page.props.auth?.user?.role;
    if (role === 'admin' || role === 'infoprodutor') return true;
    return !!page.props.auth?.permissions?.['reembolsos.manage'];
});
let toastTimer = null;
let searchTimer = null;
let removeInertiaListener = null;

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

const paymentMethodOptions = [
    { value: 'all', label: 'Todos métodos' },
    { value: 'pix', label: 'PIX' },
    { value: 'card', label: 'Cartão' },
    { value: 'boleto', label: 'Boleto' },
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
    offer_id: props.filters?.offer_id ?? '',
    payment_method: props.filters?.payment_method ?? 'all',
    payment_status: props.filters?.payment_status ?? 'all',
    utm_source: props.filters?.utm_source ?? '',
    utm_medium: props.filters?.utm_medium ?? '',
    utm_campaign: props.filters?.utm_campaign ?? '',
});

watch(
    () => props.filters,
    (filters) => {
        if (!filters || typeof filters !== 'object') return;
        filterForm.value = {
            q: filters.q ?? '',
            period: filters.period ?? 'all',
            date_from: filters.date_from ?? '',
            date_to: filters.date_to ?? '',
            product_id: filters.product_id ?? '',
            offer_id: filters.offer_id ?? '',
            payment_method: filters.payment_method ?? 'all',
            payment_status: filters.payment_status ?? 'all',
            utm_source: filters.utm_source ?? '',
            utm_medium: filters.utm_medium ?? '',
            utm_campaign: filters.utm_campaign ?? '',
        };
    },
    { deep: true }
);

const advancedFiltersOpen = ref(false);

const offersForSelectedProduct = computed(() => {
    const pid = filterForm.value.product_id;
    if (!pid) return props.offers ?? [];
    return (props.offers ?? []).filter((o) => String(o.product_id) === String(pid));
});

function buildQuery(overrides = {}) {
    const f = { ...filterForm.value, ...overrides };
    const q = { status_filter: props.status_filter, ...f };

    const cleaned = {};
    Object.entries(q).forEach(([k, v]) => {
        if (v === null || v === undefined) return;
        if (typeof v === 'string' && v.trim() === '') return;
        if ((k === 'period' || k === 'payment_method' || k === 'payment_status') && v === 'all') return;
        cleaned[k] = v;
    });
    if (cleaned.period !== 'custom') {
        delete cleaned.date_from;
        delete cleaned.date_to;
    }
    return cleaned;
}

function applyFilters(overrides = {}) {
    router.get('/vendas', buildQuery(overrides), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: [
            'vendas',
            'stats',
            'filters',
            'status_filter',
            'products',
            'offers',
            'plugin_fulfillment_providers',
            'plugin_vendas_row_actions',
            'plugin_order_detail_panels',
        ],
    });
}

const menuVenda = computed(() => {
    if (openMenuId.value == null) return null;
    const list = vendasList.value ?? [];
    return list.find((x) => x.id === openMenuId.value) ?? null;
});

function setFilter(value) {
    applyFilters({ status_filter: value });
}

function formatBRL(value) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value ?? 0);
}

function formatMoney(value, currency = 'BRL') {
    const code = typeof currency === 'string' && currency.trim() ? currency.trim().toUpperCase() : 'BRL';
    const locale = code === 'BRL' ? 'pt-BR' : code === 'EUR' ? 'de-DE' : 'en-US';
    return new Intl.NumberFormat(locale, { style: 'currency', currency: code }).format(value ?? 0);
}

function displayCurrency(value) {
    return valuesVisible.value ? formatBRL(value) : '••••••';
}

function vendaDisplayAmount(v) {
    if (v?.display_amount_is_producer_share && v.display_amount != null) {
        return v.display_amount;
    }
    return v?.amount_total ?? v?.amount ?? 0;
}

function displayMoney(value, currency = 'BRL') {
    return valuesVisible.value ? formatMoney(value, currency) : '••••••';
}

function displayNumber(value) {
    return valuesVisible.value ? String(value) : '—';
}

function whatsappUrl(venda) {
    return whatsappUrlForPhone(venda?.phone);
}

function statusBadgeClass(status) {
    const map = {
        completed: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
        pending: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
        disputed: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
        cancelled: 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700/50 dark:text-zinc-300',
        refunded: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    };
    return map[status] ?? 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700/50 dark:text-zinc-300';
}

function statusBadgeLabel(status) {
    const map = {
        completed: 'Pago',
        pending: 'Pendente',
        disputed: 'MED',
        cancelled: 'Cancelado',
        refunded: 'Reembolsado',
    };
    return map[status] ?? status ?? '–';
}

function openDetail(v) {
    selectedVenda.value = v;
    sidebarOpen.value = true;
    closeMenu();
}

function closeSidebar() {
    sidebarOpen.value = false;
    selectedVenda.value = null;
}

async function updateMenuPosition() {
    const anchor = menuAnchorEl.value;
    if (!anchor || openMenuId.value == null) return;

    const rect = anchor.getBoundingClientRect();
    const minMargin = 8;
    const desiredWidth = 192;
    const viewportW = window.innerWidth || 0;
    const viewportH = window.innerHeight || 0;

    let left = rect.right - desiredWidth;
    left = Math.max(minMargin, Math.min(left, Math.max(minMargin, viewportW - desiredWidth - minMargin)));

    let top = rect.bottom + 4;
    top = Math.max(minMargin, Math.min(top, Math.max(minMargin, viewportH - minMargin)));

    menuPos.value = { top, left };

    await nextTick();
    const menu = menuEl.value;
    if (!menu) return;

    const menuRect = menu.getBoundingClientRect();
    const spaceBelow = viewportH - rect.bottom;
    const spaceAbove = rect.top;
    const shouldOpenUp = menuRect.height + 8 > spaceBelow && spaceAbove >= menuRect.height + 8;

    if (shouldOpenUp) {
        const newTop = Math.max(minMargin, rect.top - menuRect.height - 4);
        menuPos.value = { top: newTop, left: menuPos.value.left };
    }
}

async function toggleMenu(id, event) {
    if (openMenuId.value === id) {
        closeMenu();
        return;
    }
    openMenuId.value = id;
    menuAnchorEl.value = event?.currentTarget ?? null;
    await nextTick();
    await updateMenuPosition();
}

function pluginActionHref(action, venda) {
    const raw = action.href ?? action.route ?? '';
    const base = String(raw).startsWith('/') ? String(raw) : `/${action.plugin_slug}/${String(raw).replace(/^\//, '')}`;
    return base.replace(':order_id', String(venda?.id ?? ''));
}

function closeMenu() {
    openMenuId.value = null;
    menuAnchorEl.value = null;
}

function handleClickOutside(event) {
    if (openMenuId.value == null) return;
    const el = document.querySelector(`[data-venda-menu="${openMenuId.value}"]`);
    const menu = menuEl.value;
    if (el && el.contains(event.target)) return;
    if (menu && menu.contains(event.target)) return;
    closeMenu();
}

async function resendEmail(v) {
    closeMenu();
    if (resendingId.value) return;
    resendingId.value = v.id;
    try {
        const { data } = await axios.post(`/vendas/${v.id}/resend-access-email`);
        if (data.success) {
            showToast('E-mail de compra reenviado com sucesso.', 'success');
        } else {
            showToast(data.message ?? 'Não foi possível reenviar o e-mail.', 'error');
        }
    } catch (err) {
        showToast(
            err.response?.data?.message ?? 'Erro ao reenviar e-mail. Tente novamente.',
            'error'
        );
    } finally {
        resendingId.value = null;
    }
}

function openRefundModal(v) {
    closeMenu();
    refundTarget.value = v;
    refundAdminNotes.value = '';
    refundModalOpen.value = true;
}

function closeRefundModal() {
    refundModalOpen.value = false;
    refundTarget.value = null;
    refundAdminNotes.value = '';
}

async function confirmRefund() {
    const v = refundTarget.value;
    if (!v || refundingId.value) return;
    refundingId.value = v.id;
    try {
        const { data } = await axios.post(`/vendas/${v.id}/refund`, {
            admin_notes: refundAdminNotes.value.trim() || null,
        });
        if (data.success) {
            closeRefundModal();
            showToast(data.message ?? 'Reembolso processado.', 'success');
            router.reload({ preserveScroll: true });
        } else {
            showToast(data.message ?? 'Não foi possível reembolsar.', 'error');
        }
    } catch (err) {
        showToast(
            err.response?.data?.message ?? 'Erro ao reembolsar. Tente novamente.',
            'error'
        );
    } finally {
        refundingId.value = null;
    }
}

async function approveManually(v) {
    closeMenu();
    if (approvingId.value) return;
    approvingId.value = v.id;
    try {
        const { data } = await axios.post(`/vendas/${v.id}/approve-manually`);
        if (data.success) {
            showToast(data.message ?? 'Pedido aprovado com sucesso.', 'success');
            router.reload({ preserveScroll: true });
        } else {
            showToast(data.message ?? 'Não foi possível aprovar o pedido.', 'error');
        }
    } catch (err) {
        showToast(
            err.response?.data?.message ?? 'Erro ao aprovar pedido. Tente novamente.',
            'error'
        );
    } finally {
        approvingId.value = null;
    }
}

function showToast(message, type) {
    toast.value = { message, type };
    if (toastTimer) clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        toast.value = { message: null, type: null };
        toastTimer = null;
    }, 4000);
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    window.addEventListener('resize', updateMenuPosition);
    window.addEventListener('scroll', updateMenuPosition, true);
    removeInertiaListener = router.on('before', () => {
        closeSidebar();
        closeMenu();
        closeRefundModal();
    });
});
onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    window.removeEventListener('resize', updateMenuPosition);
    window.removeEventListener('scroll', updateMenuPosition, true);
    if (typeof removeInertiaListener === 'function') {
        removeInertiaListener();
        removeInertiaListener = null;
    }
    closeSidebar();
    closeMenu();
    if (toastTimer) clearTimeout(toastTimer);
    if (searchTimer) clearTimeout(searchTimer);
});

function onSearchInput() {
    const q = (filterForm.value.q ?? '').trim();
    if (q !== '' && q.length < 3) {
        if (searchTimer) clearTimeout(searchTimer);
        searchTimer = null;
        return;
    }
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        applyFilters();
        searchTimer = null;
    }, 600);
}

function onFilterChange() {
    applyFilters();
}

function clearFilters() {
    filterForm.value = {
        q: '',
        period: 'all',
        date_from: '',
        date_to: '',
        product_id: '',
        offer_id: '',
        payment_method: 'all',
        payment_status: 'all',
        utm_source: '',
        utm_medium: '',
        utm_campaign: '',
    };
    applyFilters();
}

const exportCsvUrl = computed(() => {
    const params = new URLSearchParams({ ...buildQuery(), format: 'csv' });
    return `/vendas/export?${params.toString()}`;
});

const exportXlsUrl = computed(() => {
    const params = new URLSearchParams({ ...buildQuery(), format: 'xls' });
    return `/vendas/export?${params.toString()}`;
});

function openProofExport() {
    window.open('/vendas/comprovacao/exportar', '_blank', 'noopener,noreferrer');
}
</script>

<template>
    <div class="space-y-5">
        <!-- Abas + ações da página -->
        <div class="flex min-w-0 flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <VendasTabs />
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <button
                    type="button"
                    class="ep-btn-secondary"
                    @click="openProofExport"
                    title="Exportar comprovações (ZIP)"
                >
                    <FileText class="h-4 w-4 text-[var(--ep-text-3)]" :stroke-width="1.75" />
                    Comprovação
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
        <PluginRenderZone zone="vendas.index.after_toolbar" />

        <!-- Métricas: faturamento bruto (herói) + KPIs subordinados -->
        <div class="grid gap-4 lg:grid-cols-12">
            <section class="panel-card ep-glow-card flex flex-col p-6 lg:col-span-5" aria-labelledby="vendas-bruto">
                <div class="flex items-center justify-between gap-3">
                    <h2 id="vendas-bruto" class="text-[13px] font-medium text-[var(--ep-text-2)]" title="Valor pago pelo cliente (pedidos concluídos)">Faturamento bruto</h2>
                    <span class="ep-chip tabular-nums">
                        <ShoppingCart class="h-3.5 w-3.5 text-[var(--ep-text-3)]" :stroke-width="1.75" aria-hidden="true" />
                        {{ displayNumber(stats.vendas_encontradas ?? 0) }} vendas encontradas
                    </span>
                </div>
                <div v-if="(stats.valor_bruto_por_moeda ?? stats.valor_por_moeda ?? []).length" class="mt-5 space-y-2">
                    <MoneyAmount
                        v-for="row in (stats.valor_bruto_por_moeda ?? stats.valor_por_moeda)"
                        :key="'bruto-' + row.currency"
                        :value="Number(row.total ?? 0)"
                        :currency="row.currency"
                        :hidden="!valuesVisible"
                        :size="row === (stats.valor_bruto_por_moeda ?? stats.valor_por_moeda)[0] ? 'hero' : 'lg'"
                        class="block"
                    />
                </div>
                <MoneyAmount v-else :value="0" currency="BRL" :hidden="!valuesVisible" size="hero" class="mt-5 block" />

                <div class="mt-6 lg:mt-auto lg:pt-6">
                    <div class="mb-2.5 flex items-center justify-between gap-3 text-[11.5px]">
                        <span class="flex items-center gap-1.5 text-[var(--ep-text-3)]">
                            <span class="h-2 w-2 rounded-full bg-[var(--ep-accent)]" aria-hidden="true" />
                            Receita líquida
                        </span>
                        <span class="flex items-center gap-1.5 text-[var(--ep-text-3)]">
                            <span class="h-2 w-2 rounded-full bg-[var(--ep-warn)]" aria-hidden="true" />
                            Taxas gateway
                        </span>
                    </div>
                    <div
                        class="flex h-2.5 w-full gap-[3px] overflow-hidden rounded-full bg-[var(--ep-active)]"
                        aria-hidden="true"
                    >
                        <span
                            class="h-full rounded-full bg-gradient-to-r from-[var(--ep-accent)] to-[var(--ep-accent-2)] transition-[width] duration-500"
                            :style="{ width: `${valuesVisible ? Math.min(100, Math.max(0, (Number((stats.valor_liquido_por_moeda ?? [])[0]?.total ?? 0) / (Number((stats.valor_bruto_por_moeda ?? stats.valor_por_moeda ?? [])[0]?.total ?? 0) || 1)) * 100)) : 0}%` }"
                        />
                        <span
                            class="h-full rounded-full bg-[var(--ep-warn)] transition-[width] duration-500"
                            :style="{ width: `${valuesVisible ? Math.min(100, Math.max(0, (Number((stats.taxas_gateway_por_moeda ?? [])[0]?.total ?? 0) / (Number((stats.valor_bruto_por_moeda ?? stats.valor_por_moeda ?? [])[0]?.total ?? 0) || 1)) * 100)) : 0}%` }"
                        />
                    </div>
                    <p class="mt-2.5 text-[11.5px] text-[var(--ep-text-4)]">Bruto pago pelos clientes, descontadas as taxas do gateway.</p>
                </div>
            </section>

            <div class="grid gap-4 sm:grid-cols-2 lg:col-span-7">
                <div class="panel-card ep-kpi">
                    <div class="flex items-start justify-between gap-3">
                        <span class="ep-kpi__label" title="Faturamento bruto menos taxas do gateway">Receita líquida</span>
                        <span class="ep-kpi__icon" aria-hidden="true">
                            <CircleDollarSign class="h-4 w-4" :stroke-width="1.75" />
                        </span>
                    </div>
                    <div v-if="(stats.valor_liquido_por_moeda ?? []).length" class="space-y-1">
                        <MoneyAmount
                            v-for="row in stats.valor_liquido_por_moeda"
                            :key="'liq-' + row.currency"
                            :value="Number(row.total ?? 0)"
                            :currency="row.currency"
                            :hidden="!valuesVisible"
                            size="lg"
                            class="block"
                        />
                    </div>
                    <MoneyAmount v-else :value="0" currency="BRL" :hidden="!valuesVisible" size="lg" class="block" />
                    <span class="ep-kpi__meta">o que fica após as taxas</span>
                </div>
                <div class="panel-card ep-kpi">
                    <div class="flex items-start justify-between gap-3">
                        <span class="ep-kpi__label" title="Taxas do gateway (reais quando informadas, senão estimativa da configuração)">Taxas gateway</span>
                        <span
                            class="ep-kpi__icon"
                            style="color: var(--ep-warn); background: var(--ep-warn-bg); border-color: color-mix(in oklab, var(--ep-warn) 28%, transparent); box-shadow: none"
                            aria-hidden="true"
                        >
                            <CircleDollarSign class="h-4 w-4" :stroke-width="1.75" />
                        </span>
                    </div>
                    <div v-if="(stats.taxas_gateway_por_moeda ?? []).length" class="space-y-1">
                        <MoneyAmount
                            v-for="row in stats.taxas_gateway_por_moeda"
                            :key="'taxa-' + row.currency"
                            :value="Number(row.total ?? 0)"
                            :currency="row.currency"
                            :hidden="!valuesVisible"
                            size="lg"
                            class="block"
                        />
                    </div>
                    <MoneyAmount v-else :value="0" currency="BRL" :hidden="!valuesVisible" size="lg" class="block" />
                    <span class="ep-kpi__meta">reais quando informadas, senão estimadas</span>
                </div>
                <div class="panel-card ep-kpi">
                    <div class="flex items-start justify-between gap-3">
                        <span class="ep-kpi__label flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-[var(--ep-pix)]" aria-hidden="true" />
                            Vendas no PIX
                        </span>
                        <span
                            class="ep-kpi__icon"
                            style="color: var(--ep-pix); background: color-mix(in oklab, var(--ep-pix) 14%, transparent); border-color: color-mix(in oklab, var(--ep-pix) 28%, transparent); box-shadow: none"
                            aria-hidden="true"
                        >
                            <Banknote class="h-4 w-4" :stroke-width="1.75" />
                        </span>
                    </div>
                    <p class="ep-kpi__value">{{ displayNumber(stats.vendas_pix ?? 0) }}</p>
                    <span class="ep-kpi__meta">pedidos via PIX no filtro atual</span>
                </div>
                <div class="panel-card ep-kpi">
                    <div class="flex items-start justify-between gap-3">
                        <span class="ep-kpi__label flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-[var(--ep-cartao)]" aria-hidden="true" />
                            Vendas no cartão
                        </span>
                        <span
                            class="ep-kpi__icon"
                            style="color: var(--ep-cartao); background: color-mix(in oklab, var(--ep-cartao) 14%, transparent); border-color: color-mix(in oklab, var(--ep-cartao) 28%, transparent); box-shadow: none"
                            aria-hidden="true"
                        >
                            <CreditCard class="h-4 w-4" :stroke-width="1.75" />
                        </span>
                    </div>
                    <p class="ep-kpi__value">{{ displayNumber(stats.vendas_cartao ?? 0) }}</p>
                    <span class="ep-kpi__meta">pedidos no cartão no filtro atual</span>
                </div>
            </div>
        </div>

        <!-- Busca, abas de status, filtros e exportação -->
        <section class="panel-card p-5" aria-label="Filtros de vendas">
            <div class="flex min-w-0 flex-wrap items-center justify-between gap-3">
                <HorizontalScrollTabs aria-label="Filtrar vendas" nav-class="ep-tabs" class="min-w-0 max-w-full sm:w-auto sm:max-w-none">
                    <button
                        v-for="opt in filterOptions"
                        :key="opt.value"
                        type="button"
                        :aria-current="status_filter === opt.value ? 'true' : undefined"
                        :class="[
                            'ep-tab border',
                            status_filter === opt.value ? 'ep-tab--active' : 'border-transparent',
                        ]"
                        @click="setFilter(opt.value)"
                    >
                        {{ opt.label }}
                    </button>
                </HorizontalScrollTabs>
                <div class="flex items-center gap-2">
                    <a
                        :href="exportCsvUrl"
                        class="ep-btn-secondary"
                    >
                        <Download class="h-4 w-4 text-[var(--ep-text-3)]" :stroke-width="1.75" />
                        Exportar CSV
                    </a>
                    <a
                        :href="exportXlsUrl"
                        class="ep-btn-secondary"
                    >
                        <Download class="h-4 w-4 text-[var(--ep-text-3)]" :stroke-width="1.75" />
                        Exportar XLS
                    </a>
                </div>
            </div>

            <div class="ep-divider my-5" aria-hidden="true" />

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="relative w-full max-w-xl">
                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--ep-text-4)]" :stroke-width="1.75" />
                    <input
                        v-model="filterForm.q"
                        type="text"
                        class="ep-input pl-9 pr-10"
                        placeholder="Buscar por cliente, e-mail, pedido, produto..."
                        @input="onSearchInput"
                    />
                    <button
                        v-if="filterForm.q"
                        type="button"
                        class="absolute right-1.5 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-[9px] text-[var(--ep-text-4)] transition-colors duration-150 hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)]"
                        aria-label="Limpar busca"
                        @click="filterForm.q = ''; onFilterChange()"
                    >
                        <X class="h-4 w-4" :stroke-width="1.75" />
                    </button>
                </div>
                <button
                    type="button"
                    class="ep-btn-ghost"
                    @click="clearFilters"
                >
                    <RotateCcw class="h-3.5 w-3.5" :stroke-width="1.75" aria-hidden="true" />
                    Limpar filtros
                </button>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
                <div class="lg:col-span-2">
                    <label class="ep-label">Período</label>
                    <select
                        v-model="filterForm.period"
                        class="ep-input"
                        @change="onFilterChange"
                    >
                        <option v-for="p in periodOptions" :key="p.value" :value="p.value">{{ p.label }}</option>
                    </select>
                </div>

                <div v-if="filterForm.period === 'custom'">
                    <label class="ep-label">De</label>
                    <input
                        v-model="filterForm.date_from"
                        type="date"
                        class="ep-input tabular-nums"
                        @change="onFilterChange"
                    />
                </div>
                <div v-if="filterForm.period === 'custom'">
                    <label class="ep-label">Até</label>
                    <input
                        v-model="filterForm.date_to"
                        type="date"
                        class="ep-input tabular-nums"
                        @change="onFilterChange"
                    />
                </div>

                <div :class="filterForm.period === 'custom' ? 'lg:col-span-2' : ''">
                    <label class="ep-label">Produto</label>
                    <select
                        v-model="filterForm.product_id"
                        class="ep-input"
                        @change="() => { if (filterForm.offer_id && !offersForSelectedProduct.some(o => String(o.id) === String(filterForm.offer_id))) filterForm.offer_id = ''; onFilterChange(); }"
                    >
                        <option value="">Todos produtos</option>
                        <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                </div>

                <div>
                    <label class="ep-label">Oferta</label>
                    <select
                        v-model="filterForm.offer_id"
                        class="ep-input"
                        @change="onFilterChange"
                    >
                        <option value="">Todas ofertas</option>
                        <option v-for="o in offersForSelectedProduct" :key="o.id" :value="o.id">
                            {{ o.product_name ? `${o.product_name} - ${o.name}` : o.name }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="ep-label">Método</label>
                    <select
                        v-model="filterForm.payment_method"
                        class="ep-input"
                        @change="onFilterChange"
                    >
                        <option v-for="m in paymentMethodOptions" :key="m.value" :value="m.value">{{ m.label }}</option>
                    </select>
                </div>

                <div>
                    <label class="ep-label">Status</label>
                    <select
                        v-model="filterForm.payment_status"
                        class="ep-input"
                        @change="onFilterChange"
                    >
                        <option v-for="s in paymentStatusOptions" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                </div>
            </div>

            <div class="mt-4">
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 text-[12.5px] font-medium text-[var(--ep-text-3)] transition-colors duration-150 hover:text-[var(--ep-text)]"
                    @click="advancedFiltersOpen = !advancedFiltersOpen"
                >
                    <ChevronRight
                        class="h-3.5 w-3.5 transition-transform duration-150"
                        :class="advancedFiltersOpen ? 'rotate-90' : ''"
                        :stroke-width="1.75"
                        aria-hidden="true"
                    />
                    {{ advancedFiltersOpen ? 'Ocultar filtros avançados' : 'Mostrar filtros avançados' }}
                </button>
                <div v-if="advancedFiltersOpen" class="mt-3 grid gap-3 rounded-[14px] border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4 lg:grid-cols-3">
                    <div>
                        <label class="ep-label font-mono text-[12px]">utm_source</label>
                        <input
                            v-model="filterForm.utm_source"
                            type="text"
                            class="ep-input"
                            @change="onFilterChange"
                        />
                    </div>
                    <div>
                        <label class="ep-label font-mono text-[12px]">utm_medium</label>
                        <input
                            v-model="filterForm.utm_medium"
                            type="text"
                            class="ep-input"
                            @change="onFilterChange"
                        />
                    </div>
                    <div>
                        <label class="ep-label font-mono text-[12px]">utm_campaign</label>
                        <input
                            v-model="filterForm.utm_campaign"
                            type="text"
                            class="ep-input"
                            @change="onFilterChange"
                        />
                    </div>
                </div>
            </div>
        </section>

        <!-- Tabela de vendas -->
        <div class="sm:hidden space-y-3">
            <div
                v-for="v in vendasList"
                :key="v.id"
                class="panel-card ep-data p-4 transition-colors duration-150 active:bg-[var(--ep-hover)]"
                role="button"
                tabindex="0"
                @click="openDetail(v)"
                @keydown.enter.prevent="openDetail(v)"
                @keydown.space.prevent="openDetail(v)"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-start gap-3">
                        <span v-avatar="v.user?.name || v.email" class="ep-avatar mt-0.5 shrink-0" aria-hidden="true">{{ String(v.user?.name || v.email || '?').trim().split(/\s+/).filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase() || '?' }}</span>
                        <div class="min-w-0">
                            <p class="break-words text-[13.5px] font-medium leading-snug text-[var(--ep-text)]">
                                {{ v.product_display_name ?? v.product?.name ?? '–' }}
                            </p>
                            <p class="mt-0.5 text-[12px] tabular-nums text-[var(--ep-text-4)]">
                                {{ new Date(v.created_at).toLocaleDateString('pt-BR') }} · {{ new Date(v.created_at).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }) }}
                            </p>
                            <p
                                v-if="v.is_affiliate_sale"
                                class="mt-1.5 inline-flex max-w-full flex-wrap items-center gap-1.5 text-[12px] text-[var(--ep-text-3)]"
                            >
                                <span class="ep-chip ep-chip--accent">Afiliado</span>
                                <span v-if="v.affiliate?.name" class="truncate">{{ v.affiliate.name }}</span>
                                <span v-else-if="v.affiliate?.code" class="font-mono">{{ v.affiliate.code }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-1" @click.stop>
                        <a
                            v-if="whatsappUrl(v)"
                            :href="whatsappUrl(v)"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex h-9 w-9 items-center justify-center rounded-xl text-[#25D366] transition-colors duration-150 hover:bg-[var(--ep-hover)]"
                            :aria-label="`WhatsApp de ${v.user?.name ?? 'cliente'}`"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                            </svg>
                        </a>
                        <div class="relative" :data-venda-menu="v.id">
                            <button
                                type="button"
                                class="flex h-9 w-9 items-center justify-center rounded-xl text-[var(--ep-text-3)] transition-colors duration-150 hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)]"
                                aria-label="Abrir menu"
                                aria-expanded="openMenuId === v.id"
                                @click="toggleMenu(v.id, $event)"
                            >
                                <MoreVertical class="h-4 w-4" :stroke-width="1.75" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-1.5">
                    <span
                        class="ep-chip"
                        :class="{ completed: 'ep-chip--pos', pending: 'ep-chip--warn', disputed: 'ep-chip--neg' }[v.status] ?? ''"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                        {{ statusBadgeLabel(v.status) }}
                    </span>
                    <span class="ep-chip">
                        <span
                            class="h-1.5 w-1.5 rounded-full"
                            :style="{ background: String(v.gateway_label ?? '').toUpperCase().startsWith('PIX') ? 'var(--ep-pix)' : v.gateway_label === 'Boleto' ? 'var(--ep-boleto)' : ['Cartão', 'Apple Pay', 'Google Pay', 'PayPal'].includes(v.gateway_label) ? 'var(--ep-cartao)' : 'var(--ep-text-4)' }"
                            aria-hidden="true"
                        />
                        {{ v.gateway_label ?? '–' }}
                    </span>
                </div>

                <div class="mt-3 flex items-center justify-between gap-3 border-t border-[var(--ep-line)] pt-3">
                    <div class="min-w-0">
                        <p class="truncate text-[12.5px] font-medium text-[var(--ep-text-2)]">
                            {{ v.user?.name ?? '–' }}
                        </p>
                        <p class="truncate text-[12px] text-[var(--ep-text-4)]">
                            {{ v.email ?? v.user?.email ?? '–' }}
                        </p>
                    </div>
                </div>

                <dl class="mt-3 grid grid-cols-3 gap-2 rounded-xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] px-3 py-2.5">
                    <div>
                        <dt class="text-[11.5px] text-[var(--ep-text-3)]">Bruto</dt>
                        <dd class="mt-0.5 truncate text-[13px] font-medium tabular-nums text-[var(--ep-text-2)]">
                            {{ v.status === 'completed' ? displayMoney(v.gross_amount ?? v.amount_total, v.currency) : '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-[11.5px] text-[var(--ep-text-3)]">Taxa</dt>
                        <dd class="mt-0.5 truncate text-[13px] font-medium tabular-nums text-[var(--ep-text-3)]">
                            {{ v.status === 'completed' ? displayMoney(v.gateway_fee ?? 0, v.currency) : '—' }}
                        </dd>
                    </div>
                    <div class="text-right">
                        <dt class="text-[11.5px] text-[var(--ep-text-3)]">Líquido</dt>
                        <dd class="mt-0.5 truncate text-[13px] font-semibold tabular-nums tracking-[-0.01em] text-[var(--ep-text)]">
                            {{ v.status === 'completed' ? displayMoney(v.net_amount ?? v.gross_amount, v.currency) : displayMoney(vendaDisplayAmount(v), v.currency) }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div
                v-if="!vendasList.length"
                class="panel-card ep-empty"
            >
                <ShoppingCart class="h-5 w-5 text-[var(--ep-text-4)]" :stroke-width="1.75" aria-hidden="true" />
                <p class="ep-empty__title">Nenhuma venda encontrada.</p>
                <p class="ep-empty__text">Ajuste o período ou limpe os filtros para ver mais resultados.</p>
            </div>
        </div>

        <section class="panel-card ep-data hidden overflow-hidden sm:block" aria-labelledby="vendas-lista">
            <div class="flex items-center justify-between gap-3 px-5 py-4">
                <h2 id="vendas-lista" class="ep-section-title">Vendas</h2>
                <span class="text-[12px] tabular-nums text-[var(--ep-text-4)]">{{ displayNumber(stats.vendas_encontradas ?? 0) }} resultados</span>
            </div>
            <div class="overflow-x-auto border-t border-[var(--ep-line)]">
            <table class="ep-table">
                <thead>
                    <tr>
                        <th>
                            Data
                        </th>
                        <th>
                            Produto
                        </th>
                        <th>
                            Cliente
                        </th>
                        <th>
                            Status
                        </th>
                        <th class="ep-num">
                            Bruto
                        </th>
                        <th class="ep-num">
                            Taxa
                        </th>
                        <th class="ep-num">
                            Líquido
                        </th>
                        <th class="w-20 pl-2 pr-3">
                            <span class="sr-only">Ações</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="v in vendasList"
                        :key="v.id"
                        class="cursor-pointer"
                        @click="openDetail(v)"
                    >
                        <td class="whitespace-nowrap">
                            <p class="text-[13px] tabular-nums text-[var(--ep-text-2)]">{{ new Date(v.created_at).toLocaleDateString('pt-BR') }}</p>
                            <p class="mt-0.5 text-[11.5px] tabular-nums text-[var(--ep-text-4)]">{{ new Date(v.created_at).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }) }}</p>
                        </td>
                        <td class="max-w-[260px]">
                            <p class="truncate font-medium text-[var(--ep-text)]">{{ v.product_display_name ?? v.product?.name ?? '–' }}</p>
                            <p
                                v-if="v.is_affiliate_sale"
                                class="mt-1 flex flex-wrap items-center gap-1.5 text-[12px] text-[var(--ep-text-3)]"
                            >
                                <span class="ep-chip ep-chip--accent">Afiliado</span>
                                <span v-if="v.affiliate?.name">{{ v.affiliate.name }}</span>
                                <span v-else-if="v.affiliate?.code" class="font-mono">{{ v.affiliate.code }}</span>
                            </p>
                        </td>
                        <td>
                            <div class="flex min-w-0 items-center gap-3">
                                <span v-avatar="v.user?.name || v.email" class="ep-avatar shrink-0" aria-hidden="true">{{ String(v.user?.name || v.email || '?').trim().split(/\s+/).filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase() || '?' }}</span>
                                <div class="flex min-w-0 flex-col gap-0.5">
                                    <span class="truncate text-[13px] font-medium text-[var(--ep-text)]">
                                        {{ v.user?.name ?? '–' }}
                                    </span>
                                    <span class="truncate text-[12px] text-[var(--ep-text-4)]">
                                        {{ v.email ?? v.user?.email ?? '–' }}
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="flex flex-col items-start gap-1.5">
                                <span
                                    class="ep-chip"
                                    :class="{ completed: 'ep-chip--pos', pending: 'ep-chip--warn', disputed: 'ep-chip--neg' }[v.status] ?? ''"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                                    {{ statusBadgeLabel(v.status) }}
                                </span>
                                <span class="flex items-center gap-1.5 text-[12px] text-[var(--ep-text-3)]">
                                    <span
                                        class="h-1.5 w-1.5 shrink-0 rounded-full"
                                        :style="{ background: String(v.gateway_label ?? '').toUpperCase().startsWith('PIX') ? 'var(--ep-pix)' : v.gateway_label === 'Boleto' ? 'var(--ep-boleto)' : ['Cartão', 'Apple Pay', 'Google Pay', 'PayPal'].includes(v.gateway_label) ? 'var(--ep-cartao)' : 'var(--ep-text-4)' }"
                                        aria-hidden="true"
                                    />
                                    {{ v.gateway_label ?? '–' }}
                                </span>
                            </div>
                        </td>
                        <td class="ep-num text-[var(--ep-text-2)]">
                            {{ v.status === 'completed' ? displayMoney(v.gross_amount ?? v.amount_total, v.currency) : '—' }}
                        </td>
                        <td class="ep-num text-[var(--ep-text-3)]">
                            {{ v.status === 'completed' ? displayMoney(v.gateway_fee ?? 0, v.currency) : '—' }}
                        </td>
                        <td class="ep-num">
                            <p class="font-semibold tracking-[-0.01em] text-[var(--ep-text)]">{{ v.status === 'completed' ? displayMoney(v.net_amount ?? v.gross_amount, v.currency) : displayMoney(vendaDisplayAmount(v), v.currency) }}</p>
                            <p
                                v-if="v.display_amount_is_producer_share && v.sale_gross_total != null"
                                class="mt-0.5 text-[11.5px] font-normal text-[var(--ep-text-4)]"
                            >
                                Sua parte
                            </p>
                        </td>
                        <td class="relative whitespace-nowrap pl-2 pr-3" @click.stop>
                            <div class="flex items-center justify-end gap-1">
                                <a
                                    v-if="whatsappUrl(v)"
                                    :href="whatsappUrl(v)"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="flex h-8 w-8 items-center justify-center rounded-[10px] text-[#25D366] transition-colors duration-150 hover:bg-[var(--ep-hover)]"
                                    :aria-label="`WhatsApp de ${v.user?.name ?? 'cliente'}`"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                                    </svg>
                                </a>
                                <div class="relative" :data-venda-menu="v.id">
                                    <button
                                        type="button"
                                        class="flex h-8 w-8 items-center justify-center rounded-[10px] text-[var(--ep-text-3)] transition-colors duration-150 hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)]"
                                        aria-label="Abrir menu"
                                        aria-expanded="openMenuId === v.id"
                                        @click="toggleMenu(v.id, $event)"
                                    >
                                        <MoreVertical class="h-4 w-4" :stroke-width="1.75" />
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!vendasList.length">
                        <td colspan="8">
                            <div class="ep-empty">
                                <ShoppingCart class="h-5 w-5 text-[var(--ep-text-4)]" :stroke-width="1.75" aria-hidden="true" />
                                <p class="ep-empty__title">Nenhuma venda encontrada.</p>
                                <p class="ep-empty__text">Ajuste o período ou limpe os filtros para ver mais resultados.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            </div>
        </section>

        <!-- Paginação -->
        <nav
            v-if="hasPagination"
            class="relative z-10 flex w-full items-center gap-2 py-1"
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

            <div class="hidden min-w-0 flex-1 items-center justify-center gap-1 overflow-x-auto no-scrollbar tabular-nums sm:flex">
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

        <!-- Sidebar de detalhes -->
        <VendaDetailSidebar
            :open="sidebarOpen"
            :venda="selectedVenda"
            :plugin_order_detail_panels="plugin_order_detail_panels"
            @close="closeSidebar"
        />

        <!-- Toast local -->
        <Teleport to="body">
            <div
                v-if="openMenuId != null && menuVenda"
                ref="menuEl"
                class="fixed z-[99990] w-48 rounded-[14px] border border-[var(--ep-glass-border)] bg-[var(--ep-drawer)] p-1 shadow-[var(--ep-shadow-pop)] backdrop-blur-2xl backdrop-saturate-150"
                :style="{ top: `${menuPos.top}px`, left: `${menuPos.left}px` }"
                role="menu"
                aria-label="Ações da venda"
            >
                <button
                    type="button"
                    class="flex w-full items-center gap-2.5 rounded-[10px] px-2.5 py-2 text-left text-[13px] text-[var(--ep-text-2)] transition-colors duration-150 hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)]"
                    @click="openDetail(menuVenda)"
                >
                    <FileText class="h-4 w-4 shrink-0 text-[var(--ep-text-3)]" :stroke-width="1.75" />
                    Detalhes
                </button>
                <button
                    v-if="menuVenda.status === 'pending'"
                    type="button"
                    class="flex w-full items-center gap-2.5 rounded-[10px] px-2.5 py-2 text-left text-[13px] text-[var(--ep-pos)] transition-colors duration-150 hover:bg-[var(--ep-pos-bg)] disabled:opacity-50"
                    :disabled="approvingId === openMenuId"
                    @click="approveManually(menuVenda)"
                >
                    <CheckCircle class="h-4 w-4 shrink-0" :stroke-width="1.75" />
                    {{ approvingId === openMenuId ? 'Aprovando...' : 'Aprovar manualmente' }}
                </button>
                <button
                    v-if="canManageRefund && menuVenda.can_refund"
                    type="button"
                    class="flex w-full items-center gap-2.5 rounded-[10px] px-2.5 py-2 text-left text-[13px] text-[var(--ep-warn)] transition-colors duration-150 hover:bg-[var(--ep-warn-bg)]"
                    @click="openRefundModal(menuVenda)"
                >
                    <RotateCcw class="h-4 w-4 shrink-0" :stroke-width="1.75" />
                    Reembolsar
                </button>
                <button
                    type="button"
                    class="flex w-full items-center gap-2.5 rounded-[10px] px-2.5 py-2 text-left text-[13px] text-[var(--ep-text-2)] transition-colors duration-150 hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)] disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="resendingId === openMenuId || menuVenda.status === 'pending'"
                    title="Indisponível para pagamentos pendentes"
                    @click="resendEmail(menuVenda)"
                >
                    <Mail class="h-4 w-4 shrink-0 text-[var(--ep-text-3)]" :stroke-width="1.75" />
                    {{ resendingId === openMenuId ? 'Enviando...' : 'Reenviar e-mail de compra' }}
                </button>
                <template v-for="fp in plugin_fulfillment_providers" :key="`fp-${fp.plugin_slug}-${fp.id}`">
                    <div
                        v-if="fp.ui_mode === 'runtime' && menuVenda.status === 'completed'"
                        class="px-2.5 py-2"
                        @click="closeMenu"
                    >
                        <PluginRuntimeMount :item="fp" :context="{ order: menuVenda }" />
                    </div>
                    <a
                        v-else-if="(fp.href || fp.route) && menuVenda.status === 'completed'"
                        :href="pluginActionHref(fp, menuVenda)"
                        class="flex w-full items-center gap-2.5 rounded-[10px] px-2.5 py-2 text-left text-[13px] text-[var(--ep-text-2)] transition-colors duration-150 hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)]"
                        @click="closeMenu"
                    >
                        {{ fp.label ?? 'Enviar' }}
                    </a>
                </template>
                <template v-for="act in plugin_vendas_row_actions" :key="`act-${act.plugin_slug}-${act.id}`">
                    <div
                        v-if="act.ui_mode === 'runtime'"
                        class="px-2.5 py-2"
                        @click="closeMenu"
                    >
                        <PluginRuntimeMount :item="act" :context="{ order: menuVenda }" />
                    </div>
                    <a
                        v-else-if="act.href || act.route"
                        :href="pluginActionHref(act, menuVenda)"
                        class="flex w-full items-center gap-2.5 rounded-[10px] px-2.5 py-2 text-left text-[13px] text-[var(--ep-text-2)] transition-colors duration-150 hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)]"
                        @click="closeMenu"
                    >
                        {{ act.label ?? 'Ação' }}
                    </a>
                </template>
            </div>
            <div
                v-if="refundModalOpen && refundTarget"
                class="ep-scrim fixed inset-0 z-[100002] flex items-center justify-center p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="refund-venda-title"
                @click.self="closeRefundModal"
            >
                <div class="ep-modal w-full max-w-md p-6">
                    <div class="flex items-start gap-3">
                        <span
                            class="ep-kpi__icon shrink-0"
                            style="color: var(--ep-warn); background: var(--ep-warn-bg); border-color: color-mix(in oklab, var(--ep-warn) 28%, transparent); box-shadow: none"
                            aria-hidden="true"
                        >
                            <RotateCcw class="h-4 w-4" :stroke-width="1.75" />
                        </span>
                        <div class="min-w-0">
                            <h2 id="refund-venda-title" class="text-[16px] font-semibold tracking-[-0.02em] text-[var(--ep-text)]">
                                Confirmar reembolso
                            </h2>
                            <p class="mt-1 text-[13px] text-[var(--ep-text-3)]">
                                Pedido <strong class="font-mono font-medium text-[var(--ep-text-2)]">#{{ refundTarget.id }}</strong> —
                                <span class="font-medium tabular-nums text-[var(--ep-text)]">{{ displayMoney(refundTarget.amount_total ?? refundTarget.amount, refundTarget.currency) }}</span>
                                ({{ refundTarget.gateway_label ?? refundTarget.gateway ?? '–' }})
                            </p>
                        </div>
                    </div>
                    <p
                        v-if="refundTarget.refund_auto_cajupay_pix"
                        class="mt-4 rounded-xl border border-[color-mix(in_oklab,var(--ep-accent)_30%,transparent)] bg-[color-mix(in_oklab,var(--ep-accent)_10%,transparent)] px-3 py-2.5 text-[12.5px] leading-relaxed text-[var(--ep-text-2)]"
                    >
                        Pagamento PIX via CajuPay: o estorno será solicitado automaticamente na API. O status do pedido
                        será atualizado quando o gateway confirmar.
                    </p>
                    <p
                        v-else-if="refundTarget.gateway === 'cajupay'"
                        class="mt-4 rounded-xl border border-[color-mix(in_oklab,var(--ep-warn)_30%,transparent)] bg-[var(--ep-warn-bg)] px-3 py-2.5 text-[12.5px] leading-relaxed text-[var(--ep-text-2)]"
                    >
                        CajuPay (cartão/outro): registre o reembolso aqui e conclua o estorno no painel CajuPay. O acesso
                        do aluno será revogado ao confirmar o webhook.
                    </p>
                    <p
                        v-else
                        class="mt-4 rounded-xl border border-[color-mix(in_oklab,var(--ep-warn)_30%,transparent)] bg-[var(--ep-warn-bg)] px-3 py-2.5 text-[12.5px] leading-relaxed text-[var(--ep-text-2)]"
                    >
                        O pedido será marcado como reembolsado e o acesso do aluno será revogado imediatamente.
                    </p>
                    <label class="ep-label mt-5">
                        Observação (opcional)
                    </label>
                    <textarea
                        v-model="refundAdminNotes"
                        rows="2"
                        class="ep-input"
                        placeholder="Motivo ou nota interna…"
                    />
                    <div class="mt-6 flex gap-2">
                        <button
                            type="button"
                            class="ep-btn-secondary flex-1"
                            :disabled="refundingId === refundTarget.id"
                            @click="closeRefundModal"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            class="ep-btn-danger flex-1"
                            :disabled="refundingId === refundTarget.id"
                            @click="confirmRefund"
                        >
                            {{ refundingId === refundTarget.id ? 'Processando…' : 'Confirmar reembolso' }}
                        </button>
                    </div>
                </div>
            </div>
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="translate-y-2 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="translate-y-0 opacity-100"
                leave-to-class="translate-y-2 opacity-0"
            >
                <div
                    v-if="toast.message"
                    role="alert"
                    :class="[
                        'fixed bottom-4 right-4 z-[100001] flex max-w-sm items-start gap-2.5 rounded-[14px] border bg-[var(--ep-drawer)] px-4 py-3 shadow-[var(--ep-shadow-pop)] backdrop-blur-2xl backdrop-saturate-150',
                        toast.type === 'error'
                            ? 'border-[color-mix(in_oklab,var(--ep-neg)_40%,transparent)] text-[var(--ep-neg)]'
                            : 'border-[color-mix(in_oklab,var(--ep-pos)_40%,transparent)] text-[var(--ep-pos)]',
                    ]"
                >
                    <span class="mt-[6px] h-1.5 w-1.5 shrink-0 rounded-full bg-current" aria-hidden="true" />
                    <p class="text-[13px] font-medium text-[var(--ep-text)]">{{ toast.message }}</p>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
