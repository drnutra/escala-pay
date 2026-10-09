<script setup>
import { computed } from 'vue';
import {
    CreditCard,
    QrCode,
    Barcode,
    Smartphone,
    Wallet,
} from 'lucide-vue-next';
import { formatBRL } from '@/composables/useTrackingPanel';
import MoneyAmount from '@/components/ui/MoneyAmount.vue';

const props = defineProps({
    methods: { type: Array, default: () => [] },
    valuesVisible: { type: Boolean, default: true },
});

const METHOD_STYLES = {
    pix: {
        icon: QrCode,
        bar: 'from-teal-500 to-emerald-400',
        chip: 'bg-teal-500/12 text-teal-700 dark:text-teal-300',
        ring: 'ring-teal-500/20',
    },
    pix_auto: {
        icon: QrCode,
        bar: 'from-teal-500 to-emerald-400',
        chip: 'bg-teal-500/12 text-teal-700 dark:text-teal-300',
        ring: 'ring-teal-500/20',
    },
    card: {
        icon: CreditCard,
        bar: 'from-blue-500 to-indigo-400',
        chip: 'bg-blue-500/12 text-blue-700 dark:text-blue-300',
        ring: 'ring-blue-500/20',
    },
    boleto: {
        icon: Barcode,
        bar: 'from-amber-500 to-orange-400',
        chip: 'bg-amber-500/12 text-amber-800 dark:text-amber-300',
        ring: 'ring-amber-500/20',
    },
    apple_pay: {
        icon: Smartphone,
        bar: 'from-zinc-600 to-zinc-800',
        chip: 'bg-zinc-500/12 text-zinc-700 dark:text-zinc-300',
        ring: 'ring-zinc-500/20',
    },
    google_pay: {
        icon: Smartphone,
        bar: 'from-violet-500 to-purple-400',
        chip: 'bg-violet-500/12 text-violet-700 dark:text-violet-300',
        ring: 'ring-violet-500/20',
    },
};

const defaultStyle = {
    icon: Wallet,
    bar: 'from-[var(--color-primary)] to-[var(--color-primary)]/70',
    chip: 'bg-[var(--color-primary)]/12 text-[var(--color-primary)]',
    ring: 'ring-[var(--color-primary)]/20',
};

const summary = computed(() => {
    const items = props.methods.map((m) => {
        const style = METHOD_STYLES[m.metodo] ?? defaultStyle;
        return { ...m, style };
    });
    const totalAmount = items.reduce((s, m) => s + (m.total ?? 0), 0);
    const totalQty = items.reduce((s, m) => s + (m.quantidade ?? 0), 0);

    return {
        totalAmount,
        totalQty,
        items: items.map((m) => ({
            ...m,
            percent: totalAmount > 0 ? Math.round((m.total / totalAmount) * 1000) / 10 : 0,
        })),
    };
});

const leader = computed(() => summary.value.items[0] ?? null);

function displayAmount(value) {
    return props.valuesVisible ? formatBRL(value) : '••••••';
}

function displayCount(value) {
    return props.valuesVisible ? String(value) : '—';
}
</script>

<template>
    <section class="panel-card flex h-full min-w-0 flex-col p-5" aria-labelledby="trk-metodos">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 id="trk-metodos" class="flex items-center gap-2 text-[13px] font-medium text-[var(--ep-text-2)]">
                    <CreditCard class="h-4 w-4 text-[var(--ep-text-3)]" :stroke-width="1.75" aria-hidden="true" />
                    Métodos de pagamento
                </h2>
                <p v-if="summary.items.length" class="mt-1 text-[12px] tabular-nums text-[var(--ep-text-4)]">
                    {{ displayCount(summary.totalQty) }} vendas · {{ displayAmount(summary.totalAmount) }}
                </p>
            </div>
            <div
                v-if="leader"
                class="ep-chip hidden shrink-0 tabular-nums sm:inline-flex"
                :style="{ '--mc': ({ pix: 'var(--ep-pix)', pix_auto: 'var(--ep-pix)', card: 'var(--ep-cartao)', boleto: 'var(--ep-boleto)', apple_pay: 'var(--ep-text-2)', google_pay: 'var(--ep-accent-2)' })[leader.metodo] || 'var(--ep-accent)' }"
            >
                <span class="h-1.5 w-1.5 rounded-full bg-[var(--mc)]" aria-hidden="true" />
                <span class="text-[10.5px] font-medium text-[var(--ep-text-3)]">Líder</span>
                <span class="font-semibold text-[var(--ep-text)]">{{ leader.percent }}%</span>
            </div>
        </div>

        <div v-if="summary.items.length" class="mt-4 flex flex-1 flex-col gap-4">
            <div
                v-if="leader"
                class="relative overflow-hidden rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4 shadow-[var(--ep-glass-highlight)]"
                :style="{ '--mc': ({ pix: 'var(--ep-pix)', pix_auto: 'var(--ep-pix)', card: 'var(--ep-cartao)', boleto: 'var(--ep-boleto)', apple_pay: 'var(--ep-text-2)', google_pay: 'var(--ep-accent-2)' })[leader.metodo] || 'var(--ep-accent)' }"
            >
                <div
                    class="pointer-events-none absolute -right-6 -top-10 h-28 w-28 rounded-full bg-[radial-gradient(closest-side,color-mix(in_oklab,var(--mc)_45%,transparent),transparent)] blur-xl"
                    aria-hidden="true"
                />
                <div class="relative flex items-center gap-3">
                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-[color-mix(in_oklab,var(--mc)_35%,transparent)] bg-[color-mix(in_oklab,var(--mc)_14%,transparent)] text-[var(--mc)]"
                    >
                        <component :is="leader.style.icon" class="h-5 w-5" :stroke-width="1.75" aria-hidden="true" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-[13.5px] font-semibold text-[var(--ep-text)]">{{ leader.label }}</p>
                        <p class="text-[12px] tabular-nums text-[var(--ep-text-3)]">
                            {{ displayCount(leader.quantidade) }}
                            {{ leader.quantidade === 1 ? 'venda' : 'vendas' }}
                            · {{ leader.percent }}%
                        </p>
                    </div>
                    <div class="shrink-0 text-right">
                        <MoneyAmount :value="Number(leader.total) || 0" :hidden="!valuesVisible" size="md" />
                    </div>
                </div>
            </div>

            <ul class="space-y-3.5">
                <li
                    v-for="m in summary.items"
                    :key="m.metodo"
                    :style="{ '--mc': ({ pix: 'var(--ep-pix)', pix_auto: 'var(--ep-pix)', card: 'var(--ep-cartao)', boleto: 'var(--ep-boleto)', apple_pay: 'var(--ep-text-2)', google_pay: 'var(--ep-accent-2)' })[m.metodo] || 'var(--ep-accent)' }"
                >
                    <div class="flex items-center gap-2.5 text-[13px]">
                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border border-[var(--ep-line)] bg-[var(--ep-card-2)] text-[var(--mc)]">
                            <component :is="m.style.icon" class="h-3.5 w-3.5" :stroke-width="1.75" aria-hidden="true" />
                        </div>
                        <span class="min-w-0 flex-1 truncate font-medium text-[var(--ep-text)]">
                            {{ m.label }}
                        </span>
                        <span class="hidden shrink-0 whitespace-nowrap text-[12px] tabular-nums text-[var(--ep-text-4)] sm:inline">{{ displayCount(m.quantidade) }} vendas</span>
                        <span class="w-11 shrink-0 text-right text-[12px] tabular-nums text-[var(--ep-text-3)]">
                            {{ m.percent }}%
                        </span>
                        <span class="w-[104px] shrink-0 truncate text-right font-medium tabular-nums text-[var(--ep-text)]">
                            {{ displayAmount(m.total) }}
                        </span>
                    </div>
                    <div class="ml-[38px] mt-1.5 h-1.5 overflow-hidden rounded-full bg-[var(--ep-active)]">
                        <div
                            class="h-full rounded-full bg-[linear-gradient(90deg,color-mix(in_oklab,var(--mc)_65%,transparent),var(--mc))] transition-[width] duration-500 ease-[cubic-bezier(0.23,1,0.32,1)]"
                            :style="{ width: `${Math.max(m.percent, m.total > 0 ? 4 : 0)}%` }"
                        />
                    </div>
                </li>
            </ul>
        </div>

        <div v-else class="ep-empty flex-1">
            <p class="ep-empty__title">Nenhum pagamento no período</p>
            <p class="ep-empty__text">A divisão entre Pix, cartão e boleto aparece após a primeira venda aprovada.</p>
        </div>
    </section>
</template>
