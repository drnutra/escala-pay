<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import FinanceiroDashboard from '@/components/financeiro/FinanceiroDashboard.vue';
import FinanceiroPartnerPayoutsTab from '@/components/financeiro/FinanceiroPartnerPayoutsTab.vue';
import FinanceiroCajupayGate from '@/components/financeiro/FinanceiroCajupayGate.vue';
import FinanceiroCajupayHint from '@/components/financeiro/FinanceiroCajupayHint.vue';
import BetaBadge from '@/components/ui/BetaBadge.vue';
import PluginSlotHost from '@/components/plugins/PluginSlotHost.vue';

defineOptions({ layout: LayoutInfoprodutor });

const props = defineProps({
    cajupay_connected: { type: Boolean, default: false },
    balances: { type: Object, default: () => ({ by_wallet: {}, totals: {} }) },
    wallet_labels: { type: Object, default: () => ({}) },
    transactions: { type: Array, default: () => [] },
    payouts: { type: Array, default: () => [] },
    pix_key: { type: String, default: '' },
    pix_key_type: { type: String, default: '' },
    pix_owner_document: { type: String, default: '' },
    min_payout_cents: { type: Number, default: 100 },
    summary: { type: Object, default: () => ({}) },
    partner_payouts: { type: Object, default: () => ({ items: [], summary: {} }) },
    plugin_financeiro_tabs: { type: Array, default: () => [] },
});

const activeTab = ref('wallet');

const page = usePage();
const canManageFinanceiro = computed(() => {
    const role = page.props.auth?.user?.role;
    if (role === 'admin' || role === 'infoprodutor') {
        return true;
    }
    return !!page.props.auth?.permissions?.['financeiro.manage'];
});

function onReload() {
    router.reload();
}
</script>

<template>
    <div class="space-y-5">
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div class="min-w-0">
                <h1 class="flex flex-wrap items-center gap-2 text-[22px] font-semibold tracking-[-0.025em] text-[var(--ep-text)]">
                    Financeiro
                    <BetaBadge />
                </h1>
                <p class="mt-1 text-[13px] text-[var(--ep-text-3)]">
                    Acompanhe seu saldo e movimentações das vendas na plataforma.
                </p>
            </div>
            <FinanceiroCajupayHint v-if="cajupay_connected" variant="producer" />
        </header>

        <div
            v-if="!cajupay_connected"
            class="panel-card flex items-start gap-3 px-4 py-3 text-[13px] text-[var(--ep-text-2)]"
            role="status"
        >
            <span class="ep-chip ep-chip--warn shrink-0">
                <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                Atenção
            </span>
            <p class="leading-relaxed">
                Saques e aprovações de parceiros dependem da
                <strong class="font-medium text-[var(--ep-text)]">CajuPay</strong> configurada. Conecte em Integrações para liberar o painel abaixo.
            </p>
        </div>

        <FinanceiroCajupayGate :connected="cajupay_connected" variant="producer">
            <div class="space-y-5">
                <nav
                    class="ep-tabs max-w-full overflow-x-auto no-scrollbar"
                    aria-label="Seções do financeiro"
                >
                    <button
                        type="button"
                        class="ep-tab"
                        :class="activeTab === 'wallet' ? 'ep-tab--active' : ''"
                        @click="activeTab = 'wallet'"
                    >
                        Minha carteira
                    </button>
                    <button
                        type="button"
                        class="ep-tab"
                        :class="activeTab === 'partners' ? 'ep-tab--active' : ''"
                        @click="activeTab = 'partners'"
                    >
                        Saques de parceiros
                        <span
                            v-if="partner_payouts.summary?.pending_count > 0"
                            class="ep-chip ep-chip--warn !h-[18px] min-w-[18px] justify-center !px-1.5 text-[10.5px] tabular-nums"
                        >
                            {{ partner_payouts.summary.pending_count }}
                        </span>
                    </button>
                    <button
                        v-for="tab in plugin_financeiro_tabs"
                        :key="tab.id || tab.plugin_slug"
                        type="button"
                        class="ep-tab"
                        :class="activeTab === `plugin-${tab.id}` ? 'ep-tab--active' : ''"
                        @click="activeTab = `plugin-${tab.id}`"
                    >
                        {{ tab.label || tab.id }}
                    </button>
                </nav>

                <div v-show="activeTab === 'wallet'" class="min-h-0">
                    <FinanceiroDashboard
                        :balances="balances"
                        :wallet-labels="wallet_labels"
                        :transactions="transactions"
                        :commissions="[]"
                        :payouts="payouts"
                        :pix-key="pix_key"
                        :pix-key-type="pix_key_type"
                        :pix-owner-document="pix_owner_document"
                        :min-payout-cents="min_payout_cents"
                        :cajupay-connected="cajupay_connected"
                        payout-base-url="/financeiro/payout"
                        pix-base-url="/financeiro/pix"
                        :partner-summary="summary"
                        :show-commissions-table="false"
                        @reload="onReload"
                    />
                </div>

                <div v-show="activeTab === 'partners'" class="min-h-0">
                    <FinanceiroPartnerPayoutsTab
                        :partner-payouts="partner_payouts"
                        :can-manage="canManageFinanceiro"
                    />
                </div>

                <div
                    v-for="tab in plugin_financeiro_tabs"
                    :key="`panel-${tab.id}`"
                    v-show="activeTab === `plugin-${tab.id}`"
                    class="min-h-0"
                >
                    <PluginSlotHost
                        layout="stack"
                        :items="[tab]"
                        :context="{ tab }"
                    />
                </div>
            </div>
        </FinanceiroCajupayGate>
    </div>
</template>
