<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import Button from '@/components/ui/Button.vue';
import ProdutosTabs from '@/components/produtos/ProdutosTabs.vue';
import CupomSidebar from '@/components/produtos/CupomSidebar.vue';
import { Pencil, Trash2, Ticket } from 'lucide-vue-next';
import { Plus, TicketPercent } from 'lucide-vue-next';

defineOptions({ layout: LayoutInfoprodutor });

const props = defineProps({
    cupons: { type: Array, default: () => [] },
    produtos: { type: Array, default: () => [] },
});

const sidebarOpen = ref(false);
const couponToEdit = ref(null);
const couponToDelete = ref(null);

function openNew() {
    couponToEdit.value = null;
    sidebarOpen.value = true;
}

function openEdit(c) {
    couponToEdit.value = c;
    sidebarOpen.value = true;
}

function closeSidebar() {
    sidebarOpen.value = false;
    couponToEdit.value = null;
}

function openDeleteModal(c) {
    couponToDelete.value = c;
}

function closeDeleteModal() {
    couponToDelete.value = null;
}

function confirmDestroy() {
    const c = couponToDelete.value;
    if (!c) return;
    router.delete(`/produtos/cupons/${c.id}`, { preserveScroll: true });
    closeDeleteModal();
}

function formatValor(c) {
    if (c.type === 'percent') return `${Number(c.value)}%`;
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(c.value ?? 0);
}

function formatDate(str) {
    if (!str) return '—';
    const d = new Date(str);
    return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

function usosText(c) {
    if (c.max_uses == null) return `${c.used_count} usos`;
    return `${c.used_count} / ${c.max_uses}`;
}
</script>

<template>
    <div class="space-y-5">
        <ProdutosTabs />
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-[12.5px] text-[var(--ep-text-3)]">
                <span class="font-semibold tabular-nums text-[var(--ep-text)]">{{ cupons.length }}</span>
                {{ cupons.length === 1 ? 'cupom' : 'cupons' }} ·
                <span class="font-semibold tabular-nums text-[var(--ep-text)]">{{ cupons.filter((c) => c.is_active).length }}</span>
                {{ cupons.filter((c) => c.is_active).length === 1 ? 'ativo' : 'ativos' }} ·
                <span class="font-semibold tabular-nums text-[var(--ep-text)]">{{ cupons.reduce((acc, c) => acc + (Number(c.used_count) || 0), 0) }}</span>
                {{ cupons.reduce((acc, c) => acc + (Number(c.used_count) || 0), 0) === 1 ? 'uso' : 'usos' }}
            </p>
            <Button @click="openNew">
                <Plus class="h-4 w-4" :stroke-width="2" aria-hidden="true" />
                Novo cupom
            </Button>
        </div>

        <section class="panel-card ep-data overflow-hidden" aria-labelledby="cupons-title">
            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 px-4 pb-3.5 pt-4">
                <h2 id="cupons-title" class="ep-section-title">Cupons de desconto</h2>
                <span class="text-[12px] text-[var(--ep-text-4)]">Aplicados no checkout pelo código</span>
            </div>
            <div class="overflow-x-auto">
                <table class="ep-table min-w-full">
                    <thead>
                        <tr>
                            <th scope="col">
                                Código
                            </th>
                            <th scope="col">
                                Tipo
                            </th>
                            <th scope="col" class="ep-num">
                                Valor
                            </th>
                            <th scope="col">
                                Produto
                            </th>
                            <th scope="col" class="ep-num">
                                Usos
                            </th>
                            <th scope="col">
                                Validade
                            </th>
                            <th scope="col">
                                Ativo
                            </th>
                            <th scope="col" class="relative w-[88px]">
                                <span class="sr-only">Ações</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="c in cupons"
                            :key="c.id"
                            class="group"
                        >
                            <td class="whitespace-nowrap">
                                <span class="inline-flex items-center gap-2 font-mono text-[12.5px] font-medium tracking-[0.02em] text-[var(--ep-text)]">
                                    <TicketPercent class="h-4 w-4 shrink-0 text-[var(--ep-text-4)]" :stroke-width="1.75" aria-hidden="true" />
                                    {{ c.code }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap">
                                <span class="ep-chip">
                                    {{ c.type === 'percent' ? 'Percentual' : 'Fixo' }}
                                </span>
                            </td>
                            <td class="ep-num font-semibold tracking-[-0.01em] text-[var(--ep-text)]">
                                {{ formatValor(c) }}
                            </td>
                            <td class="max-w-[240px] truncate whitespace-nowrap text-[var(--ep-text-2)]">
                                {{ c.product_name ?? 'Todos' }}
                            </td>
                            <td class="ep-num text-[var(--ep-text-2)]">
                                {{ usosText(c) }}
                            </td>
                            <td class="whitespace-nowrap tabular-nums text-[12.5px] text-[var(--ep-text-3)]">
                                {{ formatDate(c.valid_from) }} – {{ formatDate(c.valid_until) }}
                            </td>
                            <td class="whitespace-nowrap">
                                <span
                                    :class="[
                                        'ep-chip',
                                        c.is_active
                                            ? 'ep-chip--pos'
                                            : 'text-[var(--ep-text-3)]',
                                    ]"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                                    {{ c.is_active ? 'Sim' : 'Não' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap">
                                <div class="flex items-center justify-end gap-0.5">
                                    <button
                                        type="button"
                                        class="ep-btn-ghost ep-btn-icon !h-8 !w-8 !rounded-[10px] text-[var(--ep-text-3)]"
                                        aria-label="Editar cupom"
                                        @click="openEdit(c)"
                                    >
                                        <Pencil class="h-4 w-4" :stroke-width="1.75" />
                                    </button>
                                    <button
                                        type="button"
                                        class="ep-btn-ghost ep-btn-icon !h-8 !w-8 !rounded-[10px] text-[var(--ep-text-3)] hover:!bg-[var(--ep-neg-bg)] hover:!text-[var(--ep-neg)]"
                                        aria-label="Excluir cupom"
                                        @click="openDeleteModal(c)"
                                    >
                                        <Trash2 class="h-4 w-4" :stroke-width="1.75" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div
                v-if="!cupons.length"
                class="ep-empty border-t border-[var(--ep-line)] py-14"
            >
                <span class="ep-kpi__icon mb-2 h-10 w-10 rounded-[12px]" aria-hidden="true">
                    <Ticket class="h-5 w-5" :stroke-width="1.75" />
                </span>
                <p class="ep-empty__title">Nenhum cupom ainda.</p>
                <p class="ep-empty__text">Crie um código de desconto percentual ou de valor fixo e defina para quais produtos ele vale.</p>
                <Button class="mt-3" @click="openNew">
                    <Plus class="h-4 w-4" :stroke-width="2" aria-hidden="true" />
                    Criar primeiro cupom
                </Button>
            </div>
        </section>
    </div>

    <!-- Modal de confirmação de exclusão -->
    <Teleport to="body">
        <div
            v-if="couponToDelete"
            class="fixed inset-0 z-[100002] flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="delete-cupom-title"
        >
            <div
                class="ep-scrim fixed inset-0"
                aria-hidden="true"
                @click="closeDeleteModal"
            />
            <div
                class="ep-modal relative w-full max-w-sm p-6"
            >
                <span class="mb-4 inline-flex h-10 w-10 items-center justify-center rounded-[12px] border border-[color-mix(in_oklab,var(--ep-neg)_35%,transparent)] bg-[var(--ep-neg-bg)] text-[var(--ep-neg)]" aria-hidden="true">
                    <Trash2 class="h-[18px] w-[18px]" :stroke-width="1.75" />
                </span>
                <h2 id="delete-cupom-title" class="text-[16px] font-semibold tracking-[-0.02em] text-[var(--ep-text)]">
                    Excluir cupom?
                </h2>
                <p class="mt-2 text-[13px] leading-relaxed text-[var(--ep-text-3)]">
                    Tem certeza que deseja excluir o cupom
                    <strong class="font-mono font-medium text-[var(--ep-text)]">"{{ couponToDelete?.code }}"</strong>?
                    Esta ação não pode ser desfeita.
                </p>
                <div class="mt-6 flex justify-end gap-2">
                    <Button variant="outline" @click="closeDeleteModal">
                        Cancelar
                    </Button>
                    <Button variant="destructive" @click="confirmDestroy">
                        Excluir
                    </Button>
                </div>
            </div>
        </div>
    </Teleport>

    <CupomSidebar
        :open="sidebarOpen"
        :produtos="produtos"
        :coupon="couponToEdit"
        @close="closeSidebar"
        @success="closeSidebar"
    />
</template>
