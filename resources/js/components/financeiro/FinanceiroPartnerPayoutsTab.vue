<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import Button from '@/components/ui/Button.vue';
import { Clock, CheckCircle, XCircle, CreditCard, QrCode, Receipt, Users } from 'lucide-vue-next';

const props = defineProps({
    partnerPayouts: {
        type: Object,
        default: () => ({ items: [], summary: { pending_count: 0, pending_amount: 0 } }),
    },
    canManage: { type: Boolean, default: true },
});

const msg = ref('');
const processingId = ref(null);

const statusLabels = {
    pending_approval: 'Aguardando aprovação',
    processing: 'Processando',
    awaiting_payout: 'Processando PIX',
    completed: 'Concluído',
    failed: 'Falhou',
    cancelled: 'Rejeitado',
};

const walletIcons = { pix: QrCode, card: CreditCard, boleto: Receipt };

function formatBRL(v) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v ?? 0);
}

function formatDate(iso) {
    if (!iso) return '—';
    return new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(iso));
}

function roleLabel(role) {
    if (role === 'afiliado') return 'Afiliado';
    if (role === 'coprodutor') return 'Co-produtor';
    return role || 'Parceiro';
}

function roleBadgeClass(role) {
    if (role === 'afiliado') {
        return 'bg-violet-500/15 text-violet-700 dark:text-violet-300';
    }
    if (role === 'coprodutor') {
        return 'bg-sky-500/15 text-sky-700 dark:text-sky-300';
    }
    return 'bg-zinc-500/15 text-zinc-600 dark:text-zinc-400';
}

async function approve(payout) {
    if (!confirm(`Aprovar saque de ${formatBRL(payout.amount)} para ${payout.partner?.name}?`)) return;
    processingId.value = payout.id;
    msg.value = '';
    try {
        const { data } = await axios.post(`/financeiro/saques-parceiros/${payout.id}/approve`);
        msg.value = data.message || 'Saque aprovado.';
        router.reload({ only: ['partner_payouts', 'summary', 'balances'] });
    } catch (e) {
        msg.value = e.response?.data?.message || e.response?.data?.errors
            ? Object.values(e.response.data.errors || {}).flat().join(' ')
            : 'Erro ao aprovar.';
    } finally {
        processingId.value = null;
    }
}

async function reject(payout) {
    const reason = window.prompt('Motivo da rejeição (opcional):');
    if (reason === null) return;
    processingId.value = payout.id;
    msg.value = '';
    try {
        await axios.post(`/financeiro/saques-parceiros/${payout.id}/reject`, { reason: reason || undefined });
        msg.value = 'Saque rejeitado.';
        router.reload({ only: ['partner_payouts', 'summary', 'balances'] });
    } catch (e) {
        msg.value = e.response?.data?.message || 'Erro ao rejeitar.';
    } finally {
        processingId.value = null;
    }
}
</script>

<template>
    <div class="space-y-4">
        <div class="grid gap-4 lg:grid-cols-12">
            <section class="panel-card ep-glow-card flex flex-col p-6 lg:col-span-5" aria-labelledby="fin-parceiros-fila">
                <div class="flex items-center justify-between gap-3">
                    <h2 id="fin-parceiros-fila" class="text-[13px] font-medium text-[var(--ep-text-2)]">Saques aguardando sua aprovação</h2>
                    <span class="ep-kpi__icon" aria-hidden="true"><Clock class="h-4 w-4" :stroke-width="1.75" /></span>
                </div>
                <div class="mt-5 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                    <span class="text-[44px] font-semibold leading-none tabular-nums tracking-[-0.045em] text-[var(--ep-text)]">
                        {{ partnerPayouts.summary?.pending_count ?? 0 }}
                    </span>
                    <span class="text-[15px] font-medium tabular-nums text-[var(--ep-text-3)]">
                        · {{ formatBRL(partnerPayouts.summary?.pending_amount) }}
                    </span>
                </div>
                <p class="mt-6 flex items-start gap-2 border-t border-[var(--ep-line)] pt-4 text-[12.5px] leading-relaxed text-[var(--ep-text-3)] lg:mt-auto">
                    <span class="mt-[7px] h-1.5 w-1.5 shrink-0 rounded-full bg-[var(--ep-warn)]" aria-hidden="true" />
                    Cartão e boleto exigem aprovação. Confirme que há saldo na plataforma antes de aprovar.
                </p>
            </section>

            <section class="panel-card flex items-start gap-3 p-6 lg:col-span-7" aria-labelledby="fin-parceiros-sobre">
                <span class="ep-kpi__icon shrink-0" aria-hidden="true"><Users class="h-4 w-4" :stroke-width="1.75" /></span>
                <div class="min-w-0 text-[13px] leading-relaxed text-[var(--ep-text-3)]">
                    <h2 id="fin-parceiros-sobre" class="text-[13px] font-medium text-[var(--ep-text)]">
                        Saques de afiliados e co-produtores
                    </h2>
                    <p class="mt-1.5">
                        Aqui aparecem as solicitações de saque feitas pelos seus
                        <strong class="font-medium text-[var(--ep-text-2)]">afiliados</strong>
                        e
                        <strong class="font-medium text-[var(--ep-text-2)]">co-produtores</strong>
                        (comissões de vendas na sua conta). PIX costuma ser automático; saques de
                        <strong class="font-medium text-[var(--ep-text-2)]">cartão</strong>
                        e
                        <strong class="font-medium text-[var(--ep-text-2)]">boleto</strong>
                        ficam nesta fila até você aprovar ou rejeitar.
                    </p>
                </div>
            </section>
        </div>

        <p
            v-if="msg"
            class="flex items-start gap-2 rounded-xl border px-4 py-2.5 text-[13px]"
            :class="msg.includes('rejeit')
                ? 'border-[color-mix(in_oklab,var(--ep-neg)_30%,transparent)] bg-[var(--ep-neg-bg)] text-[var(--ep-neg)]'
                : 'border-[color-mix(in_oklab,var(--ep-pos)_30%,transparent)] bg-[var(--ep-pos-bg)] text-[var(--ep-pos)]'"
            role="status"
        >
            <span class="mt-[7px] h-1.5 w-1.5 shrink-0 rounded-full bg-current" aria-hidden="true" />
            {{ msg }}
        </p>

        <section class="panel-card ep-data overflow-hidden" aria-labelledby="fin-parceiros-lista">
            <div class="border-b border-[var(--ep-line)] px-5 pb-4 pt-5">
                <h2 id="fin-parceiros-lista" class="ep-section-title">
                    Solicitações de afiliados e co-produtores
                </h2>
                <p class="mt-1 text-[12px] text-[var(--ep-text-4)]">
                    Cada linha é um parceiro que pediu saque da comissão — o papel (afiliado ou co-produtor)
                    aparece ao lado do nome.
                </p>
            </div>

            <div v-if="partnerPayouts.items?.length" class="divide-y divide-[var(--ep-line)]">
                <div
                    v-for="p in partnerPayouts.items"
                    :key="p.id"
                    class="flex flex-col gap-4 px-5 py-4 transition-colors duration-150 hover:bg-[var(--ep-hover)] sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="flex min-w-0 items-start gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[10px] border border-[var(--ep-line)] bg-[var(--ep-card-2)]">
                            <component :is="walletIcons[p.wallet_bucket] || QrCode" class="h-4 w-4 text-[var(--ep-text-3)]" :stroke-width="1.75" />
                        </div>
                        <div class="min-w-0">
                            <p class="flex flex-wrap items-center gap-2 text-[13.5px] font-medium text-[var(--ep-text)]">
                                {{ p.partner?.name || 'Parceiro' }}
                                <span
                                    class="ep-chip"
                                    :class="p.partner?.role === 'afiliado' || p.partner?.role === 'coprodutor' ? 'ep-chip--accent' : ''"
                                >
                                    {{ roleLabel(p.partner?.role) }}
                                </span>
                            </p>
                            <p class="truncate text-[12px] text-[var(--ep-text-3)]">{{ p.partner?.email }}</p>
                            <p class="mt-1.5 text-[13px] text-[var(--ep-text-3)]">
                                <span class="font-semibold tabular-nums text-[var(--ep-text)]">{{ formatBRL(p.amount) }}</span>
                                · {{ p.wallet_label }}
                                · PIX {{ p.pix_key_masked }}
                            </p>
                            <p class="mt-0.5 text-[11.5px] tabular-nums text-[var(--ep-text-4)]">{{ formatDate(p.created_at) }}</p>
                        </div>
                    </div>

                    <div class="flex shrink-0 flex-wrap items-center gap-2 sm:flex-col sm:items-end">
                        <span
                            class="ep-chip"
                            :class="{
                                'ep-chip--warn': p.status === 'pending_approval',
                                'ep-chip--pos': p.status === 'completed',
                                'ep-chip--neg': p.status === 'failed' || p.status === 'cancelled',
                                'ep-chip--accent': p.status === 'processing' || p.status === 'awaiting_payout',
                            }"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                            {{ statusLabels[p.status] || p.status }}
                        </span>

                        <p
                            v-if="p.status === 'pending_approval' && !canManage"
                            class="text-[12px] text-[var(--ep-text-4)]"
                        >
                            Sem permissão para aprovar
                        </p>
                        <div v-else-if="canManage && p.status === 'pending_approval'" class="flex gap-2">
                            <Button
                                type="button"
                                size="sm"
                                :disabled="processingId === p.id"
                                @click="approve(p)"
                            >
                                <CheckCircle class="h-4 w-4" :stroke-width="1.75" />
                                Aprovar
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                :disabled="processingId === p.id"
                                @click="reject(p)"
                            >
                                <XCircle class="h-4 w-4" :stroke-width="1.75" />
                                Rejeitar
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="ep-empty">
                <p class="ep-empty__title">Nenhuma solicitação de saque de afiliados ou co-produtores no momento.</p>
                <p class="ep-empty__text">Quando um parceiro pedir saque da comissão, a solicitação aparece nesta fila.</p>
            </div>
        </section>
    </div>
</template>
