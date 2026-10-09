<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import VendasTabs from '@/components/vendas/VendasTabs.vue';
import MoneyAmount from '@/components/ui/MoneyAmount.vue';
import { Repeat, TrendingUp, AlertTriangle, XCircle, Copy, Ban, Eye, RefreshCw, RotateCcw, X } from 'lucide-vue-next';

defineOptions({ layout: LayoutInfoprodutor });

const props = defineProps({
    stats: { type: Object, default: () => ({ ativas: 0, past_due: 0, canceladas: 0, clientes: 0, mrr: 0 }) },
    statusFilter: { type: String, default: 'all' },
    assinaturas: { type: [Array, Object], default: () => [] },
});

const assinaturasList = computed(() => props.assinaturas?.data ?? (Array.isArray(props.assinaturas) ? props.assinaturas : []));

const tabs = [
    { id: 'all', label: 'Todas' },
    { id: 'active', label: 'Ativas' },
    { id: 'past_due', label: 'Em atraso' },
    { id: 'cancelled', label: 'Canceladas' },
];

const cancellingId = ref(null);
const copiedId = ref(null);
const detailOpen = ref(false);
const detailLoading = ref(false);
const detailError = ref('');
const detail = ref(null);
const actionBusy = ref('');

function formatBRL(value) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value ?? 0);
}

function formatCents(cents) {
    return formatBRL((Number(cents) || 0) / 100);
}

function displayStatus(s) {
    return s.effective_status ?? s.status;
}

function statusBadgeClass(status) {
    const map = {
        active: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
        past_due: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
        cancelled: 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700/50 dark:text-zinc-300',
    };
    return map[status] ?? 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700/50 dark:text-zinc-300';
}

function statusBadgeLabel(status) {
    const map = {
        active: 'Ativa',
        past_due: 'Em atraso',
        cancelled: 'Cancelada',
    };
    return map[status] ?? status ?? '–';
}

function setStatusFilter(status) {
    router.get('/vendas/assinaturas', { status }, { preserveState: true, replace: true });
}

async function copyRenewalLink(s) {
    if (!s.renewal_url) return;
    try {
        await navigator.clipboard.writeText(s.renewal_url);
        copiedId.value = s.id;
        setTimeout(() => {
            if (copiedId.value === s.id) copiedId.value = null;
        }, 2000);
    } catch {
        /* ignore */
    }
}

async function cancelSubscription(s, revokeNow = false) {
    const msg = revokeNow
        ? 'Cancelar agora e revogar o acesso imediatamente?'
        : 'Cancelar assinatura? O cliente mantém acesso até o fim da carência (se configurada).';
    if (!window.confirm(msg)) return;

    cancellingId.value = s.id;
    try {
        const { data } = await axios.post(`/vendas/assinaturas/${s.id}/cancel`, {
            revoke_access_now: revokeNow,
        });
        if (!data.success) {
            window.alert(data.message || 'Não foi possível cancelar.');
            return;
        }
        if (detail.value?.id === s.id) {
            closeDetail();
        }
        router.reload({ preserveScroll: true });
    } catch (err) {
        window.alert(err.response?.data?.message || 'Erro ao cancelar assinatura.');
    } finally {
        cancellingId.value = null;
    }
}

async function openDetail(s) {
    if (!s?.id) {
        detailError.value = 'Assinatura inválida.';
        return;
    }
    detailOpen.value = true;
    detailLoading.value = true;
    detailError.value = '';
    detail.value = null;
    try {
        const { data } = await axios.get(`/vendas/assinaturas/${s.id}`, {
            headers: { Accept: 'application/json' },
        });
        if (!data?.success) {
            detailError.value = data?.message || 'Não foi possível carregar o detalhe.';
            return;
        }
        detail.value = data.subscription;
    } catch (err) {
        const status = err.response?.status;
        detailError.value = err.response?.data?.message
            || (status ? `Erro ao carregar assinatura (HTTP ${status}).` : 'Erro ao carregar assinatura. Recarregue a página e tente de novo.');
    } finally {
        detailLoading.value = false;
    }
}

function closeDetail() {
    detailOpen.value = false;
    detail.value = null;
    detailError.value = '';
}

async function syncSubscription() {
    if (!detail.value?.is_cajupay) return;
    actionBusy.value = 'sync';
    try {
        const { data } = await axios.post(`/vendas/assinaturas/${detail.value.id}/sync`);
        if (!data.success) {
            detailError.value = data.message || 'Falha no sync.';
            return;
        }
        await openDetail({ id: detail.value.id });
    } catch (err) {
        detailError.value = err.response?.data?.message || 'Erro ao sincronizar.';
    } finally {
        actionBusy.value = '';
    }
}

async function refundCharge(charge) {
    if (!detail.value?.is_cajupay || !charge?.id) return;
    if (!window.confirm('Reembolsar esta parcela na CajuPay?')) return;
    actionBusy.value = `refund-${charge.id}`;
    try {
        const { data } = await axios.post(`/vendas/assinaturas/${detail.value.id}/charges/${encodeURIComponent(charge.id)}/refund`);
        if (!data.success) {
            detailError.value = data.message || 'Falha no reembolso.';
            return;
        }
        await openDetail({ id: detail.value.id });
    } catch (err) {
        detailError.value = err.response?.data?.message || 'Erro ao reembolsar parcela.';
    } finally {
        actionBusy.value = '';
    }
}

async function retryCharge(charge) {
    if (!detail.value?.is_cajupay || !charge?.id) return;
    actionBusy.value = `retry-${charge.id}`;
    try {
        const { data } = await axios.post(`/vendas/assinaturas/${detail.value.id}/charges/${encodeURIComponent(charge.id)}/retry`);
        if (!data.success) {
            detailError.value = data.message || 'Falha no retry.';
            return;
        }
        await openDetail({ id: detail.value.id });
    } catch (err) {
        detailError.value = err.response?.data?.message || 'Erro ao retentar parcela.';
    } finally {
        actionBusy.value = '';
    }
}

function chargeCanRefund(charge) {
    const status = String(charge?.status || '').toLowerCase();
    return ['paid', 'completed', 'devolvido', 'settled'].includes(status);
}

function chargeCanRetry(charge) {
    const status = String(charge?.status || '').toLowerCase();
    return ['failed', 'rejected', 'past_due', 'unpaid', 'overdue'].includes(status);
}
</script>

<template>
    <div class="space-y-5">
        <VendasTabs />

        <!-- MRR (herói) + contagens por status -->
        <div class="grid gap-4 lg:grid-cols-12">
            <section class="panel-card ep-glow-card flex flex-col p-6 lg:col-span-5" aria-labelledby="assin-mrr">
                <div class="flex items-center justify-between gap-3">
                    <h2 id="assin-mrr" class="text-[13px] font-medium text-[var(--ep-text-2)]" title="Receita recorrente mensal">MRR</h2>
                    <span class="ep-chip tabular-nums">
                        <span class="h-1.5 w-1.5 rounded-full bg-[var(--ep-pos)]" aria-hidden="true" />
                        {{ stats.ativas }} ativas
                    </span>
                </div>
                <MoneyAmount :value="Number(stats.mrr ?? 0)" size="hero" class="mt-5 block" />
                <p class="mt-3 text-[12.5px] text-[var(--ep-text-3)]">Soma dos planos das assinaturas ativas.</p>

                <div class="mt-6 lg:mt-auto lg:pt-6">
                    <div
                        class="flex h-2.5 w-full gap-[3px] overflow-hidden rounded-full bg-[var(--ep-active)]"
                        aria-hidden="true"
                    >
                        <span
                            class="h-full rounded-full bg-[var(--ep-pos)]"
                            :style="{ width: `${(Number(stats.ativas) || 0) / ((Number(stats.ativas) || 0) + (Number(stats.past_due) || 0) + (Number(stats.canceladas) || 0) || 1) * 100}%` }"
                        />
                        <span
                            class="h-full rounded-full bg-[var(--ep-warn)]"
                            :style="{ width: `${(Number(stats.past_due) || 0) / ((Number(stats.ativas) || 0) + (Number(stats.past_due) || 0) + (Number(stats.canceladas) || 0) || 1) * 100}%` }"
                        />
                        <span
                            class="h-full rounded-full bg-[var(--ep-text-4)]"
                            :style="{ width: `${(Number(stats.canceladas) || 0) / ((Number(stats.ativas) || 0) + (Number(stats.past_due) || 0) + (Number(stats.canceladas) || 0) || 1) * 100}%` }"
                        />
                    </div>
                    <div class="mt-2.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11.5px] text-[var(--ep-text-3)]">
                        <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[var(--ep-pos)]" aria-hidden="true" />Ativas</span>
                        <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[var(--ep-warn)]" aria-hidden="true" />Em atraso</span>
                        <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[var(--ep-text-4)]" aria-hidden="true" />Canceladas</span>
                    </div>
                </div>
            </section>

            <div class="grid gap-4 sm:grid-cols-3 lg:col-span-7">
                <div class="panel-card ep-kpi">
                    <div class="flex items-start justify-between gap-3">
                        <span class="ep-kpi__label">Ativas</span>
                        <span
                            class="ep-kpi__icon"
                            style="color: var(--ep-pos); background: var(--ep-pos-bg); border-color: color-mix(in oklab, var(--ep-pos) 28%, transparent); box-shadow: none"
                            aria-hidden="true"
                        >
                            <Repeat class="h-4 w-4" :stroke-width="1.75" />
                        </span>
                    </div>
                    <p class="ep-kpi__value">{{ stats.ativas }}</p>
                    <span class="ep-kpi__meta">renovando normalmente</span>
                </div>
                <div class="panel-card ep-kpi">
                    <div class="flex items-start justify-between gap-3">
                        <span class="ep-kpi__label">Em atraso</span>
                        <span
                            class="ep-kpi__icon"
                            style="color: var(--ep-warn); background: var(--ep-warn-bg); border-color: color-mix(in oklab, var(--ep-warn) 28%, transparent); box-shadow: none"
                            aria-hidden="true"
                        >
                            <AlertTriangle class="h-4 w-4" :stroke-width="1.75" />
                        </span>
                    </div>
                    <p class="ep-kpi__value">{{ stats.past_due }}</p>
                    <span class="ep-kpi__meta">cobrança pendente</span>
                </div>
                <div class="panel-card ep-kpi">
                    <div class="flex items-start justify-between gap-3">
                        <span class="ep-kpi__label">Canceladas</span>
                        <span
                            class="ep-kpi__icon"
                            style="color: var(--ep-text-3); background: var(--ep-active); border-color: var(--ep-line-strong); box-shadow: none"
                            aria-hidden="true"
                        >
                            <XCircle class="h-4 w-4" :stroke-width="1.75" />
                        </span>
                    </div>
                    <p class="ep-kpi__value">{{ stats.canceladas }}</p>
                    <span class="ep-kpi__meta">encerradas</span>
                </div>
            </div>
        </div>

        <section class="panel-card ep-data overflow-hidden" aria-labelledby="assin-lista">
            <div class="flex flex-col gap-4 px-5 pb-4 pt-5 lg:flex-row lg:items-end lg:justify-between">
                <div class="min-w-0">
                    <h2 id="assin-lista" class="text-[15px] font-semibold tracking-[-0.02em] text-[var(--ep-text)]">Assinaturas</h2>
                    <p class="mt-1 max-w-2xl text-[12.5px] leading-relaxed text-[var(--ep-text-4)]">
                        Status atualizado automaticamente. Assinaturas CajuPay (PIX Automático) renovam pelo gateway; use o detalhe para cobranças, sync, retry e reembolso.
                    </p>
                </div>
                <div class="ep-tabs max-w-full shrink-0 self-start overflow-x-auto no-scrollbar lg:self-auto">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        type="button"
                        :class="[
                            'ep-tab border',
                            statusFilter === tab.id ? 'ep-tab--active' : 'border-transparent',
                        ]"
                        @click="setStatusFilter(tab.id)"
                    >
                        {{ tab.label }}
                    </button>
                </div>
            </div>

            <div v-if="assinaturasList.length > 0" class="border-t border-[var(--ep-line)] p-4 sm:hidden">
                <div class="space-y-3">
                    <div
                        v-for="s in assinaturasList"
                        :key="s.id"
                        class="rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-start gap-3">
                                <span v-avatar="s.user?.name || s.user?.email" class="ep-avatar shrink-0" aria-hidden="true">{{ String(s.user?.name || s.user?.email || '?').trim().split(/\s+/).filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase() || '?' }}</span>
                                <div class="min-w-0">
                                    <p class="break-words text-[13.5px] font-medium leading-snug text-[var(--ep-text)]">
                                        {{ s.user?.name || '—' }}
                                    </p>
                                    <p class="mt-0.5 break-words text-[12px] leading-snug text-[var(--ep-text-4)]">
                                        {{ s.user?.email || '—' }}
                                    </p>
                                </div>
                            </div>
                            <span
                                class="ep-chip shrink-0"
                                :class="{ active: 'ep-chip--pos', past_due: 'ep-chip--warn' }[displayStatus(s)] ?? ''"
                            >
                                <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                                {{ statusBadgeLabel(displayStatus(s)) }}
                            </span>
                        </div>

                        <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2.5 border-t border-[var(--ep-line)] pt-3 text-[12.5px]">
                            <div class="min-w-0">
                                <dt class="text-[11.5px] text-[var(--ep-text-4)]">Produto</dt>
                                <dd class="mt-0.5 truncate text-[var(--ep-text)]">{{ s.product?.name || '—' }}</dd>
                            </div>
                            <div class="min-w-0">
                                <dt class="text-[11.5px] text-[var(--ep-text-4)]">Plano</dt>
                                <dd class="mt-0.5 truncate text-[var(--ep-text)]">{{ s.plan?.name || '—' }}</dd>
                            </div>
                            <div class="min-w-0">
                                <dt class="text-[11.5px] text-[var(--ep-text-4)]">Gateway</dt>
                                <dd class="mt-0.5 truncate text-[var(--ep-text-2)]">{{ s.gateway_label || '—' }}</dd>
                            </div>
                            <div class="min-w-0">
                                <dt class="text-[11.5px] text-[var(--ep-text-4)]">Vence em</dt>
                                <dd class="mt-0.5 truncate tabular-nums text-[var(--ep-text-2)]">{{ s.current_period_end || '—' }}</dd>
                            </div>
                        </dl>

                        <div class="mt-3 flex flex-wrap gap-1.5">
                            <button
                                type="button"
                                class="ep-btn-secondary h-8 px-3 text-[12.5px]"
                                @click="openDetail(s)"
                            >
                                <Eye class="h-3.5 w-3.5 text-[var(--ep-text-3)]" :stroke-width="1.75" />
                                Detalhe
                            </button>
                            <button
                                v-if="displayStatus(s) !== 'cancelled'"
                                type="button"
                                class="ep-btn-ghost h-8 px-3 text-[12.5px]"
                                @click="copyRenewalLink(s)"
                            >
                                <Copy class="h-3.5 w-3.5" :stroke-width="1.75" />
                                {{ copiedId === s.id ? 'Copiado' : 'Link renovação' }}
                            </button>
                            <button
                                v-if="displayStatus(s) !== 'cancelled'"
                                type="button"
                                class="ep-btn-danger h-8 px-3 text-[12.5px]"
                                :disabled="cancellingId === s.id"
                                @click="cancelSubscription(s, false)"
                            >
                                <Ban class="h-3.5 w-3.5" :stroke-width="1.75" />
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="assinaturasList.length > 0" class="hidden overflow-x-auto border-t border-[var(--ep-line)] sm:block">
                <table class="ep-table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Produto / Plano</th>
                            <th>Período</th>
                            <th>Status</th>
                            <th class="ep-num">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in assinaturasList" :key="s.id">
                            <td>
                                <div class="flex min-w-0 items-center gap-3">
                                    <span v-avatar="s.user?.name || s.user?.email" class="ep-avatar shrink-0" aria-hidden="true">{{ String(s.user?.name || s.user?.email || '?').trim().split(/\s+/).filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase() || '?' }}</span>
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-[var(--ep-text)]">{{ s.user?.name || '—' }}</p>
                                        <p class="truncate text-[12px] text-[var(--ep-text-4)]">{{ s.user?.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <p class="font-medium text-[var(--ep-text)]">{{ s.product?.name || '—' }}</p>
                                <p class="mt-0.5 text-[12px] text-[var(--ep-text-3)]">{{ s.plan?.name }} · {{ s.plan?.interval_label || s.plan?.interval }}</p>
                                <p class="text-[12px] text-[var(--ep-text-4)]">{{ s.gateway_label }}</p>
                            </td>
                            <td class="whitespace-nowrap">
                                <p class="tabular-nums text-[var(--ep-text-2)]">Vence: {{ s.current_period_end || '—' }}</p>
                                <p v-if="s.access_until" class="mt-0.5 text-[12px] tabular-nums text-[var(--ep-text-4)]">Acesso até {{ s.access_until }}</p>
                            </td>
                            <td>
                                <span
                                    class="ep-chip"
                                    :class="{ active: 'ep-chip--pos', past_due: 'ep-chip--warn' }[displayStatus(s)] ?? ''"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                                    {{ statusBadgeLabel(displayStatus(s)) }}
                                </span>
                            </td>
                            <td class="ep-num">
                                <div class="flex justify-end gap-1">
                                    <button
                                        type="button"
                                        class="ep-btn-secondary h-8 px-2.5 text-[12.5px]"
                                        @click="openDetail(s)"
                                    >
                                        <Eye class="h-3.5 w-3.5 text-[var(--ep-text-3)]" :stroke-width="1.75" />
                                        Detalhe
                                    </button>
                                    <template v-if="displayStatus(s) !== 'cancelled'">
                                        <button
                                            type="button"
                                            class="ep-btn-ghost h-8 px-2.5 text-[12.5px]"
                                            @click="copyRenewalLink(s)"
                                        >
                                            <Copy class="h-3.5 w-3.5" :stroke-width="1.75" />
                                            {{ copiedId === s.id ? 'Copiado' : 'Link' }}
                                        </button>
                                        <button
                                            type="button"
                                            class="ep-btn-ghost h-8 px-2.5 text-[12.5px] text-[var(--ep-neg)] hover:bg-[var(--ep-neg-bg)] hover:text-[var(--ep-neg)]"
                                            :disabled="cancellingId === s.id"
                                            @click="cancelSubscription(s, false)"
                                        >
                                            <Ban class="h-3.5 w-3.5" :stroke-width="1.75" />
                                            Cancelar
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <nav
                v-if="assinaturas?.links?.length > 3"
                class="flex items-center justify-center gap-1 border-t border-[var(--ep-line)] px-4 py-3 tabular-nums"
                aria-label="Paginação"
            >
                <a
                    v-for="link in assinaturas.links"
                    :key="link.label"
                    :href="link.url"
                    :aria-current="link.active ? 'page' : undefined"
                    :aria-disabled="!link.url"
                    :class="[
                        'relative inline-flex h-9 min-w-9 items-center justify-center rounded-[10px] px-3 text-[13px] font-medium transition-colors duration-150',
                        link.active
                            ? 'ep-tab--active text-[var(--ep-text)]'
                            : link.url
                              ? 'text-[var(--ep-text-3)] hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)]'
                              : 'cursor-not-allowed text-[var(--ep-text-4)] opacity-60',
                    ]"
                    v-html="link.label"
                    @click.prevent="link.url && router.visit(link.url, { preserveState: true })"
                />
            </nav>
            <div v-else-if="assinaturasList.length === 0" class="ep-empty border-t border-[var(--ep-line)]">
                <Repeat class="h-5 w-5 text-[var(--ep-text-4)]" :stroke-width="1.75" aria-hidden="true" />
                <p class="ep-empty__title">Nenhuma assinatura encontrada</p>
                <p class="ep-empty__text">
                    Assinaturas de produtos recorrentes aparecem aqui após a primeira venda.
                </p>
            </div>
        </section>

        <Teleport to="body">
            <div
                v-if="detailOpen"
                class="fixed inset-0 z-[100000] flex justify-end"
                aria-modal="true"
                role="dialog"
            >
                <div
                    class="ep-scrim fixed inset-0"
                    aria-hidden="true"
                    @click="closeDetail"
                />
                <aside class="ep-drawer relative z-[100001] flex h-full w-full max-w-md flex-col sm:w-[440px] sm:rounded-l-[22px]">
                    <div class="flex items-start justify-between gap-3 px-6 pb-4 pt-6">
                        <div class="min-w-0">
                            <p class="text-[12px] text-[var(--ep-text-3)]">Assinatura</p>
                            <h3 class="mt-0.5 text-[17px] font-semibold tracking-[-0.02em] text-[var(--ep-text)]">Detalhe da assinatura</h3>
                        </div>
                        <button type="button" class="ep-btn-ghost ep-btn-icon -mr-2 shrink-0" aria-label="Fechar" @click="closeDetail">
                            <X class="h-[18px] w-[18px]" :stroke-width="1.75" />
                        </button>
                    </div>
                    <div class="ep-divider" aria-hidden="true" />
                    <div class="flex-1 overflow-y-auto px-6 py-5">
                        <div v-if="detailLoading" class="space-y-3" aria-busy="true">
                            <p class="text-[13px] text-[var(--ep-text-3)]">Carregando…</p>
                            <div class="h-24 animate-pulse rounded-2xl bg-[var(--ep-active)]" aria-hidden="true" />
                            <div class="h-40 animate-pulse rounded-2xl bg-[var(--ep-active)] opacity-70" aria-hidden="true" />
                        </div>
                        <p
                            v-else-if="detailError"
                            class="flex items-start gap-2.5 rounded-[14px] border border-[color-mix(in_oklab,var(--ep-neg)_35%,transparent)] bg-[var(--ep-neg-bg)] px-3 py-2.5 text-[13px] text-[var(--ep-text)]"
                            role="alert"
                        >
                            <span class="mt-[6px] h-1.5 w-1.5 shrink-0 rounded-full bg-[var(--ep-neg)]" aria-hidden="true" />
                            {{ detailError }}
                        </p>
                        <template v-else-if="detail">
                            <p
                                v-if="detail.remote_error"
                                class="mb-4 rounded-[14px] border border-[color-mix(in_oklab,var(--ep-warn)_35%,transparent)] bg-[var(--ep-warn-bg)] px-3 py-2.5 text-[12.5px] text-[var(--ep-text-2)]"
                            >
                                {{ detail.remote_error }}
                            </p>

                            <section class="panel-card ep-glow-card p-5" aria-label="Resumo da assinatura">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-[12.5px] font-medium text-[var(--ep-text-3)]">Valor do plano</span>
                                    <span
                                        class="ep-chip"
                                        :class="{ active: 'ep-chip--pos', past_due: 'ep-chip--warn' }[detail.effective_status || detail.status] ?? ''"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                                        {{ statusBadgeLabel(detail.effective_status || detail.status) }}
                                    </span>
                                </div>
                                <p class="mt-3 text-[30px] font-semibold leading-none tabular-nums tracking-[-0.04em] text-[var(--ep-text)]">{{ formatBRL(detail.plan?.price) }}</p>
                                <p class="mt-2 truncate text-[12.5px] text-[var(--ep-text-3)]">{{ detail.plan?.name }} · {{ detail.plan?.interval_label }}</p>
                            </section>

                            <dl class="mt-4 text-[13px]">
                                <div class="flex items-start justify-between gap-4 border-b border-[var(--ep-line)] py-2.5">
                                    <dt class="shrink-0 text-[var(--ep-text-3)]">Cliente</dt>
                                    <dd class="min-w-0 text-right text-[var(--ep-text)]">
                                        {{ detail.user?.name }}
                                        <span class="block break-all text-[12px] text-[var(--ep-text-4)]">({{ detail.user?.email }})</span>
                                    </dd>
                                </div>
                                <div class="flex items-start justify-between gap-4 border-b border-[var(--ep-line)] py-2.5">
                                    <dt class="shrink-0 text-[var(--ep-text-3)]">Produto</dt>
                                    <dd class="min-w-0 text-right text-[var(--ep-text)]">{{ detail.product?.name }}</dd>
                                </div>
                                <div class="flex items-start justify-between gap-4 border-b border-[var(--ep-line)] py-2.5">
                                    <dt class="shrink-0 text-[var(--ep-text-3)]">Plano</dt>
                                    <dd class="min-w-0 text-right text-[var(--ep-text)]">{{ detail.plan?.name }} · {{ detail.plan?.interval_label }} · <span class="tabular-nums">{{ formatBRL(detail.plan?.price) }}</span></dd>
                                </div>
                                <div class="flex items-start justify-between gap-4 border-b border-[var(--ep-line)] py-2.5">
                                    <dt class="shrink-0 text-[var(--ep-text-3)]">Período</dt>
                                    <dd class="min-w-0 text-right tabular-nums text-[var(--ep-text)]">{{ detail.current_period_start }} → {{ detail.current_period_end }}</dd>
                                </div>
                                <div v-if="detail.gateway_subscription_id" class="flex items-start justify-between gap-4 border-b border-[var(--ep-line)] py-2.5 last:border-b-0">
                                    <dt class="shrink-0 text-[var(--ep-text-3)]">ID gateway</dt>
                                    <dd class="min-w-0 break-all text-right font-mono text-[12px] text-[var(--ep-text-2)]">{{ detail.gateway_subscription_id }}</dd>
                                </div>
                                <div v-if="detail.remote_status" class="flex items-start justify-between gap-4 border-b border-[var(--ep-line)] py-2.5 last:border-b-0">
                                    <dt class="shrink-0 text-[var(--ep-text-3)]">Status CajuPay</dt>
                                    <dd class="min-w-0 text-right text-[var(--ep-text)]">{{ detail.remote_status }}</dd>
                                </div>
                            </dl>

                            <div v-if="detail.is_cajupay" class="mt-4 flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    class="ep-btn-secondary h-8 px-3 text-[12.5px]"
                                    :disabled="actionBusy === 'sync'"
                                    @click="syncSubscription"
                                >
                                    <RefreshCw class="h-3.5 w-3.5 text-[var(--ep-text-3)]" :class="actionBusy === 'sync' ? 'animate-spin' : ''" :stroke-width="1.75" />
                                    Sync CajuPay
                                </button>
                                <button
                                    v-if="(detail.effective_status || detail.status) !== 'cancelled'"
                                    type="button"
                                    class="ep-btn-danger h-8 px-3 text-[12.5px]"
                                    @click="cancelSubscription(detail, false)"
                                >
                                    <Ban class="h-3.5 w-3.5" :stroke-width="1.75" />
                                    Cancelar
                                </button>
                            </div>

                            <section class="mt-6" aria-label="Cobranças">
                                <h4 class="ep-section-title">Cobranças</h4>
                                <ul v-if="detail.charges?.length" class="mt-2 overflow-hidden rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)]">
                                    <li
                                        v-for="c in detail.charges"
                                        :key="c.id"
                                        class="border-b border-[var(--ep-line)] px-4 py-3 last:border-b-0"
                                    >
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="text-[14px] font-semibold tabular-nums tracking-[-0.01em] text-[var(--ep-text)]">{{ formatCents(c.amount_cents) }} <span class="text-[12px] font-normal text-[var(--ep-text-3)]">· {{ c.status || '—' }}</span></p>
                                                <p class="mt-0.5 truncate font-mono text-[11px] text-[var(--ep-text-4)]">{{ c.id }}</p>
                                            </div>
                                            <div v-if="detail.is_cajupay" class="flex shrink-0 gap-1">
                                                <button
                                                    v-if="chargeCanRetry(c)"
                                                    type="button"
                                                    class="ep-btn-secondary h-7 rounded-[9px] px-2.5 text-[11.5px]"
                                                    :disabled="actionBusy === `retry-${c.id}`"
                                                    @click="retryCharge(c)"
                                                >
                                                    Retry
                                                </button>
                                                <button
                                                    v-if="chargeCanRefund(c)"
                                                    type="button"
                                                    class="ep-btn-danger h-7 gap-1 rounded-[9px] px-2.5 text-[11.5px]"
                                                    :disabled="actionBusy === `refund-${c.id}`"
                                                    @click="refundCharge(c)"
                                                >
                                                    <RotateCcw class="h-3 w-3" :stroke-width="1.75" />
                                                    Reembolsar
                                                </button>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                                <p v-else class="mt-2 rounded-2xl border border-dashed border-[var(--ep-line-strong)] px-4 py-5 text-center text-[12.5px] text-[var(--ep-text-4)]">Nenhuma cobrança listada.</p>
                            </section>
                        </template>
                    </div>
                </aside>
            </div>
        </Teleport>
    </div>
</template>
