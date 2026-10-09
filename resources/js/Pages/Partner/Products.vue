<script setup>
import { Link } from '@inertiajs/vue3';
import { Package } from 'lucide-vue-next';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';

defineOptions({ layout: LayoutInfoprodutor });

defineProps({
    products: { type: Array, default: () => [] },
    partner_role: { type: String, default: '' },
});

function formatPrice(value, currency = 'BRL') {
    const n = Number(value ?? 0);
    if (!n) return null;
    try {
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency }).format(n);
    } catch {
        return `R$ ${n.toFixed(2)}`;
    }
}

function statusLabel(status) {
    if (status === 'approved') return 'Aprovado';
    if (status === 'pending') return 'Aguardando aprovação';
    return status || '';
}

function statusClass(status) {
    if (status === 'approved') {
        return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300';
    }
    if (status === 'pending') {
        return 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300';
    }
    return 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400';
}
</script>

<template>
    <div class="space-y-5">
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-[22px] font-semibold tracking-[-0.025em] text-[var(--ep-text)]">Meus produtos</h1>
                <p class="mt-1 text-[13px] text-[var(--ep-text-3)]">
                    Produtos em que você atua como parceiro. Clique para ver detalhes, links e pixels.
                </p>
            </div>
            <span class="ep-chip tabular-nums">
                <Package class="h-3.5 w-3.5 text-[var(--ep-text-3)]" :stroke-width="1.75" aria-hidden="true" />
                {{ products.length }} {{ products.length === 1 ? 'produto' : 'produtos' }}
            </span>
        </header>

        <section>
            <div
                v-if="products.length === 0"
                class="panel-card ep-empty py-14"
            >
                <span class="ep-kpi__icon mb-2" aria-hidden="true">
                    <Package class="h-4 w-4" :stroke-width="1.75" />
                </span>
                <p class="ep-empty__title">Nenhum produto ainda</p>
                <p class="ep-empty__text">
                    Afilie-se pelo link público do produtor ou aguarde a aprovação se já solicitou afiliação.
                </p>
            </div>

            <ul v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <li v-for="p in products" :key="p.id">
                    <Link
                        :href="`/parceiro/produtos/${p.id}`"
                        class="panel-card group flex h-full items-center gap-4 p-4 transition-[border-color,transform] duration-150 hover:-translate-y-px hover:border-[var(--ep-line-strong)]"
                    >
                        <div
                            class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] shadow-[var(--ep-glass-highlight)]"
                        >
                            <img
                                v-if="p.image_url"
                                :src="p.image_url"
                                :alt="p.name"
                                class="h-full w-full object-cover"
                            />
                            <span
                                v-else
                                class="text-lg font-semibold text-[var(--ep-text-4)]"
                            >
                                {{ p.name?.charAt(0) }}
                            </span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <p class="truncate text-[13.5px] font-medium text-[var(--ep-text)]">{{ p.name }}</p>
                                <span
                                    v-if="p.affiliate_status"
                                    class="ep-chip shrink-0 !h-5 !px-1.5 !text-[10.5px]"
                                    :class="p.affiliate_status === 'approved' ? 'ep-chip--pos' : p.affiliate_status === 'pending' ? 'ep-chip--warn' : ''"
                                >
                                    {{ statusLabel(p.affiliate_status) }}
                                </span>
                            </div>
                            <p
                                v-if="formatPrice(p.price, p.currency)"
                                class="mt-0.5 text-[15px] font-semibold tabular-nums tracking-[-0.02em] text-[var(--ep-text)]"
                            >
                                {{ formatPrice(p.price, p.currency) }}
                            </p>
                            <p class="mt-1 text-[12px] text-[var(--ep-text-3)]">
                                <span v-if="p.commission_percent != null" class="tabular-nums"><span class="font-medium text-[var(--ep-accent)]">{{ p.commission_percent }}%</span> comissão</span>
                                <span v-if="p.partner_type" class="capitalize"> · {{ p.partner_type }}</span>
                            </p>
                        </div>
                    </Link>
                </li>
            </ul>
        </section>
    </div>
</template>
