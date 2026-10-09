<script setup>
import { computed } from 'vue';
import { Zap } from 'lucide-vue-next';
import { formatBRL, countryFlag, timeAgo } from '@/composables/useTrackingPanel';
import MoneyAmount from '@/components/ui/MoneyAmount.vue';

const props = defineProps({
    sales: { type: Array, default: () => [] },
    valuesVisible: { type: Boolean, default: true },
});

const summary = computed(() => {
    const total = props.sales.reduce((s, sale) => s + (sale.amount ?? 0), 0);
    return { count: props.sales.length, total };
});

function displayAmount(value) {
    return props.valuesVisible ? formatBRL(value) : '••••••';
}
</script>

<template>
    <section class="panel-card ep-data flex h-full min-w-0 max-w-full flex-col overflow-hidden" aria-labelledby="trk-recentes">
        <div class="flex min-w-0 items-start justify-between gap-3 px-5 pt-5">
            <div class="min-w-0">
                <h2 id="trk-recentes" class="flex min-w-0 flex-wrap items-center gap-2 text-[13px] font-medium text-[var(--ep-text-2)]">
                    <Zap class="h-4 w-4 text-[var(--ep-text-3)]" :stroke-width="1.75" aria-hidden="true" />
                    Vendas recentes
                    <span class="relative flex h-1.5 w-1.5" aria-hidden="true">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[var(--ep-pos)] opacity-60" />
                        <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-[var(--ep-pos)]" />
                    </span>
                </h2>
                <p v-if="summary.count" class="mt-1 text-[12px] tabular-nums text-[var(--ep-text-4)]">
                    {{ summary.count }} últimas · {{ displayAmount(summary.total) }}
                </p>
            </div>
        </div>

        <ul v-if="sales.length" class="mt-3 max-h-[380px] min-w-0 flex-1 overflow-y-auto overflow-x-hidden px-2 pb-2">
            <li
                v-for="(sale, idx) in sales"
                :key="sale.id"
                class="relative flex min-w-0 items-center gap-3 rounded-xl px-3 py-2.5 transition-colors duration-150 hover:bg-[var(--ep-hover)]"
            >
                <div class="relative shrink-0">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl border border-[var(--ep-glass-border)] bg-[var(--ep-glass-strong)] text-lg leading-none shadow-[var(--ep-glass-highlight)]">
                        {{ countryFlag(sale.country_code) }}
                    </span>
                    <span
                        class="absolute -left-1.5 -top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full border border-[var(--ep-glass-border)] bg-[var(--ep-drawer)] px-1 text-[9.5px] font-semibold tabular-nums text-[var(--ep-text-2)]"
                    >
                        {{ idx + 1 }}
                    </span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-[13px] font-medium text-[var(--ep-text)]">
                        {{ sale.product_name }}
                    </p>
                    <div class="mt-0.5 flex min-w-0 items-center gap-1.5 text-[11.5px] text-[var(--ep-text-3)]">
                        <span class="truncate">
                            {{ sale.payment_label }}
                        </span>
                        <span class="text-[var(--ep-text-4)]" aria-hidden="true">·</span>
                        <span class="shrink-0 tabular-nums text-[var(--ep-text-4)]">{{ timeAgo(sale.created_at) }}</span>
                    </div>
                </div>
                <div class="max-w-[42%] shrink-0 truncate text-right">
                    <MoneyAmount :value="Number(sale.amount) || 0" :hidden="!valuesVisible" size="md" />
                </div>
            </li>
        </ul>

        <div v-else class="ep-empty flex-1">
            <p class="ep-empty__title">Nenhuma venda recente</p>
            <p class="ep-empty__text">As últimas vendas do período aparecem aqui.</p>
        </div>
    </section>
</template>
