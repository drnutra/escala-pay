<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import Button from '@/components/ui/Button.vue';
import MoneyAmount from '@/components/ui/MoneyAmount.vue';
import ProdutosTabs from '@/components/produtos/ProdutosTabs.vue';
import ProdutoCreateSidebar from '@/components/produtos/ProdutoCreateSidebar.vue';
import PluginRuntimeMount from '@/components/plugins/PluginRuntimeMount.vue';
import PluginRenderZone from '@/components/plugins/PluginRenderZone.vue';
import {
    MoreVertical,
    Pencil,
    Copy,
    Trash2,
    Package,
    ExternalLink,
    Download,
    Upload,
} from 'lucide-vue-next';
import ProductPackageModal from '@/components/produtos/ProductPackageModal.vue';
import { Plus, ArrowUpRight } from 'lucide-vue-next';

defineOptions({ layout: LayoutInfoprodutor });

const props = defineProps({
    produtos: { type: [Array, Object], default: () => [] },
    productTypes: { type: Array, default: () => [] },
    billingTypes: { type: Array, default: () => [] },
    exchange_rates: { type: Object, default: () => ({ brl_eur: 0.16, brl_usd: 0.18 }) },
    plugin_card_actions: { type: [Object, Array], default: () => [] },
    plugin_form_sections: { type: Array, default: () => [] },
});

const produtosList = computed(() => props.produtos?.data ?? (Array.isArray(props.produtos) ? props.produtos : []));

const sidebarOpen = ref(false);
const openMenuId = ref(null);
const productToDelete = ref(null);
const packageModalOpen = ref(false);
const packageModalMode = ref('import');
const packageProduct = ref(null);

function openImportModal() {
    packageModalMode.value = 'import';
    packageProduct.value = null;
    packageModalOpen.value = true;
}

function openExportModal(p) {
    closeMenu();
    packageModalMode.value = 'export';
    packageProduct.value = p;
    packageModalOpen.value = true;
}

function openSidebar() {
    sidebarOpen.value = true;
}

function closeSidebar() {
    sidebarOpen.value = false;
}

function toggleMenu(id) {
    openMenuId.value = openMenuId.value === id ? null : id;
}

function closeMenu() {
    openMenuId.value = null;
}

function handleClickOutside(event) {
    if (openMenuId.value == null) return;
    const menuEl = document.querySelector(`[data-product-menu="${openMenuId.value}"]`);
    if (menuEl && !menuEl.contains(event.target)) {
        closeMenu();
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

function formatBRL(value) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value ?? 0);
}

function duplicate(p) {
    router.post(`/produtos/${p.id}/duplicate`, {}, { preserveScroll: true });
    closeMenu();
}

function openDeleteModal(p) {
    closeMenu();
    productToDelete.value = p;
}

function closeDeleteModal() {
    productToDelete.value = null;
}

function confirmDestroy() {
    const p = productToDelete.value;
    if (!p) return;
    router.delete(`/produtos/${p.id}`, { preserveScroll: true });
    closeDeleteModal();
}

function pluginActions(productId) {
    const raw = props.plugin_card_actions;
    if (Array.isArray(raw)) {
        return raw.filter((a) => !a?.product_id || String(a.product_id) === String(productId));
    }
    return raw?.[productId] ?? raw?.[String(productId)] ?? [];
}
</script>

<template>
    <div class="space-y-5">
        <ProdutosTabs />

        <!-- Barra de ações -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-[12.5px] text-[var(--ep-text-3)]">
                <span class="font-semibold tabular-nums text-[var(--ep-text)]">{{ produtos?.total ?? produtosList.length }}</span>
                {{ (produtos?.total ?? produtosList.length) === 1 ? 'produto' : 'produtos' }} no catálogo
            </p>
            <div class="flex items-center gap-2">
                <Button variant="outline" @click="openImportModal">
                    <Upload class="h-4 w-4" :stroke-width="1.75" />
                    Importar produto
                </Button>
                <Button @click="openSidebar">
                    <Plus class="h-4 w-4" :stroke-width="2" aria-hidden="true" />
                    Novo produto
                </Button>
            </div>
        </div>
        <PluginRenderZone zone="produtos.index.after_toolbar" />

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="p in produtosList"
                :key="p.id"
                class="panel-card group relative flex gap-4 p-3 transition-[border-color,box-shadow] duration-200 hover:border-[var(--ep-line-strong)] focus-within:z-20 [&:has([data-product-menu]>div:not([style*=none]))]:z-20"
            >
                <!-- Imagem: clicável → edição (raio concêntrico ao card: 20px − 12px de respiro) -->
                <Link
                    :href="`/produtos/${p.id}/edit`"
                    class="block shrink-0 self-start"
                >
                    <div class="relative h-[92px] w-[92px] shrink-0 overflow-hidden rounded-[8px] border border-[var(--ep-line)] bg-[var(--ep-card-2)] shadow-[inset_0_1px_0_rgba(255,255,255,0.08)]">
                        <img
                            v-if="p.image_url"
                            :src="p.image_url"
                            :alt="p.name"
                            class="absolute inset-0 h-full w-full object-cover object-center transition-transform duration-300 ease-[cubic-bezier(0.23,1,0.32,1)] group-hover:scale-[1.04]"
                        />
                        <div
                            v-else
                            class="flex h-full w-full items-center justify-center bg-[linear-gradient(145deg,color-mix(in_oklab,var(--ep-accent)_16%,transparent),color-mix(in_oklab,var(--ep-accent-2)_10%,transparent))] text-[var(--ep-text-4)]"
                        >
                            <Package class="h-6 w-6" :stroke-width="1.75" aria-hidden="true" />
                        </div>
                    </div>
                </Link>

                <!-- Conteúdo -->
                <div class="flex min-w-0 flex-1 flex-col py-0.5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1 pt-0.5">
                            <Link
                                :href="`/produtos/${p.id}/edit`"
                                class="line-clamp-2 block text-[14px] font-medium leading-[1.3] tracking-[-0.01em] text-[var(--ep-text)] transition-colors duration-150 hover:text-[var(--ep-accent)]"
                            >
                                {{ p.name }}
                            </Link>
                            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                <span
                                    :class="[
                                        'ep-chip',
                                        p.is_active
                                            ? 'ep-chip--pos'
                                            : 'text-[var(--ep-text-3)]',
                                    ]"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                                    {{ p.is_active ? 'Ativo' : 'Inativo' }}
                                </span>
                                <span class="ep-chip">
                                    {{ p.type_label }}
                                </span>
                                <span class="ep-chip text-[var(--ep-text-3)]">
                                    {{ p.billing_type_label ?? 'Pagamento único' }}
                                </span>
                            </div>
                        </div>
                        <div class="relative -mr-1 -mt-1 shrink-0" :data-product-menu="p.id">
                            <button
                                type="button"
                                class="ep-btn-ghost ep-btn-icon !h-8 !w-8 !rounded-[10px] text-[var(--ep-text-3)]"
                                aria-label="Abrir menu"
                                aria-expanded="openMenuId === p.id"
                                @click="toggleMenu(p.id)"
                            >
                                <MoreVertical class="h-4 w-4" :stroke-width="1.75" />
                            </button>
                            <div
                                v-show="openMenuId === p.id"
                                class="absolute right-0 top-full z-50 mt-1.5 w-52 rounded-[14px] border border-[var(--ep-glass-border)] bg-[var(--ep-drawer)] p-1 shadow-[var(--ep-shadow-pop)] backdrop-blur-2xl backdrop-saturate-150"
                            >
                                <Link
                                    :href="`/produtos/${p.id}/edit`"
                                    class="flex w-full items-center gap-2.5 rounded-[10px] px-2.5 py-2 text-left text-[13px] text-[var(--ep-text-2)] transition-colors duration-150 hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)]"
                                    @click="closeMenu"
                                >
                                    <Pencil class="h-4 w-4 shrink-0 text-[var(--ep-text-3)]" :stroke-width="1.75" />
                                    Editar
                                </Link>
                                <button
                                    type="button"
                                    class="flex w-full items-center gap-2.5 rounded-[10px] px-2.5 py-2 text-left text-[13px] text-[var(--ep-text-2)] transition-colors duration-150 hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)]"
                                    @click="duplicate(p)"
                                >
                                    <Copy class="h-4 w-4 shrink-0 text-[var(--ep-text-3)]" :stroke-width="1.75" />
                                    Duplicar
                                </button>
                                <button
                                    type="button"
                                    class="flex w-full items-center gap-2.5 rounded-[10px] px-2.5 py-2 text-left text-[13px] text-[var(--ep-text-2)] transition-colors duration-150 hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)]"
                                    @click="openExportModal(p)"
                                >
                                    <Download class="h-4 w-4 shrink-0 text-[var(--ep-text-3)]" :stroke-width="1.75" />
                                    Exportar
                                </button>
                                <div class="ep-divider my-1" aria-hidden="true" />
                                <button
                                    type="button"
                                    class="flex w-full items-center gap-2.5 rounded-[10px] px-2.5 py-2 text-left text-[13px] text-[var(--ep-neg)] transition-colors duration-150 hover:bg-[var(--ep-neg-bg)]"
                                    @click="openDeleteModal(p)"
                                >
                                    <Trash2 class="h-4 w-4 shrink-0" :stroke-width="1.75" />
                                    Excluir
                                </button>
                                <template v-for="(action, actIdx) in pluginActions(p.id)" :key="`plugin-${p.id}-${actIdx}`">
                                    <div
                                        v-if="action.ui_mode === 'runtime'"
                                        class="px-2.5 py-2"
                                        @click="closeMenu"
                                    >
                                        <PluginRuntimeMount :item="action" :context="{ product: p }" />
                                    </div>
                                    <a
                                        v-else-if="action.href"
                                        :href="action.href"
                                        class="flex w-full items-center gap-2.5 rounded-[10px] px-2.5 py-2 text-left text-[13px] text-[var(--ep-text-2)] transition-colors duration-150 hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)]"
                                        @click="closeMenu"
                                    >
                                        <ExternalLink v-if="!action.icon" class="h-4 w-4 shrink-0 text-[var(--ep-text-3)]" :stroke-width="1.75" />
                                        <component v-else :is="action.icon" class="h-4 w-4 shrink-0 text-[var(--ep-text-3)]" />
                                        {{ action.label }}
                                    </a>
                                    <span v-else class="mt-1 block border-t border-[var(--ep-line)] px-2.5 pb-1 pt-2 text-[11.5px] text-[var(--ep-text-4)]">
                                        {{ action.label }}
                                    </span>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="mt-auto flex items-end justify-between gap-3 pt-3">
                        <MoneyAmount :value="p.price_brl ?? p.price" size="md" class="tracking-[-0.02em]" />
                        <a
                            v-if="p.checkout_slug"
                            :href="`/c/${p.checkout_slug}`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="-mb-0.5 -mr-0.5 inline-flex shrink-0 items-center gap-1 rounded-[8px] px-1.5 py-1 text-[12px] font-medium text-[var(--ep-text-3)] transition-colors duration-150 hover:bg-[var(--ep-hover)] hover:text-[var(--ep-accent)]"
                        >
                            Ver checkout
                            <ArrowUpRight class="h-3.5 w-3.5" :stroke-width="1.75" aria-hidden="true" />
                        </a>
                    </div>
                </div>
            </article>
        </div>

        <nav
            v-if="produtos?.links?.length > 3"
            class="flex justify-center"
            aria-label="Paginação"
        >
            <div class="ep-tabs max-w-full overflow-x-auto">
                <a
                    v-for="link in produtos.links"
                    :key="link.label"
                    :href="link.url"
                    :aria-current="link.active ? 'page' : undefined"
                    :aria-disabled="!link.url"
                    :class="[
                        'ep-tab min-w-[30px] justify-center tabular-nums',
                        link.active
                            ? 'ep-tab--active'
                            : link.url
                              ? ''
                              : 'pointer-events-none cursor-not-allowed opacity-40',
                    ]"
                    v-html="link.label"
                    @click.prevent="link.url && router.visit(link.url, { preserveState: true })"
                />
            </div>
        </nav>

        <div
            v-if="!produtosList.length"
            class="panel-card ep-empty py-16"
        >
            <span class="ep-kpi__icon mb-2 h-10 w-10 rounded-[12px]" aria-hidden="true">
                <Package class="h-5 w-5" :stroke-width="1.75" />
            </span>
            <p class="ep-empty__title">Nenhum produto ainda.</p>
            <p class="ep-empty__text">Crie seu primeiro produto para gerar o checkout e começar a vender — ou importe um pacote exportado de outra conta.</p>
            <Button class="mt-3" @click="openSidebar">
                <Plus class="h-4 w-4" :stroke-width="2" aria-hidden="true" />
                Criar primeiro produto
            </Button>
        </div>
    </div>

    <!-- Modal de confirmação de exclusão -->
    <Teleport to="body">
        <div
            v-if="productToDelete"
            class="fixed inset-0 z-[100002] flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="delete-modal-title"
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
                <h2 id="delete-modal-title" class="text-[16px] font-semibold tracking-[-0.02em] text-[var(--ep-text)]">
                    Excluir produto?
                </h2>
                <p class="mt-2 text-[13px] leading-relaxed text-[var(--ep-text-3)]">
                    Tem certeza que deseja excluir
                    <strong class="font-medium text-[var(--ep-text)]">"{{ productToDelete?.name }}"</strong>?
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

    <ProductPackageModal
        :open="packageModalOpen"
        :mode="packageModalMode"
        :product="packageProduct"
        @update:open="packageModalOpen = $event"
    />

    <ProdutoCreateSidebar
        :open="sidebarOpen"
        :product-types="productTypes"
        :billing-types="billingTypes"
        :exchange-rates="exchange_rates"
        :plugin-form-sections="plugin_form_sections"
        @close="closeSidebar"
        @success="closeSidebar"
    />
</template>
