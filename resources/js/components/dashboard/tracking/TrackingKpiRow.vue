<script setup>
import { computed } from 'vue';
import { formatBRL, formatPercent } from '@/composables/useTrackingPanel';
import {
    CircleDollarSign, TrendingUp, Percent, BarChart3, Receipt, Wallet,
} from 'lucide-vue-next';
import TrackingAdSpendCard from './TrackingAdSpendCard.vue';

const props = defineProps({
    financial: { type: Object, default: () => ({}) },
    adSpend: { type: Object, default: () => ({}) },
    period: { type: String, default: 'hoje' },
    valuesVisible: { type: Boolean, default: true },
});

const emit = defineEmits(['save-daily', 'save-period', 'clear-period']);

const cards = computed(() => {
    const f = props.financial ?? {};
    const hide = (v) => (props.valuesVisible ? v : '••••••');
    const lucroOperacional = f.lucro_operacional ?? f.lucro_liquido;

    return [
        {
            key: 'faturamento',
            label: 'Faturamento',
            title: 'Valor bruto pago pelos clientes',
            value: hide(formatBRL(f.faturamento_bruto)),
            icon: CircleDollarSign,
            accent: 'from-emerald-500/10',
        },
        {
            key: 'receita_liquida',
            label: 'Receita líquida',
            title: 'Faturamento bruto menos taxas do gateway',
            value: hide(formatBRL(f.receita_liquida)),
            icon: Wallet,
            accent: 'from-teal-500/10',
        },
        {
            key: 'lucro',
            label: 'Lucro operacional',
            title: 'Receita líquida menos comissões, reembolsos e gasto com ads',
            value: hide(formatBRL(lucroOperacional)),
            icon: TrendingUp,
            accent: 'from-[var(--color-primary)]/10',
        },
        {
            key: 'roi',
            label: 'ROI',
            title: 'Retorno sobre investimento em ads',
            value: hide(formatPercent(f.roi)),
            icon: Percent,
            accent: 'from-violet-500/10',
        },
        {
            key: 'roas',
            label: 'ROAS',
            title: 'Retorno sobre gasto com ads',
            value: hide(f.roas != null ? `${f.roas}x` : '—'),
            icon: BarChart3,
            accent: 'from-blue-500/10',
        },
        {
            key: 'ticket',
            label: 'Ticket médio',
            title: 'Faturamento bruto dividido pelo número de vendas',
            value: hide(formatBRL(f.ticket_medio)),
            icon: Receipt,
            accent: 'from-amber-500/10',
        },
    ];
});
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div
            v-for="card in cards"
            :key="card.key"
            class="panel-card ep-kpi min-w-0"
            :class="card.key === 'faturamento' ? 'ep-glow-card justify-between sm:col-span-2' : ''"
            :title="card.title"
        >
            <div class="flex items-center justify-between gap-2">
                <span class="ep-kpi__label truncate">{{ card.label }}</span>
                <span class="ep-kpi__icon shrink-0" aria-hidden="true">
                    <component :is="card.icon" class="h-4 w-4" :stroke-width="1.75" />
                </span>
            </div>
            <p
                class="ep-kpi__value mt-1 truncate"
                :class="card.key === 'faturamento' ? 'text-[clamp(28px,2.6vw,36px)] tracking-[-0.04em] [text-shadow:0_2px_24px_color-mix(in_oklab,var(--ep-glow)_60%,transparent)]' : ''"
            >
                {{ card.value }}
            </p>
            <p class="ep-kpi__meta truncate">{{ card.title }}</p>
        </div>
        <TrackingAdSpendCard
            :amount="financial?.gasto_ads ?? 0"
            :ad-spend-meta="adSpend"
            :period="period"
            :values-visible="valuesVisible"
            class="min-w-0"
            @save-daily="emit('save-daily', $event)"
            @save-period="emit('save-period', $event)"
            @clear-period="emit('clear-period')"
        />
    </div>
</template>
