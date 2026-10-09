<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import Button from '@/components/ui/Button.vue';
import ProductPartnersTable from '@/components/produtos/ProductPartnersTable.vue';
import { Mail, Users, Search, Info, Wallet, Zap, Check } from 'lucide-vue-next';

const props = defineProps({
    productId: { type: String, required: true },
});

const loading = ref(true);
const saving = ref(false);
const savingSettings = ref(false);
const mode = ref('member');
const coproducers = ref([]);
const candidates = ref([]);
const candidateSearch = ref('');
const selectedUserId = ref(null);
const message = ref('');

const splitPayoutEnabled = ref(false);
const cajupayConnected = ref(false);

const editingCoproducer = ref(null);
const editForm = ref({
    commission_percent: 10,
    payout_method: 'internal',
    cajupay_split_id: '',
    commission_on_producer_sales: true,
    commission_on_affiliate_sales: true,
});

const defaultCommissionFields = () => ({
    commission_percent: 10,
    duration_days: null,
    commission_on_producer_sales: true,
    commission_on_affiliate_sales: true,
    settlement_days_pix: null,
    settlement_days_card: null,
    settlement_days_boleto: null,
    payout_method: 'internal',
    cajupay_split_id: '',
});

const commissionForm = ref(defaultCommissionFields());
const inviteForm = ref({ email: '', ...defaultCommissionFields() });

const canUseSplitPayout = computed(
    () => splitPayoutEnabled.value && cajupayConnected.value
);

let searchTimer = null;

function payoutPayload(form) {
    const base = { ...form };
    if (form.payout_method !== 'cajupay_split') {
        base.payout_method = 'internal';
        base.cajupay_split_id = null;
    } else {
        base.cajupay_split_id = (form.cajupay_split_id || '').trim() || null;
    }
    return base;
}

async function load() {
    loading.value = true;
    try {
        const { data } = await axios.get(`/produtos/${props.productId}/coproducers`);
        coproducers.value = data.coproducers ?? [];
        splitPayoutEnabled.value = !!data.cajupay_split_payout_enabled;
        cajupayConnected.value = !!data.cajupay_connected;
    } finally {
        loading.value = false;
    }
}

async function toggleSplitPayout() {
    if (!cajupayConnected.value && !splitPayoutEnabled.value) {
        message.value = 'Conecte o CajuPay em Integrações antes de ativar split.';
        return;
    }
    savingSettings.value = true;
    message.value = '';
    try {
        const { data } = await axios.patch(`/produtos/${props.productId}/coproduction-settings`, {
            cajupay_split_payout_enabled: !splitPayoutEnabled.value,
        });
        splitPayoutEnabled.value = !!data.cajupay_split_payout_enabled;
        message.value = splitPayoutEnabled.value
            ? 'Repasse via split ativado neste produto.'
            : 'Repasse via split desativado. Novos co-produtores usarão conta única.';
    } catch (e) {
        message.value = e.response?.data?.message || 'Erro ao salvar configuração.';
    } finally {
        savingSettings.value = false;
    }
}

async function loadCandidates() {
    try {
        const { data } = await axios.get(`/produtos/${props.productId}/coproducers/candidates`, {
            params: { q: candidateSearch.value.trim() || undefined },
        });
        candidates.value = data.candidates ?? [];
    } catch {
        candidates.value = [];
    }
}

function onCandidateSearch() {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(loadCandidates, 350);
}

function selectCandidate(c) {
    selectedUserId.value = c.id;
}

const coproducerStatusLabels = {
    active: 'Ativo',
    pending: 'Convite pendente',
    revoked: 'Revogado',
    expired: 'Expirado',
};

const coproducerRows = computed(() =>
    coproducers.value.map((c) => ({
        id: c.id,
        created_at: c.created_at,
        name: c.user?.name ?? null,
        email: c.user?.email ?? c.email ?? null,
        product_name: c.product_name,
        commission_percent: c.commission_percent,
        status: c.status,
    }))
);

function openEdit(c) {
    editingCoproducer.value = c;
    editForm.value = {
        commission_percent: c.commission_percent,
        payout_method: c.payout_method || 'internal',
        cajupay_split_id: c.cajupay_split_id || '',
        commission_on_producer_sales: c.commission_on_producer_sales,
        commission_on_affiliate_sales: c.commission_on_affiliate_sales,
    };
}

function closeEdit() {
    editingCoproducer.value = null;
}

async function saveEdit() {
    if (!editingCoproducer.value) return;
    saving.value = true;
    message.value = '';
    try {
        await axios.patch(
            `/produtos/${props.productId}/coproducers/${editingCoproducer.value.id}`,
            payoutPayload(editForm.value)
        );
        message.value = 'Co-produtor atualizado.';
        closeEdit();
        await load();
    } catch (e) {
        const err = e.response?.data?.errors;
        message.value =
            e.response?.data?.message
            || err?.cajupay_split_id?.[0]
            || err?.payout_method?.[0]
            || 'Erro ao salvar.';
    } finally {
        saving.value = false;
    }
}

async function assignMember() {
    if (!selectedUserId.value) {
        message.value = 'Selecione um membro da lista.';
        return;
    }
    saving.value = true;
    message.value = '';
    try {
        await axios.post(`/produtos/${props.productId}/coproducers/assign`, {
            user_id: selectedUserId.value,
            ...payoutPayload(commissionForm.value),
        });
        message.value = 'Co-produtor adicionado.';
        selectedUserId.value = null;
        candidateSearch.value = '';
        await load();
        await loadCandidates();
    } catch (e) {
        const err = e.response?.data?.errors;
        message.value =
            e.response?.data?.message
            || err?.user_id?.[0]
            || err?.cajupay_split_id?.[0]
            || 'Erro ao adicionar.';
    } finally {
        saving.value = false;
    }
}

async function sendInvite() {
    saving.value = true;
    message.value = '';
    try {
        const { data } = await axios.post(`/produtos/${props.productId}/coproducers/invite`, {
            email: inviteForm.value.email,
            ...payoutPayload(inviteForm.value),
        });
        message.value = data.warning || 'Convite enviado.';
        inviteForm.value.email = '';
        await load();
    } catch (e) {
        const err = e.response?.data?.errors;
        message.value =
            e.response?.data?.message
            || err?.cajupay_split_id?.[0]
            || 'Erro ao enviar convite.';
    } finally {
        saving.value = false;
    }
}

async function revoke(id) {
    if (!confirm('Revogar este co-produtor?')) return;
    await axios.post(`/produtos/${props.productId}/coproducers/${id}/revoke`);
    message.value = 'Co-produtor revogado.';
    await load();
    await loadCandidates();
}

async function resend(id) {
    await axios.post(`/produtos/${props.productId}/coproducers/${id}/resend`);
    message.value = 'Convite reenviado.';
}

onMounted(async () => {
    await load();
    await loadCandidates();
});
</script>

<template>
    <div class="space-y-4">
        <section class="panel-card p-6" aria-labelledby="coproducao-titulo">
            <div>
                <h2 id="coproducao-titulo" class="text-[15px] font-semibold tracking-[-0.015em] text-[var(--ep-text)]">Co-produção</h2>
                <p class="mt-1 max-w-[640px] text-[12.5px] leading-relaxed text-[var(--ep-text-3)]">
                    Comissão sobre o valor líquido (após taxas do gateway). Adicione alguém da sua equipe ou envie convite por e-mail.
                </p>
            </div>

            <div
                class="mt-6 rounded-2xl border p-4 transition-colors duration-150"
                :class="splitPayoutEnabled
                    ? 'border-[color-mix(in_oklab,var(--ep-accent)_40%,transparent)] bg-[color-mix(in_oklab,var(--ep-accent)_9%,transparent)]'
                    : 'border-[var(--ep-line)] bg-[var(--ep-card-2)]'"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex min-w-0 items-start gap-3">
                        <span class="ep-kpi__icon !h-8 !w-8 shrink-0" aria-hidden="true">
                            <Zap class="h-4 w-4" stroke-width="1.75" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-[13.5px] font-medium text-[var(--ep-text)]">
                                Repasse automático via split CajuPay
                            </p>
                            <p class="mt-0.5 text-[12px] text-[var(--ep-text-4)]">
                                Permite que parte da venda vá direto para a conta CajuPay do co-produtor (PIX com split).
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        role="switch"
                        :aria-checked="splitPayoutEnabled"
                        :disabled="savingSettings || (!cajupayConnected && !splitPayoutEnabled)"
                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border border-[var(--ep-line-strong)] transition-[background-color,box-shadow] duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--ep-accent)] disabled:cursor-not-allowed disabled:opacity-50"
                        :class="splitPayoutEnabled ? 'bg-[var(--ep-accent)]' : 'bg-[var(--ep-active)]'"
                        @click="toggleSplitPayout"
                    >
                        <span
                            class="pointer-events-none mt-px inline-block h-5 w-5 transform rounded-full bg-white shadow-[0_1px_3px_rgba(0,0,0,0.35)] transition-transform duration-200 ease-[cubic-bezier(0.23,1,0.32,1)]"
                            :class="splitPayoutEnabled ? 'translate-x-[21px]' : 'translate-x-px'"
                        />
                    </button>
                </div>
                <p v-if="!cajupayConnected" class="mt-3 flex items-center gap-2 border-t border-[var(--ep-line)] pt-3 text-[12px] text-[var(--ep-warn)]">
                    <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-current" aria-hidden="true" />
                    Conecte o gateway CajuPay em Integrações para usar split.
                </p>
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-2">
                <div class="rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4">
                    <div class="flex items-center gap-2 text-[13px] font-medium text-[var(--ep-text)]">
                        <Wallet class="h-4 w-4 text-[var(--ep-text-3)]" stroke-width="1.75" aria-hidden="true" />
                        Conta única (padrão)
                    </div>
                    <p class="mt-2 text-[12px] leading-relaxed text-[var(--ep-text-3)] [&_strong]:font-medium [&_strong]:text-[var(--ep-text-2)]">
                        Todo o pagamento cai na <strong>sua</strong> conta CajuPay. O Getfy registra a comissão do co-produtor;
                        ele acompanha vendas aqui e saca pelo <strong>Financeiro do parceiro</strong> quando o saldo liberar.
                    </p>
                </div>
                <div class="rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4">
                    <div class="flex items-center gap-2 text-[13px] font-medium text-[var(--ep-text)]">
                        <Zap class="h-4 w-4 text-[var(--ep-accent)]" stroke-width="1.75" aria-hidden="true" />
                        Split direto na CajuPay
                    </div>
                    <p class="mt-2 text-[12px] leading-relaxed text-[var(--ep-text-3)] [&_strong]:font-medium [&_strong]:text-[var(--ep-text-2)]">
                        Cada co-produtor pode ter <strong>seu próprio UUID</strong> (cadastre um por pessoa na lista abaixo).
                        Na venda PIX, a CajuPay reparte o líquido para a conta dele. O percentual do split é configurado
                        <strong>no painel CajuPay</strong> (o Getfy só guarda o ID).
                    </p>
                </div>
            </div>

            <div class="mt-3 flex gap-3 rounded-2xl border border-[color-mix(in_oklab,var(--ep-accent)_26%,transparent)] bg-[color-mix(in_oklab,var(--ep-accent)_7%,transparent)] px-4 py-3 text-[12px] leading-relaxed text-[var(--ep-text-3)] [&_strong]:font-medium [&_strong]:text-[var(--ep-text-2)]">
                <Info class="mt-0.5 h-4 w-4 shrink-0 text-[var(--ep-accent)]" stroke-width="1.75" aria-hidden="true" />
                <p>
                    A CajuPay aceita <strong>um split por cobrança PIX</strong>. Você pode ter vários co-produtores com split
                    (cada um com seu UUID); em cada venda, o split na cobrança vai para <strong>um</strong> co-produtor
                    (o de maior % naquela venda; os demais co-produtores com split recebem pelo Financeiro do parceiro).
                    <strong>Venda com afiliado:</strong> o afiliado continua na conta única; o co-produtor com split
                    <strong>pode</strong> receber repasse direto na CajuPay normalmente.
                </p>
            </div>
        </section>

        <section class="panel-card p-6" aria-labelledby="coproducao-adicionar">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 id="coproducao-adicionar" class="ep-section-title">Adicionar co-produtor</h3>
                <div class="ep-tabs" role="tablist">
                    <button
                        type="button"
                        role="tab"
                        :class="['ep-tab', mode === 'member' ? 'ep-tab--active' : 'border border-transparent']"
                        @click="mode = 'member'"
                    >
                        <Users class="h-4 w-4" stroke-width="1.75" aria-hidden="true" />
                        Equipe / conta
                    </button>
                    <button
                        type="button"
                        role="tab"
                        :class="['ep-tab', mode === 'invite' ? 'ep-tab--active' : 'border border-transparent']"
                        @click="mode = 'invite'"
                    >
                        <Mail class="h-4 w-4" stroke-width="1.75" aria-hidden="true" />
                        Convite por e-mail
                    </button>
                </div>
            </div>

            <div class="mt-6 grid gap-x-4 gap-y-5 md:grid-cols-2">
                <div>
                    <label class="ep-label">Comissão (%)</label>
                    <div class="relative">
                        <input
                            v-model.number="commissionForm.commission_percent"
                            type="number"
                            min="0"
                            max="100"
                            step="0.01"
                            class="ep-input pr-9 tabular-nums"
                        />
                        <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[12.5px] text-[var(--ep-text-4)]" aria-hidden="true">%</span>
                    </div>
                </div>
                <div>
                    <label class="ep-label">Duração</label>
                    <select
                        v-model="commissionForm.duration_days"
                        class="ep-input cursor-pointer"
                    >
                        <option :value="null">Indeterminado</option>
                        <option :value="30">30 dias</option>
                        <option :value="60">60 dias</option>
                        <option :value="90">90 dias</option>
                        <option :value="120">120 dias</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="ep-label">Como o co-produtor recebe?</label>
                    <select
                        v-model="commissionForm.payout_method"
                        class="ep-input cursor-pointer disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="!canUseSplitPayout && commissionForm.payout_method !== 'cajupay_split'"
                    >
                        <option value="internal">Conta única (Getfy / sua CajuPay)</option>
                        <option value="cajupay_split" :disabled="!canUseSplitPayout">Split direto na CajuPay</option>
                    </select>
                </div>
                <div v-if="commissionForm.payout_method === 'cajupay_split'" class="md:col-span-2">
                    <label class="ep-label">ID do split (UUID) — painel CajuPay</label>
                    <input
                        v-model="commissionForm.cajupay_split_id"
                        type="text"
                        placeholder="550e8400-e29b-41d4-a716-446655440000"
                        class="ep-input font-mono !text-[13px]"
                    />
                    <p class="ep-help">
                        O co-produtor cria o split na conta CajuPay dele e envia este código para você colar aqui.
                    </p>
                </div>
                <div class="overflow-hidden rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] md:col-span-2">
                    <p class="px-4 pt-3 text-[12px] text-[var(--ep-text-4)]">Comissão vale para</p>
                    <label class="flex cursor-pointer items-center gap-3 px-4 py-2.5 text-[13px] text-[var(--ep-text)] transition-colors duration-150 hover:bg-[var(--ep-hover)]">
                        <input v-model="commissionForm.commission_on_producer_sales" type="checkbox" class="h-4 w-4 shrink-0 cursor-pointer accent-[var(--ep-accent)]" />
                        Vendas do produtor (checkout direto)
                    </label>
                    <label class="flex cursor-pointer items-center gap-3 px-4 pb-3 pt-2.5 text-[13px] text-[var(--ep-text)] transition-colors duration-150 hover:bg-[var(--ep-hover)]">
                        <input v-model="commissionForm.commission_on_affiliate_sales" type="checkbox" class="h-4 w-4 shrink-0 cursor-pointer accent-[var(--ep-accent)]" />
                        Vendas de afiliados
                    </label>
                </div>
            </div>

            <div v-if="mode === 'member'" class="mt-6 space-y-3 border-t border-[var(--ep-line)] pt-6">
                <div class="relative">
                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--ep-text-4)]" stroke-width="1.75" aria-hidden="true" />
                    <input
                        v-model="candidateSearch"
                        type="search"
                        placeholder="Buscar por nome ou e-mail…"
                        class="ep-input pl-9"
                        @input="onCandidateSearch"
                    />
                </div>
                <p class="text-[12px] text-[var(--ep-text-4)]">
                    Lista infoprodutores e membros da equipe desta conta que ainda não são co-produtores deste produto.
                </p>
                <ul v-if="candidates.length" class="max-h-64 space-y-1 overflow-y-auto rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-1.5">
                    <li
                        v-for="c in candidates"
                        :key="c.id"
                        class="flex cursor-pointer items-center gap-3 rounded-xl border px-3 py-2.5 transition-colors duration-150"
                        :class="selectedUserId === c.id
                            ? 'border-[color-mix(in_oklab,var(--ep-accent)_45%,transparent)] bg-[color-mix(in_oklab,var(--ep-accent)_12%,transparent)]'
                            : 'border-transparent hover:bg-[var(--ep-hover)]'"
                        @click="selectCandidate(c)"
                    >
                        <span v-avatar="c.name || c.email" class="ep-avatar shrink-0 !h-8 !w-8" aria-hidden="true">
                            {{ String(c.name || c.email || '?').trim().split(/\s+/).filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase() || '?' }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[13px] font-medium text-[var(--ep-text)]">{{ c.name }}</p>
                            <p class="truncate text-[12px] text-[var(--ep-text-3)]">{{ c.email }}</p>
                        </div>
                        <span class="ep-chip shrink-0">
                            {{ c.role_label }}
                        </span>
                        <Check
                            class="h-4 w-4 shrink-0 text-[var(--ep-accent)] transition-opacity duration-150"
                            :class="selectedUserId === c.id ? 'opacity-100' : 'opacity-0'"
                            stroke-width="2"
                            aria-hidden="true"
                        />
                    </li>
                </ul>
                <div v-else class="ep-empty rounded-2xl border border-dashed border-[var(--ep-line-strong)] !py-8">
                    <p class="ep-empty__title">Nenhum membro disponível para adicionar.</p>
                    <p class="ep-empty__text">Use a aba “Convite por e-mail” para chamar alguém de fora da conta.</p>
                </div>
                <div class="flex justify-end pt-1">
                    <Button type="button" :disabled="saving || !selectedUserId" @click="assignMember">
                        {{ saving ? 'Adicionando…' : 'Adicionar co-produtor' }}
                    </Button>
                </div>
            </div>

            <form v-else class="mt-6 space-y-5 border-t border-[var(--ep-line)] pt-6" @submit.prevent="sendInvite">
                <div class="grid gap-x-4 gap-y-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="ep-label">E-mail do convidado</label>
                        <input
                            v-model="inviteForm.email"
                            type="email"
                            required
                            placeholder="pessoa@email.com"
                            class="ep-input"
                        />
                    </div>
                    <div>
                        <label class="ep-label">Comissão (%)</label>
                        <div class="relative">
                            <input
                                v-model.number="inviteForm.commission_percent"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                class="ep-input pr-9 tabular-nums"
                            />
                            <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[12.5px] text-[var(--ep-text-4)]" aria-hidden="true">%</span>
                        </div>
                    </div>
                    <div>
                        <label class="ep-label">Como o co-produtor recebe?</label>
                        <select
                            v-model="inviteForm.payout_method"
                            class="ep-input cursor-pointer"
                        >
                            <option value="internal">Conta única (Getfy)</option>
                            <option value="cajupay_split" :disabled="!canUseSplitPayout">Split direto na CajuPay</option>
                        </select>
                    </div>
                    <div v-if="inviteForm.payout_method === 'cajupay_split'" class="md:col-span-2">
                        <label class="ep-label">ID do split (UUID)</label>
                        <input
                            v-model="inviteForm.cajupay_split_id"
                            type="text"
                            required
                            placeholder="UUID do painel CajuPay"
                            class="ep-input font-mono !text-[13px]"
                        />
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-[12px] text-[var(--ep-text-4)]">
                        A pessoa receberá um link para aceitar o convite (mesmo estilo da página de afiliados).
                    </p>
                    <Button type="submit" :disabled="saving">{{ saving ? 'Enviando…' : 'Enviar convite por e-mail' }}</Button>
                </div>
            </form>

            <p v-if="message" class="mt-5 flex items-center gap-2 rounded-xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] px-3.5 py-2.5 text-[12.5px] text-[var(--ep-text-2)]" role="status">
                <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[var(--ep-accent)]" aria-hidden="true" />
                {{ message }}
            </p>
        </section>

        <Teleport to="body">
            <div
                v-if="editingCoproducer"
                class="ep-scrim fixed inset-0 z-[100000] flex items-center justify-center p-4"
                @click.self="closeEdit"
            >
                <div class="ep-modal w-full max-w-md p-6" role="dialog" aria-modal="true" aria-labelledby="coproducao-editar">
                    <h3 id="coproducao-editar" class="text-[15px] font-semibold tracking-[-0.015em] text-[var(--ep-text)]">Editar repasse</h3>
                    <p class="mt-1 text-[12.5px] text-[var(--ep-text-3)]">{{ editingCoproducer.user?.name || editingCoproducer.email }}</p>
                    <div class="mt-5 space-y-4">
                        <div>
                            <label class="ep-label">Comissão (%)</label>
                            <div class="relative">
                                <input
                                    v-model.number="editForm.commission_percent"
                                    type="number"
                                    min="0"
                                    max="100"
                                    class="ep-input pr-9 tabular-nums"
                                />
                                <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[12.5px] text-[var(--ep-text-4)]" aria-hidden="true">%</span>
                            </div>
                        </div>
                        <div>
                            <label class="ep-label">Forma de repasse</label>
                            <select v-model="editForm.payout_method" class="ep-input cursor-pointer">
                                <option value="internal">Conta única</option>
                                <option value="cajupay_split" :disabled="!canUseSplitPayout">Split CajuPay</option>
                            </select>
                        </div>
                        <div v-if="editForm.payout_method === 'cajupay_split'">
                            <label class="ep-label">ID do split (UUID)</label>
                            <input
                                v-model="editForm.cajupay_split_id"
                                type="text"
                                class="ep-input font-mono !text-[13px]"
                            />
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-2 border-t border-[var(--ep-line)] pt-5">
                        <Button type="button" variant="outline" @click="closeEdit">Cancelar</Button>
                        <Button type="button" :disabled="saving" @click="saveEdit">Salvar</Button>
                    </div>
                </div>
            </div>
        </Teleport>

        <section class="space-y-3" aria-labelledby="coproducao-lista">
            <div class="flex items-baseline justify-between gap-3 px-1">
                <h3 id="coproducao-lista" class="ep-section-title">Co-produtores</h3>
                <span class="text-[12px] tabular-nums text-[var(--ep-text-4)]">{{ coproducers.length }} {{ coproducers.length === 1 ? 'co-produtor' : 'co-produtores' }}</span>
            </div>
            <div v-if="loading" class="panel-card ep-data flex items-center justify-center gap-2 px-5 py-10 text-[12.5px] text-[var(--ep-text-3)]">
                <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-[var(--ep-accent)]" aria-hidden="true" />
                Carregando…
            </div>
            <ProductPartnersTable
                v-else
                :rows="coproducerRows"
                :status-labels="coproducerStatusLabels"
                :show-product-column="false"
                empty-label="Nenhum co-produtor ainda."
            >
                <template #menu="{ row, close }">
                    <template v-if="row">
                        <button
                            v-if="row.status === 'active'"
                            type="button"
                            class="flex w-full items-center px-3 py-2 text-left text-[13px] text-[var(--ep-text-2)] transition-colors duration-150 hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)]"
                            @click="openEdit(coproducers.find((c) => c.id === row.id)); close()"
                        >
                            Editar repasse
                        </button>
                        <button
                            v-if="row.status === 'pending'"
                            type="button"
                            class="flex w-full items-center px-3 py-2 text-left text-[13px] text-[var(--ep-text-2)] transition-colors duration-150 hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)]"
                            @click="resend(row.id); close()"
                        >
                            Reenviar convite
                        </button>
                        <button
                            v-if="row.status !== 'revoked'"
                            type="button"
                            class="flex w-full items-center px-3 py-2 text-left text-[13px] text-[var(--ep-neg)] transition-colors duration-150 hover:bg-[var(--ep-neg-bg)]"
                            @click="revoke(row.id); close()"
                        >
                            Revogar
                        </button>
                    </template>
                </template>
            </ProductPartnersTable>
        </section>
    </div>
</template>
