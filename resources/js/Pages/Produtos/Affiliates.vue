<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import ProdutosTabs from '@/components/produtos/ProdutosTabs.vue';
import ProductPartnersTable from '@/components/produtos/ProductPartnersTable.vue';
import { ChevronDown, ChevronRight } from 'lucide-vue-next';

defineOptions({ layout: LayoutInfoprodutor });

const props = defineProps({
    affiliates: { type: Object, required: true },
    programs: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const affiliateList = computed(() => props.affiliates?.data ?? []);

const affiliateRows = computed(() =>
    affiliateList.value.map((a) => ({
        id: a.id,
        created_at: a.created_at,
        name: a.user?.name ?? null,
        email: a.user?.email ?? null,
        product_name: a.product_name,
        commission_percent: a.commission_percent,
        status: a.status,
        product_id: a.product_id,
        affiliate_link: a.affiliate_link,
    }))
);

async function approve(id, productId) {
    await axios.put(`/produtos/${productId}/affiliates/${id}`, { status: 'approved' });
    router.reload({ only: ['affiliates'] });
}

async function reject(id, productId) {
    await axios.put(`/produtos/${productId}/affiliates/${id}`, { status: 'rejected' });
    router.reload({ only: ['affiliates'] });
}

function copyLink(url) {
    if (!url) return;
    navigator.clipboard?.writeText(url);
}
</script>

<template>
    <div class="space-y-5">
        <div>
            <h1 class="ep-page-heading">Afiliados</h1>
            <p class="mt-1 text-[13px] text-[var(--ep-text-3)]">
                Todos os afiliados da conta. Para configurar comissão e página pública, abra o produto.
            </p>
        </div>
        <ProdutosTabs />

        <ProductPartnersTable
            :rows="affiliateRows"
            empty-label="Nenhum afiliado encontrado."
        >
            <template #menu="{ row, close }">
                <template v-if="row">
                    <Link
                        v-if="row.product_id"
                        :href="`/produtos/${row.product_id}/edit?tab=afiliados`"
                        class="flex w-full rounded-[10px] px-2.5 py-2 text-left text-[13px] text-[var(--ep-text-2)] transition-colors duration-150 hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)]"
                        @click="close()"
                    >
                        Configurar programa
                    </Link>
                    <button
                        v-if="row.affiliate_link"
                        type="button"
                        class="flex w-full rounded-[10px] px-2.5 py-2 text-left text-[13px] text-[var(--ep-text-2)] transition-colors duration-150 hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)]"
                        @click="copyLink(row.affiliate_link); close()"
                    >
                        Copiar link
                    </button>
                    <button
                        v-if="row.status === 'pending'"
                        type="button"
                        class="flex w-full rounded-[10px] px-2.5 py-2 text-left text-[13px] font-medium text-[var(--ep-pos)] transition-colors duration-150 hover:bg-[var(--ep-pos-bg)]"
                        @click="approve(row.id, row.product_id); close()"
                    >
                        Aprovar
                    </button>
                    <button
                        v-if="row.status === 'pending'"
                        type="button"
                        class="flex w-full rounded-[10px] px-2.5 py-2 text-left text-[13px] text-[var(--ep-text-2)] transition-colors duration-150 hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)]"
                        @click="reject(row.id, row.product_id); close()"
                    >
                        Rejeitar
                    </button>
                </template>
            </template>
        </ProductPartnersTable>

        <details v-if="programs.length" class="panel-card group overflow-hidden">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-4 transition-colors duration-150 hover:bg-[var(--ep-hover)] [&::-webkit-details-marker]:hidden">
                <span class="ep-section-title">
                    Programas por produto <span class="ml-1 font-normal tabular-nums text-[var(--ep-text-4)]">({{ programs.length }})</span>
                </span>
                <ChevronDown class="h-4 w-4 shrink-0 text-[var(--ep-text-3)] transition-transform duration-200 group-open:rotate-180" :stroke-width="1.75" aria-hidden="true" />
            </summary>
            <ul class="border-t border-[var(--ep-line)] px-2 py-2">
                <li
                    v-for="p in programs"
                    :key="p.product_id"
                    class="flex flex-wrap items-center justify-between gap-3 rounded-xl px-3 py-2.5 transition-colors duration-150 hover:bg-[var(--ep-hover)]"
                >
                    <div class="min-w-0">
                        <p class="truncate text-[13px] font-medium text-[var(--ep-text)]">{{ p.product_name }}</p>
                        <p class="mt-1 flex flex-wrap items-center gap-x-1.5 gap-y-1 text-[12px] tabular-nums text-[var(--ep-text-3)]">
                            <span :class="p.enabled ? 'ep-chip ep-chip--pos' : 'ep-chip text-[var(--ep-text-3)]'"><span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />{{ p.enabled ? 'Ativo' : 'Inativo' }}</span> · {{ p.affiliates_count }} aprovados ·
                            {{ p.pending_count }} pendentes
                        </p>
                    </div>
                    <Link
                        :href="`/produtos/${p.product_id}/edit?tab=afiliados`"
                        class="ep-btn-ghost !h-8 !px-3 text-[12.5px]"
                    >
                        Configurar
                        <ChevronRight class="h-3.5 w-3.5" :stroke-width="1.75" aria-hidden="true" />
                    </Link>
                </li>
            </ul>
        </details>
    </div>
</template>
