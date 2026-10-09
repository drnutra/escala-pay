<script setup>
import { computed, onUnmounted, ref, watch } from 'vue';
import Button from '@/components/ui/Button.vue';
import MoneyAmount from '@/components/ui/MoneyAmount.vue';
import {
    Wallet,
    Clock,
    Lock,
    TrendingUp,
    TrendingDown,
    ArrowDownToLine,
    QrCode,
    CreditCard,
    Receipt,
    ChevronDown,
    ArrowDownCircle,
    ArrowUpCircle,
} from 'lucide-vue-next';

const props = defineProps({
    balances: { type: Object, default: () => ({ by_wallet: {}, totals: {} }) },
    walletLabels: { type: Object, default: () => ({}) },
    payouts: { type: Array, default: () => [] },
    transactions: { type: Array, default: () => [] },
    commissions: { type: Array, default: () => [] },
    pixKey: { type: String, default: '' },
    pixKeyType: { type: String, default: 'email' },
    pixOwnerDocument: { type: String, default: '' },
    minPayoutCents: { type: Number, default: 100 },
    cajupayConnected: { type: Boolean, default: true },
    payoutBaseUrl: { type: String, required: true },
    pixBaseUrl: { type: String, required: true },
    partnerSummary: { type: Object, default: null },
    showCommissionsTable: { type: Boolean, default: true },
    canManage: { type: Boolean, default: true },
});

const emit = defineEmits(['reload']);

const IN_FLIGHT_PAYOUT_STATUSES = ['processing', 'awaiting_payout'];
const PAYOUT_POLL_MS = 60_000;

const hasInFlightPayout = computed(() =>
    props.payouts.some((p) => IN_FLIGHT_PAYOUT_STATUSES.includes(p.status))
);

let payoutPollTimer = null;

function stopPayoutPoll() {
    if (payoutPollTimer !== null) {
        clearInterval(payoutPollTimer);
        payoutPollTimer = null;
    }
}

function startPayoutPoll() {
    if (payoutPollTimer !== null) {
        return;
    }
    payoutPollTimer = setInterval(() => emit('reload'), PAYOUT_POLL_MS);
}

watch(
    hasInFlightPayout,
    (active) => {
        if (active) {
            startPayoutPoll();
        } else {
            stopPayoutPoll();
        }
    },
    { immediate: true },
);

onUnmounted(stopPayoutPoll);

const walletKeys = ['pix', 'card', 'boleto'];

const walletIcons = {
    pix: QrCode,
    card: CreditCard,
    boleto: Receipt,
};

const selectedWallet = ref('pix');
const showPixForm = ref(false);
const showWithdrawForm = ref(false);
const withdrawAll = ref(true);
const payoutAmount = ref(0);
const payoutMsg = ref('');
const saving = ref(false);
const paying = ref(false);

const pixForm = ref({
    pix_key: props.pixKey || '',
    pix_key_type: props.pixKeyType || 'email',
    pix_owner_document: props.pixOwnerDocument || '',
});

watch(
    () => [props.pixKey, props.pixKeyType, props.pixOwnerDocument],
    () => {
        pixForm.value = {
            pix_key: props.pixKey || '',
            pix_key_type: props.pixKeyType || 'email',
            pix_owner_document: props.pixOwnerDocument || '',
        };
    }
);

const walletBalance = computed(() => props.balances?.by_wallet?.[selectedWallet.value] || {});

const metrics = computed(() => {
    const w = walletBalance.value;
    const totals = props.balances?.totals || {};
    return {
        available: w.available ?? 0,
        pending: w.pending ?? 0,
        reserved: w.reserved ?? 0,
        paidOut: w.paid_total ?? 0,
        totalAvailable: totals.available ?? 0,
    };
});

const maxAvailable = computed(() => metrics.value.available);
const minPayoutReais = computed(() => props.minPayoutCents / 100);

const needsOwnerDocument = computed(() =>
    ['email', 'phone', 'random'].includes(pixForm.value.pix_key_type)
);

const pixKeyTypeLabels = {
    email: 'E-mail',
    cpf: 'CPF',
    cnpj: 'CNPJ',
    phone: 'Telefone',
    random: 'Chave aleatória',
};

const withdrawPixDestination = computed(() => {
    if (!props.pixKey) {
        return null;
    }
    const typeLabel = pixKeyTypeLabels[props.pixKeyType] || props.pixKeyType || 'PIX';
    return `${typeLabel}: ${props.pixKey}`;
});

const entradasPeriodo = computed(() => {
    const credits = props.transactions.filter((t) => t.type === 'credit');
    return credits.reduce((s, t) => s + (t.amount || 0), 0);
});

const saidasPeriodo = computed(() => {
    const debits = props.transactions.filter((t) => t.type === 'debit');
    return debits.reduce((s, t) => s + (t.amount || 0), 0);
});

const chartBars = computed(() => {
    const days = 14;
    const map = {};
    const now = new Date();
    for (let i = days - 1; i >= 0; i--) {
        const d = new Date(now);
        d.setDate(d.getDate() - i);
        const key = d.toISOString().slice(0, 10);
        map[key] = 0;
    }
    for (const c of props.commissions) {
        if (!c.created_at) continue;
        const key = c.created_at.slice(0, 10);
        if (key in map) {
            map[key] += Number(c.commission_amount) || 0;
        }
    }
    const values = Object.values(map);
    const max = Math.max(...values, 1);
    return Object.entries(map).map(([date, value]) => ({
        date,
        label: new Date(date + 'T12:00:00').toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' }),
        value,
        height: Math.max(4, Math.round((value / max) * 100)),
    }));
});

const recentMovements = computed(() => {
    const items = [];
    for (const t of props.transactions.slice(0, 8)) {
        items.push({
            id: `t-${t.id}`,
            type: t.type,
            label: t.description || (t.type === 'credit' ? 'Comissão' : 'Saque'),
            amount: t.amount,
            date: t.created_at,
        });
    }
    for (const p of props.payouts.slice(0, 5)) {
        const dest = p.pix_destination || '';
        const wallet = p.wallet_label || '';
        const label = dest
            ? `Saque para ${dest}${wallet ? ` · ${wallet}` : ''}`
            : `Saque ${wallet}`.trim();
        items.push({
            id: `p-${p.id}`,
            type: 'debit',
            label,
            amount: p.amount,
            date: p.created_at,
            status: p.status,
        });
    }
    return items
        .sort((a, b) => new Date(b.date || 0) - new Date(a.date || 0))
        .slice(0, 10);
});

const statusLabels = {
    pending: 'Pendente',
    available: 'Disponível',
    reserved: 'Em saque',
    paid: 'Pago',
    pending_approval: 'Aguardando aprovação',
    awaiting_payout: 'Processando PIX',
    processing: 'Processando',
    completed: 'Concluído',
    failed: 'Falhou',
    cancelled: 'Cancelado',
    settled_externally: 'Split',
};

function formatBRL(v) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v ?? 0);
}

function formatDate(iso) {
    if (!iso) return '—';
    return new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(iso));
}

function selectWallet(key) {
    selectedWallet.value = key;
    payoutAmount.value = props.balances?.by_wallet?.[key]?.available ?? 0;
    withdrawAll.value = true;
}

async function savePix() {
    saving.value = true;
    payoutMsg.value = '';
    try {
        const axios = (await import('axios')).default;
        await axios.post(props.pixBaseUrl, pixForm.value);
        payoutMsg.value = 'Chave PIX salva.';
        showPixForm.value = false;
        emit('reload');
    } catch (e) {
        const errors = e.response?.data?.errors;
        payoutMsg.value = errors
            ? Object.values(errors).flat().join(' ')
            : e.response?.data?.message || 'Erro ao salvar PIX.';
    } finally {
        saving.value = false;
    }
}

async function requestPayout() {
    paying.value = true;
    payoutMsg.value = '';
    try {
        const axios = (await import('axios')).default;
        const payload = {
            wallet_bucket: selectedWallet.value,
            withdraw_all: withdrawAll.value,
        };
        if (!withdrawAll.value) {
            payload.amount = payoutAmount.value;
        }
        const { data } = await axios.post(props.payoutBaseUrl, payload);
        const status = data?.payout?.status ?? data?.payout_request?.status;
        const dest =
            data?.payout?.pix_destination
            || data?.payout_request?.pix_destination
            || withdrawPixDestination.value;
        const destSuffix = dest ? ` Destino: ${dest}.` : '';
        payoutMsg.value =
            (data?.message ||
                (status === 'completed'
                    ? 'Saque concluído.'
                    : status === 'pending_approval'
                      ? 'Solicitação enviada. Aguarde aprovação do produtor.'
                      : status === 'awaiting_payout' || status === 'processing'
                        ? 'Saque em processamento. O status será atualizado em breve.'
                        : 'Solicitação registrada.')) + destSuffix;
        if (status === 'completed' || status === 'pending_approval') {
            showWithdrawForm.value = false;
        }
        emit('reload');
    } catch (e) {
        const errors = e.response?.data?.errors;
        payoutMsg.value = errors
            ? Object.values(errors).flat().join(' ')
            : e.response?.data?.message || 'Erro ao solicitar saque.';
    } finally {
        paying.value = false;
    }
}

selectWallet('pix');
</script>

<template>
    <div class="fin-dash space-y-4">
        <!-- Seletor de carteira -->
        <div class="flex flex-wrap items-center gap-3">
            <div class="ep-tabs max-w-full overflow-x-auto no-scrollbar" role="group" aria-label="Carteiras">
                <button
                    v-for="key in walletKeys"
                    :key="key"
                    type="button"
                    class="ep-tab"
                    :class="selectedWallet === key ? 'ep-tab--active' : ''"
                    @click="selectWallet(key)"
                >
                    <component
                        :is="walletIcons[key]"
                        class="h-4 w-4"
                        :class="selectedWallet === key ? 'text-[var(--ep-accent)]' : 'text-[var(--ep-text-4)]'"
                        :stroke-width="1.75"
                    />
                    {{ walletLabels[key] || key }}
                </button>
            </div>
        </div>

        <!-- Herói (saldo disponível) + KPIs -->
        <div class="grid gap-4 lg:grid-cols-12">
            <section class="panel-card ep-glow-card flex flex-col p-6 lg:col-span-5" aria-labelledby="fin-disponivel">
                <div class="flex items-center justify-between gap-3">
                    <h2 id="fin-disponivel" class="text-[13px] font-medium text-[var(--ep-text-2)]">Saldo disponível</h2>
                    <span class="ep-chip">
                        <span class="h-1.5 w-1.5 rounded-full bg-[var(--ep-pos)]" aria-hidden="true" />
                        Carteira {{ walletLabels[selectedWallet] }}
                    </span>
                </div>

                <MoneyAmount :value="metrics.available" size="hero" class="mt-5 block" />
                <p class="mt-3 text-[12.5px] tabular-nums text-[var(--ep-text-3)]">
                    Mínimo por saque · {{ formatBRL(minPayoutReais) }}
                </p>

                <dl class="mt-6 border-t border-[var(--ep-line)] pt-4 lg:mt-auto">
                    <dt class="text-[11.5px] text-[var(--ep-text-3)]">Destino dos saques</dt>
                    <dd class="mt-1 truncate text-[13px] font-medium text-[var(--ep-text)]">
                        {{ withdrawPixDestination || 'Nenhuma chave PIX cadastrada' }}
                    </dd>
                </dl>
            </section>

            <div class="grid gap-4 sm:grid-cols-3 lg:col-span-7">
                <div class="panel-card ep-kpi">
                    <div class="flex items-start justify-between gap-3">
                        <span class="ep-kpi__label">A liberar</span>
                        <span class="ep-kpi__icon" aria-hidden="true"><TrendingUp class="h-4 w-4" :stroke-width="1.75" /></span>
                    </div>
                    <div class="mt-auto pt-6">
                        <MoneyAmount :value="metrics.pending" size="lg" class="block" />
                        <p class="ep-kpi__meta mt-1.5">Aguardando prazo de liquidação</p>
                    </div>
                </div>

                <div class="panel-card ep-kpi">
                    <div class="flex items-start justify-between gap-3">
                        <span class="ep-kpi__label">Saídas (saques)</span>
                        <span class="ep-kpi__icon" aria-hidden="true"><TrendingDown class="h-4 w-4" :stroke-width="1.75" /></span>
                    </div>
                    <div class="mt-auto pt-6">
                        <MoneyAmount :value="saidasPeriodo" size="lg" class="block" />
                        <p class="ep-kpi__meta mt-1.5">Histórico de movimentações</p>
                    </div>
                </div>

                <div class="panel-card ep-kpi">
                    <div class="flex items-start justify-between gap-3">
                        <span class="ep-kpi__label">Saques pendentes</span>
                        <span class="ep-kpi__icon" aria-hidden="true"><Clock class="h-4 w-4" :stroke-width="1.75" /></span>
                    </div>
                    <div class="mt-auto pt-6">
                        <MoneyAmount :value="metrics.reserved" size="lg" class="block" />
                        <p class="ep-kpi__meta mt-1.5 flex items-center gap-1.5" :class="metrics.reserved > 0 ? '!text-[var(--ep-warn)]' : ''">
                            <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                            {{ metrics.reserved > 0 ? 'Processando' : 'Nenhum em andamento' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="partnerSummary" class="grid gap-4 sm:grid-cols-2">
            <div class="panel-card ep-kpi">
                <span class="ep-kpi__label">Comissões parceiros (a pagar)</span>
                <MoneyAmount :value="partnerSummary.partner_commissions_pending" size="lg" class="block" />
            </div>
            <div class="panel-card ep-kpi">
                <span class="ep-kpi__label">Comissões parceiros (pagas)</span>
                <MoneyAmount :value="partnerSummary.partner_commissions_paid" size="lg" class="block" />
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <!-- Coluna principal -->
            <div class="min-w-0 space-y-4 lg:col-span-2">
                <section class="panel-card p-5" aria-labelledby="fin-visao">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 id="fin-visao" class="ep-section-title">Visão geral</h2>
                        <span class="text-[12px] tabular-nums text-[var(--ep-text-4)]">
                            Comissões — últimos 14 dias ·
                            <span class="font-medium text-[var(--ep-text-2)]">{{ formatBRL(chartBars.reduce((s, b) => s + b.value, 0)) }}</span>
                        </span>
                    </div>
                    <div class="ep-bars mt-6 grid-cols-[repeat(14,minmax(0,1fr))] gap-1 sm:gap-2" role="img" aria-label="Comissões por dia nos últimos 14 dias">
                        <div
                            v-for="bar in chartBars"
                            :key="bar.date"
                            class="ep-bar max-sm:even:[&>.ep-bar__label]:invisible"
                            :class="{ 'ep-bar--today': bar.date === chartBars[chartBars.length - 1].date }"
                            :title="formatBRL(bar.value)"
                        >
                            <div class="ep-bar__track h-[120px] w-2.5"><div class="ep-bar__fill" :style="{ height: `${bar.height}%` }" /></div>
                            <span class="ep-bar__label">{{ bar.label }}</span>
                        </div>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-4 border-t border-[var(--ep-line)] pt-3 text-[12px] text-[var(--ep-text-3)]">
                        <span class="flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-[var(--ep-accent)]" aria-hidden="true" />
                            Entradas (comissões)
                        </span>
                    </div>
                </section>

                <section class="panel-card ep-data overflow-hidden" aria-labelledby="fin-extrato">
                    <div class="flex items-center justify-between gap-3 px-5 pb-3 pt-5">
                        <h2 id="fin-extrato" class="ep-section-title">Movimentações recentes</h2>
                        <span class="text-[12px] text-[var(--ep-text-4)]">Extrato</span>
                    </div>
                    <div v-if="recentMovements.length" class="overflow-x-auto">
                        <table class="ep-table min-w-[480px]">
                            <thead>
                                <tr>
                                    <th>Movimentação</th>
                                    <th>Status</th>
                                    <th class="ep-num">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in recentMovements" :key="item.id">
                                    <td>
                                        <div class="flex min-w-0 items-center gap-3">
                                            <span
                                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-[10px] border border-[var(--ep-line)] bg-[var(--ep-card-2)]"
                                                :class="item.type === 'credit' ? 'text-[var(--ep-pos)]' : 'text-[var(--ep-text-3)]'"
                                                aria-hidden="true"
                                            >
                                                <ArrowDownCircle v-if="item.type === 'credit'" class="h-4 w-4" :stroke-width="1.75" />
                                                <ArrowUpCircle v-else class="h-4 w-4" :stroke-width="1.75" />
                                            </span>
                                            <div class="min-w-0">
                                                <p class="truncate text-[13px] font-medium text-[var(--ep-text)]">{{ item.label }}</p>
                                                <p class="text-[12px] tabular-nums text-[var(--ep-text-4)]">{{ formatDate(item.date) }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span
                                            v-if="item.status"
                                            class="ep-chip"
                                            :class="{
                                                'ep-chip--pos': item.status === 'completed' || item.status === 'paid',
                                                'ep-chip--warn': ['pending', 'pending_approval', 'awaiting_payout', 'processing'].includes(item.status),
                                                'ep-chip--neg': item.status === 'failed' || item.status === 'cancelled',
                                            }"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                                            {{ statusLabels[item.status] || item.status }}
                                        </span>
                                    </td>
                                    <td
                                        class="ep-num font-semibold"
                                        :class="item.type === 'credit' ? 'text-[var(--ep-pos)]' : 'text-[var(--ep-text)]'"
                                    >
                                        {{ item.type === 'credit' ? '+' : '−' }}{{ formatBRL(item.amount) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="ep-empty">
                        <p class="ep-empty__title">Nenhuma movimentação ainda.</p>
                        <p class="ep-empty__text">Créditos de vendas e saques aparecem neste extrato assim que acontecerem.</p>
                    </div>
                </section>
            </div>

            <!-- Sidebar ações -->
            <div class="space-y-4">
                <section class="panel-card p-5" aria-labelledby="fin-acoes">
                    <h2 id="fin-acoes" class="ep-section-title">Ações rápidas</h2>
                    <p
                        v-if="!canManage"
                        class="mt-3 text-[12.5px] leading-relaxed text-[var(--ep-text-3)]"
                    >
                        Você pode consultar saldos. Sacar e alterar PIX exigem permissão de gestão financeira.
                    </p>
                    <div v-if="canManage" class="mt-4 space-y-2">
                        <button
                            type="button"
                            class="ep-btn h-11 w-full"
                            :disabled="!cajupayConnected || maxAvailable < minPayoutReais"
                            @click="showWithdrawForm = !showWithdrawForm"
                        >
                            <ArrowDownToLine class="h-4 w-4" :stroke-width="1.75" />
                            Sacar
                        </button>
                        <button
                            type="button"
                            class="ep-btn-secondary w-full"
                            @click="showPixForm = !showPixForm"
                        >
                            <QrCode class="h-4 w-4" :stroke-width="1.75" />
                            {{ pixKey ? 'Alterar chave PIX' : 'Cadastrar chave PIX' }}
                        </button>
                    </div>

                    <div v-if="canManage && showWithdrawForm && cajupayConnected" class="mt-4 space-y-3 border-t border-[var(--ep-line)] pt-4">
                        <p class="text-[12px] tabular-nums text-[var(--ep-text-3)]">
                            Sacar de {{ walletLabels[selectedWallet] }} · <span class="font-medium text-[var(--ep-text)]">{{ formatBRL(maxAvailable) }}</span> disponível
                        </p>
                        <div
                            v-if="withdrawPixDestination"
                            class="rounded-xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] px-3 py-2.5"
                        >
                            <p class="text-[11.5px] font-medium text-[var(--ep-text-3)]">Transferência PIX para</p>
                            <p class="mt-1 break-all font-mono text-[13px] text-[var(--ep-text)]">
                                {{ withdrawPixDestination }}
                            </p>
                        </div>
                        <p
                            v-else
                            class="flex items-start gap-2 rounded-xl border border-[color-mix(in_oklab,var(--ep-warn)_30%,transparent)] bg-[var(--ep-warn-bg)] px-3 py-2 text-[12px] text-[var(--ep-warn)]"
                        >
                            <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-current" aria-hidden="true" />
                            Cadastre uma chave PIX abaixo antes de confirmar o saque.
                        </p>
                        <label class="flex items-center gap-2 text-[13px] text-[var(--ep-text-2)]">
                            <input v-model="withdrawAll" type="checkbox" class="h-4 w-4 rounded border-[var(--ep-input-border)] accent-[var(--ep-accent)]" />
                            Sacar tudo
                        </label>
                        <input
                            v-if="!withdrawAll"
                            v-model.number="payoutAmount"
                            type="number"
                            step="0.01"
                            :min="minPayoutReais"
                            :max="maxAvailable"
                            aria-label="Valor do saque"
                            class="ep-input tabular-nums"
                        />
                        <Button
                            type="button"
                            class="w-full"
                            :disabled="paying || maxAvailable < minPayoutReais || !withdrawPixDestination"
                            @click="requestPayout"
                        >
                            {{ paying ? 'Processando…' : 'Confirmar saque' }}
                        </Button>
                    </div>

                    <div v-if="canManage && showPixForm" class="mt-4 space-y-3 border-t border-[var(--ep-line)] pt-4">
                        <input
                            v-model="pixForm.pix_key"
                            type="text"
                            placeholder="Chave PIX"
                            aria-label="Chave PIX"
                            class="ep-input"
                        />
                        <div class="relative">
                            <select
                                v-model="pixForm.pix_key_type"
                                aria-label="Tipo de chave PIX"
                                class="ep-input appearance-none pr-9"
                            >
                                <option value="email">E-mail</option>
                                <option value="cpf">CPF</option>
                                <option value="cnpj">CNPJ</option>
                                <option value="phone">Telefone</option>
                                <option value="random">Aleatória (EVP)</option>
                            </select>
                            <ChevronDown class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--ep-text-4)]" :stroke-width="1.75" />
                        </div>
                        <input
                            v-if="needsOwnerDocument"
                            v-model="pixForm.pix_owner_document"
                            type="text"
                            placeholder="CPF/CNPJ do titular"
                            aria-label="CPF/CNPJ do titular"
                            class="ep-input"
                        />
                        <Button type="button" class="w-full" :disabled="saving" @click="savePix">
                            {{ saving ? 'Salvando…' : 'Salvar' }}
                        </Button>
                    </div>
                </section>

                <section class="panel-card p-5" aria-labelledby="fin-resumo">
                    <h2 id="fin-resumo" class="ep-section-title">Resumo da carteira</h2>
                    <div class="mt-4 flex h-2 w-full gap-[3px] overflow-hidden rounded-full bg-[var(--ep-active)]" aria-hidden="true">
                        <span class="h-full rounded-full bg-[var(--ep-pos)]" :style="{ width: `${(metrics.available / Math.max(0.01, metrics.available + metrics.pending + metrics.reserved)) * 100}%` }" />
                        <span class="h-full rounded-full bg-[var(--ep-accent)]" :style="{ width: `${(metrics.pending / Math.max(0.01, metrics.available + metrics.pending + metrics.reserved)) * 100}%` }" />
                        <span class="h-full rounded-full bg-[var(--ep-warn)]" :style="{ width: `${(metrics.reserved / Math.max(0.01, metrics.available + metrics.pending + metrics.reserved)) * 100}%` }" />
                    </div>
                    <ul class="mt-4 space-y-2.5 text-[13px]">
                        <li class="flex items-center gap-2.5">
                            <span class="h-2 w-2 shrink-0 rounded-full bg-[var(--ep-pos)]" aria-hidden="true" />
                            <span class="flex-1 text-[var(--ep-text-3)]">Disponível</span>
                            <span class="font-medium tabular-nums text-[var(--ep-text)]">{{ formatBRL(metrics.available) }}</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <span class="h-2 w-2 shrink-0 rounded-full bg-[var(--ep-accent)]" aria-hidden="true" />
                            <span class="flex-1 text-[var(--ep-text-3)]">A liberar</span>
                            <span class="font-medium tabular-nums text-[var(--ep-text)]">{{ formatBRL(metrics.pending) }}</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <span class="h-2 w-2 shrink-0 rounded-full bg-[var(--ep-warn)]" aria-hidden="true" />
                            <span class="flex-1 text-[var(--ep-text-3)]">Em saque</span>
                            <span class="font-medium tabular-nums text-[var(--ep-text)]">{{ formatBRL(metrics.reserved) }}</span>
                        </li>
                        <li class="flex items-center justify-between gap-3 border-t border-[var(--ep-line)] pt-3">
                            <span class="font-medium text-[var(--ep-text-2)]">Total (todas carteiras)</span>
                            <span class="font-semibold tabular-nums text-[var(--ep-text)]">{{ formatBRL(metrics.totalAvailable) }}</span>
                        </li>
                    </ul>
                </section>

                <div v-if="metrics.paidOut > 0" class="panel-card ep-kpi">
                    <div class="flex items-start justify-between gap-3">
                        <span class="ep-kpi__label">Já recebido nesta carteira</span>
                        <span class="ep-kpi__icon" aria-hidden="true"><Lock class="h-4 w-4" :stroke-width="1.75" /></span>
                    </div>
                    <MoneyAmount :value="metrics.paidOut" size="lg" class="block" />
                </div>
            </div>
        </div>

        <p
            v-if="payoutMsg"
            class="flex items-start gap-2 rounded-xl border px-4 py-2.5 text-[13px]"
            :class="payoutMsg.includes('sucesso') || payoutMsg.includes('salva')
                ? 'border-[color-mix(in_oklab,var(--ep-pos)_30%,transparent)] bg-[var(--ep-pos-bg)] text-[var(--ep-pos)]'
                : 'border-[color-mix(in_oklab,var(--ep-neg)_30%,transparent)] bg-[var(--ep-neg-bg)] text-[var(--ep-neg)]'"
            role="status"
        >
            <span class="mt-[7px] h-1.5 w-1.5 shrink-0 rounded-full bg-current" aria-hidden="true" />
            {{ payoutMsg }}
        </p>

        <section v-if="showCommissionsTable && commissions.length" class="panel-card ep-data overflow-hidden" aria-labelledby="fin-comissoes">
            <div class="px-5 pb-3 pt-5">
                <h2 id="fin-comissoes" class="ep-section-title">Comissões</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="ep-table min-w-[560px]">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Carteira</th>
                            <th class="ep-num">Valor</th>
                            <th>Status</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="c in commissions" :key="c.id">
                            <td class="font-medium">{{ c.product_name || '—' }}</td>
                            <td class="text-[var(--ep-text-3)]">{{ walletLabels[c.wallet_bucket] || c.wallet_bucket }}</td>
                            <td class="ep-num font-medium">{{ formatBRL(c.commission_amount) }}</td>
                            <td>
                                <span
                                    class="ep-chip"
                                    :class="{
                                        'ep-chip--pos': ['paid', 'available', 'completed', 'settled_externally'].includes(c.status),
                                        'ep-chip--warn': ['pending', 'pending_approval', 'awaiting_payout', 'processing'].includes(c.status),
                                        'ep-chip--accent': c.status === 'reserved',
                                        'ep-chip--neg': c.status === 'failed' || c.status === 'cancelled',
                                    }"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                                    {{ statusLabels[c.status] || c.status }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap text-[var(--ep-text-3)]">{{ formatDate(c.created_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
