<script setup>
import { ref, computed } from 'vue';
import { X, ExternalLink } from 'lucide-vue-next';
import PluginSlotHost from '@/components/plugins/PluginSlotHost.vue';
import PluginRenderZone from '@/components/plugins/PluginRenderZone.vue';
import HorizontalScrollTabs from '@/components/ui/HorizontalScrollTabs.vue';
import MoneyAmount from '@/components/ui/MoneyAmount.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    venda: { type: Object, default: null },
    plugin_order_detail_panels: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);

const activeTab = ref('venda');

function checkoutSessionFromVenda(v) {
    if (!v) return null;
    return v.checkout_session ?? v.checkoutSession ?? null;
}

function metadataFromVenda(v) {
    if (!v || v.metadata == null) return null;
    return typeof v.metadata === 'object' ? v.metadata : null;
}

const utmSource = computed(() => {
    const v = props.venda;
    if (!v) return '';
    const cs = checkoutSessionFromVenda(v);
    const meta = metadataFromVenda(v);
    return (cs?.utm_source || meta?.utm_source || '').trim();
});
const utmCampaign = computed(() => {
    const v = props.venda;
    if (!v) return '';
    const cs = checkoutSessionFromVenda(v);
    const meta = metadataFromVenda(v);
    return (cs?.utm_campaign || meta?.utm_campaign || '').trim();
});
const utmMedium = computed(() => {
    const v = props.venda;
    if (!v) return '';
    const cs = checkoutSessionFromVenda(v);
    const meta = metadataFromVenda(v);
    return (cs?.utm_medium || meta?.utm_medium || '').trim();
});

function close() {
    emit('close');
}

function formatMoney(value, currency = 'BRL') {
    const code = typeof currency === 'string' && currency.trim() ? currency.trim().toUpperCase() : 'BRL';
    const locale = code === 'BRL' ? 'pt-BR' : code === 'EUR' ? 'de-DE' : 'en-US';
    return new Intl.NumberFormat(locale, { style: 'currency', currency: code }).format(value ?? 0);
}

function formatBRL(value) {
    return formatMoney(value, 'BRL');
}

function vendaDisplayAmount(v) {
    if (v?.display_amount_is_producer_share && v.display_amount != null) {
        return v.display_amount;
    }
    return v?.amount_total ?? v?.amount ?? 0;
}

function vendaGrossAmount(v) {
    return v?.gross_amount ?? v?.amount_total ?? v?.amount ?? 0;
}

function formatDate(value) {
    if (!value) return '–';
    const d = new Date(value);
    return d.toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function statusLabel(status) {
    const map = {
        completed: 'Pago',
        pending: 'Pendente',
        disputed: 'MED',
        cancelled: 'Cancelado',
        refunded: 'Reembolsado',
    };
    return map[status] ?? status ?? '–';
}

function itemLabel(item) {
    const isBump = Number(item?.position ?? 0) > 0;
    const baseName =
        item?.product?.name ??
        item?.product_offer?.name ??
        item?.subscription_plan?.name ??
        'Item';
    return isBump ? `${baseName} (Bump)` : baseName;
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-[100000] flex justify-end"
            aria-modal="true"
            role="dialog"
        >
            <div
                class="ep-scrim fixed inset-0"
                aria-hidden="true"
                @click="close"
            />
            <aside
                class="ep-drawer relative z-[100001] flex h-full w-full max-w-md flex-col sm:w-[440px] sm:rounded-l-[22px]"
            >
                <div class="flex items-start justify-between gap-3 px-6 pb-4 pt-6">
                    <div class="min-w-0">
                        <p class="text-[12px] text-[var(--ep-text-3)]">Venda <span class="font-mono tabular-nums">{{ venda ? `#${venda.id}` : '' }}</span></p>
                        <h2 class="mt-0.5 text-[17px] font-semibold tracking-[-0.02em] text-[var(--ep-text)]">
                            Detalhes da venda
                        </h2>
                    </div>
                    <button
                        type="button"
                        class="ep-btn-ghost ep-btn-icon -mr-2 shrink-0"
                        aria-label="Fechar"
                        @click="close"
                    >
                        <X class="h-[18px] w-[18px]" :stroke-width="1.75" />
                    </button>
                </div>

                <div v-if="!venda" class="ep-empty flex-1">
                    <p class="ep-empty__title">Nenhuma venda selecionada.</p>
                    <p class="ep-empty__text">Escolha uma venda na lista para ver valores, cliente e origem.</p>
                </div>

                <div v-else class="flex min-w-0 flex-1 flex-col overflow-hidden">
                    <HorizontalScrollTabs
                        aria-label="Abas"
                        :bleed="false"
                        wrapper-class="px-6 pb-4"
                        nav-class="ep-tabs"
                    >
                        <button
                            type="button"
                            :class="[
                                'ep-tab border',
                                activeTab === 'venda' ? 'ep-tab--active' : 'border-transparent',
                            ]"
                            @click="activeTab = 'venda'"
                        >
                            Venda
                        </button>
                        <button
                            type="button"
                            :class="[
                                'ep-tab border',
                                activeTab === 'cliente' ? 'ep-tab--active' : 'border-transparent',
                            ]"
                            @click="activeTab = 'cliente'"
                        >
                            Cliente
                        </button>
                        <button
                            v-for="panel in plugin_order_detail_panels"
                            :key="panel.id || panel.plugin_slug"
                            type="button"
                            :class="[
                                'ep-tab border',
                                activeTab === `plugin-${panel.id}` ? 'ep-tab--active' : 'border-transparent',
                            ]"
                            @click="activeTab = `plugin-${panel.id}`"
                        >
                            {{ panel.label || panel.id }}
                        </button>
                    </HorizontalScrollTabs>

                    <div class="ep-divider" aria-hidden="true" />

                    <div class="flex-1 overflow-y-auto px-6 py-5">
                        <!-- Aba Venda -->
                        <div v-show="activeTab === 'venda'" class="space-y-5">
                            <!-- Herói: valor bruto + status -->
                            <section class="panel-card ep-glow-card p-5" aria-label="Resumo da venda">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-[12.5px] font-medium text-[var(--ep-text-3)]">Valor bruto</span>
                                    <span
                                        class="ep-chip"
                                        :class="{ completed: 'ep-chip--pos', pending: 'ep-chip--warn', disputed: 'ep-chip--neg' }[venda.status] ?? ''"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                                        {{ statusLabel(venda.status) }}
                                    </span>
                                </div>
                                <MoneyAmount
                                    :value="Number(vendaGrossAmount(venda))"
                                    :currency="venda.currency"
                                    size="hero"
                                    class="mt-3 block"
                                />
                                <div class="mt-4 flex flex-wrap items-center gap-1.5">
                                    <span class="ep-chip" title="Tipo">{{ venda.payment_type_label ?? 'Pagamento único' }}</span>
                                    <span class="ep-chip" title="Método de pagamento">
                                        <span
                                            class="h-1.5 w-1.5 rounded-full"
                                            :style="{ background: String(venda.gateway_label ?? '').toUpperCase().startsWith('PIX') ? 'var(--ep-pix)' : venda.gateway_label === 'Boleto' ? 'var(--ep-boleto)' : ['Cartão', 'Apple Pay', 'Google Pay', 'PayPal'].includes(venda.gateway_label) ? 'var(--ep-cartao)' : 'var(--ep-text-4)' }"
                                            aria-hidden="true"
                                        />
                                        {{ venda.gateway_label ?? '–' }}
                                    </span>
                                </div>
                            </section>

                            <!-- Decomposição bruto → taxa → líquido -->
                            <section
                                v-if="venda.status === 'completed'"
                                class="rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4"
                                aria-label="Financeiro (gateway)"
                            >
                                <h3 class="ep-section-title">Financeiro (gateway)</h3>
                                <dl class="mt-3 space-y-2.5 text-[13px]">
                                    <div class="flex items-baseline justify-between gap-3">
                                        <dt class="text-[var(--ep-text-3)]">Bruto</dt>
                                        <dd class="tabular-nums text-[var(--ep-text-2)]">{{ formatMoney(venda.gross_amount ?? vendaGrossAmount(venda), venda.currency) }}</dd>
                                    </div>
                                    <div class="flex items-baseline justify-between gap-3">
                                        <dt class="flex items-center gap-1.5 text-[var(--ep-text-3)]">
                                            Taxa
                                            <span
                                                v-if="venda.fee_source === 'gateway_webhook' || venda.fee_source === 'cajupay_webhook'"
                                                class="ep-chip ep-chip--pos h-[18px] px-1.5 text-[10.5px]"
                                                title="Taxa informada pelo gateway"
                                            >real</span>
                                            <span
                                                v-else-if="venda.fee_source === 'estimated'"
                                                class="ep-chip ep-chip--warn h-[18px] px-1.5 text-[10.5px]"
                                                title="Taxa estimada conforme configuração do gateway"
                                            >est.</span>
                                        </dt>
                                        <dd class="tabular-nums text-[var(--ep-text-3)]">− {{ formatMoney(venda.gateway_fee ?? 0, venda.currency) }}</dd>
                                    </div>
                                    <div class="ep-divider" aria-hidden="true" />
                                    <div class="flex items-baseline justify-between gap-3">
                                        <dt class="font-medium text-[var(--ep-text)]">Líquido</dt>
                                        <dd class="text-[17px] font-semibold tabular-nums tracking-[-0.02em] text-[var(--ep-text)]">{{ formatMoney(venda.net_amount ?? venda.gross_amount ?? vendaGrossAmount(venda), venda.currency) }}</dd>
                                    </div>
                                </dl>
                                <div class="mt-3 flex h-1.5 w-full gap-[3px] overflow-hidden rounded-full bg-[var(--ep-active)]" aria-hidden="true">
                                    <span
                                        class="h-full rounded-full bg-gradient-to-r from-[var(--ep-accent)] to-[var(--ep-accent-2)]"
                                        :style="{ width: `${Math.min(100, Math.max(0, (Number(venda.net_amount ?? venda.gross_amount ?? vendaGrossAmount(venda)) / (Number(venda.gross_amount ?? vendaGrossAmount(venda)) || 1)) * 100))}%` }"
                                    />
                                    <span
                                        class="h-full rounded-full bg-[var(--ep-warn)]"
                                        :style="{ width: `${Math.min(100, Math.max(0, (Number(venda.gateway_fee ?? 0) / (Number(venda.gross_amount ?? vendaGrossAmount(venda)) || 1)) * 100))}%` }"
                                    />
                                </div>
                            </section>
                            <section
                                v-else
                                class="rounded-2xl border border-dashed border-[var(--ep-line-strong)] p-4"
                                aria-label="Financeiro estimado"
                            >
                                <h3 class="ep-section-title">Financeiro estimado</h3>
                                <dl class="mt-3 space-y-2.5 text-[13px]">
                                    <div class="flex items-baseline justify-between gap-3">
                                        <dt class="text-[var(--ep-text-3)]">Bruto</dt>
                                        <dd class="tabular-nums text-[var(--ep-text-2)]">{{ formatMoney(vendaGrossAmount(venda), venda.currency) }}</dd>
                                    </div>
                                    <div class="flex items-baseline justify-between gap-3">
                                        <dt class="text-[var(--ep-text-3)]">Taxa</dt>
                                        <dd class="tabular-nums text-[var(--ep-text-3)]">
                                            − {{ formatMoney(venda.gateway_fee ?? 0, venda.currency) }}
                                            <span class="text-[11px] text-[var(--ep-text-4)]"> (est.)</span>
                                        </dd>
                                    </div>
                                    <div class="ep-divider" aria-hidden="true" />
                                    <div class="flex items-baseline justify-between gap-3">
                                        <dt class="font-medium text-[var(--ep-text-2)]">Líquido</dt>
                                        <dd class="text-[15px] font-semibold tabular-nums tracking-[-0.02em] text-[var(--ep-text-2)]">
                                            {{ formatMoney(venda.net_amount ?? Math.max(0, vendaGrossAmount(venda) - Number(venda.gateway_fee ?? 0)), venda.currency) }}
                                        </dd>
                                    </div>
                                </dl>
                            </section>

                            <div
                                v-if="venda.has_partner_split && venda.display_amount_is_producer_share"
                                class="rounded-2xl border border-[color-mix(in_oklab,var(--ep-accent)_30%,transparent)] bg-[color-mix(in_oklab,var(--ep-accent)_8%,transparent)] p-4"
                            >
                                <p class="text-[12.5px] font-medium text-[var(--ep-text-3)]">
                                    Sua parte
                                    <span v-if="venda.display_amount_is_estimated"> (estimada)</span>
                                </p>
                                <p class="mt-1 text-[20px] font-semibold tabular-nums tracking-[-0.03em] text-[var(--ep-text)]">
                                    {{ formatMoney(vendaDisplayAmount(venda), venda.currency) }}
                                    <span
                                        v-if="venda.display_amount_is_estimated"
                                        class="text-[13px] font-normal text-[var(--ep-text-4)]"
                                        title="Estimativa até confirmação do pagamento e alocação de comissões"
                                    > *</span>
                                </p>
                                <p class="mt-1.5 text-[12px] leading-relaxed text-[var(--ep-text-4)]">
                                    Valor que fica com você após taxas do gateway e comissões de afiliados/co-produtores.
                                </p>
                            </div>

                            <!-- Detalhes -->
                            <section aria-label="Detalhes">
                                <h3 class="ep-section-title">Detalhes</h3>
                                <dl class="mt-2 text-[13px]">
                                    <div class="flex items-start justify-between gap-4 border-b border-[var(--ep-line)] py-2.5">
                                        <dt class="shrink-0 text-[var(--ep-text-3)]">Produto</dt>
                                        <dd class="min-w-0 text-right font-medium text-[var(--ep-text)]">{{ venda.product_display_name ?? venda.product?.name ?? '–' }}</dd>
                                    </div>
                                    <div v-if="venda.is_affiliate_sale" class="flex items-start justify-between gap-4 border-b border-[var(--ep-line)] py-2.5">
                                        <dt class="shrink-0 text-[var(--ep-text-3)]">Afiliado</dt>
                                        <dd class="min-w-0 text-right text-[var(--ep-text)]">
                                            {{ venda.affiliate?.name ?? '—' }}
                                            <span v-if="venda.affiliate?.code" class="mt-0.5 block font-mono text-[12px] text-[var(--ep-text-4)]">
                                                ref {{ venda.affiliate.code }}
                                            </span>
                                        </dd>
                                    </div>
                                    <div class="flex items-start justify-between gap-4 border-b border-[var(--ep-line)] py-2.5">
                                        <dt class="shrink-0 text-[var(--ep-text-3)]">Método de pagamento</dt>
                                        <dd class="min-w-0 text-right text-[var(--ep-text)]">{{ venda.gateway_label ?? '–' }}</dd>
                                    </div>
                                    <div class="flex items-start justify-between gap-4 border-b border-[var(--ep-line)] py-2.5">
                                        <dt class="shrink-0 text-[var(--ep-text-3)]">Parcelas</dt>
                                        <dd class="min-w-0 text-right tabular-nums text-[var(--ep-text)]">1</dd>
                                    </div>
                                    <div class="flex items-start justify-between gap-4 border-b border-[var(--ep-line)] py-2.5">
                                        <dt class="shrink-0 text-[var(--ep-text-3)]">Recorrência</dt>
                                        <dd class="min-w-0 text-right text-[var(--ep-text)]">{{ venda.subscription_plan_id ? 'Assinatura' : '–' }}</dd>
                                    </div>
                                    <div class="flex items-start justify-between gap-4 py-2.5">
                                        <dt class="shrink-0 text-[var(--ep-text-3)]">ID da venda</dt>
                                        <dd class="min-w-0 break-all text-right font-mono text-[12.5px] text-[var(--ep-text-2)]">{{ String(venda.id) }}</dd>
                                    </div>
                                </dl>
                            </section>

                            <section class="space-y-2" v-if="(venda.order_items ?? []).length" aria-label="Itens da compra">
                                <h3 class="ep-section-title">Itens da compra</h3>
                                <div class="overflow-hidden rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)]">
                                    <div
                                        v-for="(item, idx) in (venda.order_items ?? [])"
                                        :key="idx"
                                        class="flex items-center justify-between gap-3 border-b border-[var(--ep-line)] px-4 py-3 last:border-b-0"
                                    >
                                        <p class="min-w-0 text-[13px] text-[var(--ep-text)]">
                                            {{ itemLabel(item) }}
                                        </p>
                                        <p class="shrink-0 text-[13px] font-medium tabular-nums text-[var(--ep-text)]">
                                            {{ formatMoney(item.amount, venda.currency) }}
                                        </p>
                                    </div>
                                </div>
                            </section>

                            <!-- Links -->
                            <section class="space-y-3" aria-label="Links">
                                <div>
                                    <p class="text-[12.5px] font-medium text-[var(--ep-text-3)]">URL do Checkout</p>
                                    <a
                                        v-if="venda.checkout_url"
                                        :href="venda.checkout_url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="mt-1 inline-flex items-start gap-1 break-all text-[13px] text-[var(--ep-accent)] hover:underline"
                                    >
                                        {{ venda.checkout_url }}
                                        <ExternalLink class="mt-0.5 h-3.5 w-3.5 shrink-0" :stroke-width="1.75" />
                                    </a>
                                    <p v-else class="mt-1 text-[13px] text-[var(--ep-text-4)]">–</p>
                                </div>
                                <div>
                                    <p class="flex items-center gap-1.5 text-[12.5px] font-medium text-[var(--ep-text-3)]">
                                        Comprovação
                                        <span
                                            class="inline-flex h-4 w-4 items-center justify-center rounded-full border border-[var(--ep-line-strong)] text-[10px] text-[var(--ep-text-4)]"
                                            title="Gera um dossiê com dados do comprador + evidências de entrega/atividade (progresso, logs, IP). Útil para comprovar a venda em gateways (MED/chargeback/auditoria)."
                                        >
                                            ?
                                        </span>
                                    </p>
                                    <a
                                        :href="`/vendas/${venda.id}/comprovacao`"
                                        class="ep-btn-secondary mt-2 h-8 px-3 text-[12.5px]"
                                        title="Abrir dossiê de comprovação (documento para comprovar a venda e o acesso/atividade do aluno)"
                                    >
                                        Abrir dossiê de comprovação
                                        <ExternalLink class="h-3.5 w-3.5 shrink-0 text-[var(--ep-text-3)]" :stroke-width="1.75" />
                                    </a>
                                </div>
                            </section>

                            <!-- Origem (UTM) -->
                            <section aria-label="Origem">
                                <h3 class="ep-section-title">Origem</h3>
                                <dl class="mt-2 grid grid-cols-3 gap-2">
                                    <div class="min-w-0 rounded-xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] px-3 py-2.5">
                                        <dt class="font-mono text-[11px] text-[var(--ep-text-4)]">utm_source</dt>
                                        <dd class="mt-1 truncate text-[12.5px]" :class="utmSource ? 'font-medium text-[var(--ep-text)]' : 'text-[var(--ep-text-4)]'">
                                            {{ utmSource || 'Não informado' }}
                                        </dd>
                                    </div>
                                    <div class="min-w-0 rounded-xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] px-3 py-2.5">
                                        <dt class="font-mono text-[11px] text-[var(--ep-text-4)]">utm_campaign</dt>
                                        <dd class="mt-1 truncate text-[12.5px]" :class="utmCampaign ? 'font-medium text-[var(--ep-text)]' : 'text-[var(--ep-text-4)]'">
                                            {{ utmCampaign || 'Não informado' }}
                                        </dd>
                                    </div>
                                    <div class="min-w-0 rounded-xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] px-3 py-2.5">
                                        <dt class="font-mono text-[11px] text-[var(--ep-text-4)]">utm_medium</dt>
                                        <dd class="mt-1 truncate text-[12.5px]" :class="utmMedium ? 'font-medium text-[var(--ep-text)]' : 'text-[var(--ep-text-4)]'">
                                            {{ utmMedium || 'Não informado' }}
                                        </dd>
                                    </div>
                                </dl>
                            </section>

                            <!-- Linha do tempo -->
                            <section aria-label="Linha do tempo">
                                <h3 class="ep-section-title">Linha do tempo</h3>
                                <ol class="relative ml-[5px] mt-3 space-y-4 border-l border-[var(--ep-line-strong)] pl-5">
                                    <li class="relative">
                                        <span
                                            class="absolute -left-[26px] top-1 h-2.5 w-2.5 rounded-full"
                                            :class="{ completed: 'bg-[var(--ep-pos)] text-[var(--ep-pos)]', pending: 'bg-[var(--ep-warn)] text-[var(--ep-warn)]', disputed: 'bg-[var(--ep-neg)] text-[var(--ep-neg)]' }[venda.status] ?? 'bg-[var(--ep-text-4)] text-[var(--ep-text-4)]'"
                                            aria-hidden="true"
                                        />
                                        <p class="text-[13px] font-medium text-[var(--ep-text)]">{{ statusLabel(venda.status) }}</p>
                                        <p class="mt-0.5 text-[12px] text-[var(--ep-text-3)]">Status atual · {{ venda.gateway_label ?? '–' }}</p>
                                    </li>
                                    <li class="relative">
                                        <span class="absolute -left-[26px] top-1 h-2.5 w-2.5 rounded-full bg-[var(--ep-accent)]" aria-hidden="true" />
                                        <p class="text-[13px] font-medium text-[var(--ep-text)]">Data de criação</p>
                                        <p class="mt-0.5 text-[12px] tabular-nums text-[var(--ep-text-3)]">{{ formatDate(venda.created_at) }}</p>
                                    </li>
                                </ol>
                            </section>
                        </div>

                        <div
                            v-for="panel in plugin_order_detail_panels"
                            :key="`panel-${panel.id}`"
                            v-show="activeTab === `plugin-${panel.id}`"
                        >
                            <PluginSlotHost
                                layout="stack"
                                :items="[panel]"
                                :context="{ venda, order: venda }"
                            />
                            <PluginRenderZone
                                zone="vendas.detail.after_panel"
                                :context="{ venda, order: venda }"
                            />
                        </div>

                        <!-- Aba Cliente -->
                        <div v-show="activeTab === 'cliente'" class="space-y-5">
                            <div class="panel-card flex items-center gap-3 p-4">
                                <span v-avatar="venda.user?.name || venda.email" class="ep-avatar h-10 w-10 shrink-0 text-[13px]" aria-hidden="true">{{ String(venda.user?.name || venda.email || '?').trim().split(/\s+/).filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase() || '?' }}</span>
                                <div class="min-w-0">
                                    <p class="text-[12px] text-[var(--ep-text-3)]">Nome</p>
                                    <p class="truncate text-[14px] font-medium text-[var(--ep-text)]">{{ venda.user?.name ?? venda.email ?? '–' }}</p>
                                </div>
                            </div>
                            <dl class="text-[13px]">
                                <div class="flex items-start justify-between gap-4 border-b border-[var(--ep-line)] py-2.5">
                                    <dt class="shrink-0 text-[var(--ep-text-3)]">E-mail</dt>
                                    <dd class="min-w-0 break-all text-right text-[var(--ep-text)]">{{ venda.email ?? venda.user?.email ?? '–' }}</dd>
                                </div>
                                <div class="flex items-start justify-between gap-4 border-b border-[var(--ep-line)] py-2.5">
                                    <dt class="shrink-0 text-[var(--ep-text-3)]">Celular</dt>
                                    <dd class="min-w-0 text-right tabular-nums text-[var(--ep-text)]">{{ venda.phone ?? '–' }}</dd>
                                </div>
                                <div class="flex items-start justify-between gap-4 border-b border-[var(--ep-line)] py-2.5">
                                    <dt class="shrink-0 text-[var(--ep-text-3)]">CPF</dt>
                                    <dd class="min-w-0 text-right tabular-nums text-[var(--ep-text)]">{{ venda.cpf ?? '–' }}</dd>
                                </div>
                                <div class="flex items-start justify-between gap-4 py-2.5">
                                    <dt class="shrink-0 text-[var(--ep-text-3)]">IP</dt>
                                    <dd class="min-w-0 text-right font-mono text-[12.5px] text-[var(--ep-text-2)]">{{ venda.customer_ip ?? '–' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </Teleport>
</template>
