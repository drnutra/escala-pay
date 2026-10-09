<script setup>
import { computed } from 'vue';
import axios from 'axios';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import ProdutosTabs from '@/components/produtos/ProdutosTabs.vue';
import ProductPartnersTable from '@/components/produtos/ProductPartnersTable.vue';

defineOptions({ layout: LayoutInfoprodutor });

const props = defineProps({
    affiliates: { type: Object, required: true },
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
    window.location.reload();
}

async function reject(id, productId) {
    await axios.put(`/produtos/${productId}/affiliates/${id}`, { status: 'rejected' });
    window.location.reload();
}

function copyLink(url) {
    if (!url) return;
    navigator.clipboard?.writeText(url);
}
</script>

<template>
    <div class="space-y-5">
        <header class="min-w-0">
            <h1 class="ep-page-heading !text-[22px]">Afiliados</h1>
            <p class="mt-1 text-[13px] text-[var(--ep-text-3)]">Gerencie afiliados de todos os produtos.</p>
        </header>
        <ProdutosTabs />
        <ProductPartnersTable
            :rows="affiliateRows"
            empty-label="Nenhum afiliado encontrado."
        >
            <template #menu="{ row, close }">
                <template v-if="row">
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
    </div>
</template>
