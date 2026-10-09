<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import ProdutosTabs from '@/components/produtos/ProdutosTabs.vue';
import ProductPartnersTable from '@/components/produtos/ProductPartnersTable.vue';

defineOptions({ layout: LayoutInfoprodutor });

const props = defineProps({
    coproducers: { type: Object, required: true },
});

const coproducerStatusLabels = {
    active: 'Ativo',
    pending: 'Convite pendente',
    revoked: 'Revogado',
    expired: 'Expirado',
};

const coproducerRows = computed(() =>
    (props.coproducers?.data ?? []).map((c) => ({
        id: c.id,
        created_at: c.created_at,
        name: c.user?.name ?? null,
        email: c.user?.email ?? c.email ?? null,
        product_name: c.product_name ?? c.product?.name,
        commission_percent: c.commission_percent,
        status: c.status,
        product_id: c.product_id ?? c.product?.id,
    }))
);
</script>

<template>
    <div class="space-y-5">
        <div>
            <h1 class="ep-page-heading">Co-produtores</h1>
            <p class="mt-1 text-[13px] text-[var(--ep-text-3)]">Visão geral de todos os co-produtores da conta.</p>
        </div>
        <ProdutosTabs />
        <ProductPartnersTable
            :rows="coproducerRows"
            :status-labels="coproducerStatusLabels"
            empty-label="Nenhum co-produtor encontrado."
        >
            <template #menu="{ row, close }">
                <Link
                    v-if="row?.product_id"
                    :href="`/produtos/${row.product_id}/edit?tab=coproducao`"
                    class="flex w-full rounded-[10px] px-2.5 py-2 text-left text-[13px] text-[var(--ep-text-2)] transition-colors duration-150 hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)]"
                    @click="close()"
                >
                    Gerenciar no produto
                </Link>
            </template>
        </ProductPartnersTable>
    </div>
</template>
