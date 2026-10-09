<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import ProdutosTabs from '@/components/produtos/ProdutosTabs.vue';
import AlunoDetailSidebar from '@/components/alunos/AlunoDetailSidebar.vue';
import Button from '@/components/ui/Button.vue';
import HorizontalScrollTabs from '@/components/ui/HorizontalScrollTabs.vue';
import Checkbox from '@/components/ui/Checkbox.vue';
import { Users, BookOpen, Package, UserPlus, Plus, ChevronDown, X, Upload, Download, Search } from 'lucide-vue-next';
import axios from 'axios';

defineOptions({ layout: LayoutInfoprodutor });

const props = defineProps({
    alunos: { type: [Array, Object], default: () => [] },
    produtos: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    filter: { type: String, default: 'todos' },
    product_ids_filter: { type: Array, default: () => [] },
    q: { type: String, default: '' },
});

const sidebarOpen = ref(false);
const selectedAluno = ref(null);
const novoAlunoModalOpen = ref(false);
const importModalOpen = ref(false);
const productFilterOpen = ref(false);
const novoAlunoForm = ref({
    name: '',
    email: '',
    password: '',
    product_ids: [],
    send_access_email: true,
});
const savingNovo = ref(false);
const importForm = ref({ file: null, product_ids: [], send_access_email: true });
const importing = ref(false);
const toast = ref({ message: null, type: null });
let toastTimer = null;
let searchTimer = null;

const search = ref(props.q ?? '');

const filterOptions = [
    { value: 'todos', label: 'Todos' },
    { value: 'novos_30', label: 'Novos 30 dias' },
];

const alunosList = computed(() => props.alunos?.data ?? (Array.isArray(props.alunos) ? props.alunos : []));

const selectedProdutosLabels = computed(() => {
    const ids = props.product_ids_filter;
    return props.produtos.filter((p) => ids.includes(p.id)).map((p) => ({ id: p.id, name: p.name }));
});

function setFilter(value) {
    applyQuery({ filter: value });
}

function setProductFilter(ids) {
    applyQuery({ product_ids: ids });
}

function buildQuery(overrides = {}) {
    const q = {
        filter: props.filter,
        product_ids: props.product_ids_filter,
        q: search.value,
        ...overrides,
    };

    const cleaned = {};
    Object.entries(q).forEach(([k, v]) => {
        if (v === null || v === undefined) return;
        if (Array.isArray(v) && v.length === 0) return;
        if (typeof v === 'string' && v.trim() === '') return;
        cleaned[k] = v;
    });
    return cleaned;
}

function applyQuery(overrides = {}) {
    router.get('/produtos/alunos', buildQuery(overrides), { preserveState: true, preserveScroll: true, replace: true });
}

function onSearchInput() {
    const q = (search.value ?? '').trim();
    if (q !== '' && q.length < 3) {
        if (searchTimer) clearTimeout(searchTimer);
        searchTimer = null;
        return;
    }
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        applyQuery();
        searchTimer = null;
    }, 600);
}

function toggleProductFilter(id) {
    const current = [...(props.product_ids_filter ?? [])];
    const idx = current.indexOf(id);
    if (idx >= 0) {
        current.splice(idx, 1);
    } else {
        current.push(id);
    }
    setProductFilter(current);
}

function removeProductFilter(id) {
    const current = [...(props.product_ids_filter ?? [])].filter((x) => x !== id);
    setProductFilter(current);
}

function openDetail(a) {
    selectedAluno.value = a;
    sidebarOpen.value = true;
}

function closeSidebar() {
    sidebarOpen.value = false;
    selectedAluno.value = null;
}

function handleAlunoUpdated(updated) {
    const list = [...alunosList.value];
    const idx = list.findIndex((a) => a.id === updated.id);
    if (idx >= 0) {
        list[idx] = { ...list[idx], ...updated };
        router.reload({ only: ['alunos'], preserveState: false });
    }
}

function handleAlunoDeleted(id) {
    closeSidebar();
    router.reload({ only: ['alunos', 'stats'], preserveState: false });
}

function openNovoAluno() {
    novoAlunoForm.value = { name: '', email: '', password: '', product_ids: [], send_access_email: true };
    novoAlunoModalOpen.value = true;
}

function closeNovoAluno() {
    novoAlunoModalOpen.value = false;
}

function openImportModal() {
    importForm.value = { file: null, product_ids: [], send_access_email: true };
    importModalOpen.value = true;
}

function closeImportModal() {
    importModalOpen.value = false;
}

function onImportFileChange(e) {
    const f = e.target?.files?.[0];
    importForm.value.file = f || null;
}

function toggleImportProduct(id) {
    const ids = importForm.value.product_ids;
    if (ids.includes(id)) {
        importForm.value.product_ids = ids.filter((x) => x !== id);
    } else {
        importForm.value.product_ids = [...ids, id];
    }
}

async function saveImport() {
    if (!importForm.value.file) {
        showToast('Selecione um arquivo CSV.', 'error');
        return;
    }
    if (!importForm.value.product_ids?.length) {
        showToast('Selecione ao menos um produto para dar acesso.', 'error');
        return;
    }
    importing.value = true;
    try {
        const formData = new FormData();
        formData.append('file', importForm.value.file);
        formData.append('send_access_email', importForm.value.send_access_email ? '1' : '0');
        importForm.value.product_ids.forEach((id) => formData.append('product_ids[]', id));

        const { data } = await axios.post('/produtos/alunos/import', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        showToast(data.message ?? 'Importação concluída.', 'success');
        if (data.errors?.length) {
            showToast(data.errors.slice(0, 3).join(' '), 'error');
        }
        closeImportModal();
        router.reload({ only: ['alunos', 'stats'], preserveState: false });
    } catch (err) {
        showToast(
            err.response?.data?.message ?? err.response?.data?.errors?.file?.[0] ?? 'Erro na importação. Verifique o formato do CSV.',
            'error'
        );
    } finally {
        importing.value = false;
    }
}

function toggleNovoProduct(id) {
    const ids = novoAlunoForm.value.product_ids;
    if (ids.includes(id)) {
        novoAlunoForm.value.product_ids = ids.filter((x) => x !== id);
    } else {
        novoAlunoForm.value.product_ids = [...ids, id];
    }
}

async function saveNovoAluno() {
    if (!novoAlunoForm.value.name?.trim() || !novoAlunoForm.value.email?.trim() || !novoAlunoForm.value.password) {
        showToast('Preencha nome, e-mail e senha.', 'error');
        return;
    }
    savingNovo.value = true;
    try {
        const { data } = await axios.post('/produtos/alunos', {
            ...novoAlunoForm.value,
            send_access_email: novoAlunoForm.value.send_access_email ?? true,
        });
        showToast(data.message ?? 'Aluno cadastrado com sucesso.', 'success');
        closeNovoAluno();
        router.reload({ only: ['alunos', 'stats'], preserveState: false });
    } catch (err) {
        showToast(
            err.response?.data?.message ?? err.response?.data?.errors?.email?.[0] ?? 'Erro ao cadastrar. Tente novamente.',
            'error'
        );
    } finally {
        savingNovo.value = false;
    }
}

function showToast(message, type) {
    toast.value = { message, type };
    if (toastTimer) clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        toast.value = { message: null, type: null };
        toastTimer = null;
    }, 4000);
}

function handleClickOutside(event) {
    if (productFilterOpen.value) {
        const el = document.querySelector('[data-product-filter]');
        if (el && !el.contains(event.target)) {
            productFilterOpen.value = false;
        }
    }
}

function displayNumber(value) {
    return String(value ?? 0);
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    if (toastTimer) clearTimeout(toastTimer);
    if (searchTimer) clearTimeout(searchTimer);
});
</script>

<template>
    <div class="space-y-5">
        <ProdutosTabs />

        <!-- Métricas -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="panel-card ep-glow-card ep-kpi">
                <div class="flex items-start justify-between gap-3">
                    <span class="ep-kpi__label">Total de alunos</span>
                    <span class="ep-kpi__icon" aria-hidden="true"><Users class="h-4 w-4" :stroke-width="1.75" /></span>
                </div>
                <p class="ep-kpi__value !text-[28px]">{{ displayNumber(stats.total_alunos) }}</p>
                <span class="ep-kpi__meta">com acesso à área de membros</span>
            </div>
            <div class="panel-card ep-kpi">
                <div class="flex items-start justify-between gap-3">
                    <span class="ep-kpi__label">Total de inscrições</span>
                    <span class="ep-kpi__icon" aria-hidden="true"><BookOpen class="h-4 w-4" :stroke-width="1.75" /></span>
                </div>
                <p class="ep-kpi__value">{{ displayNumber(stats.total_inscricoes) }}</p>
                <span class="ep-kpi__meta">acessos somando todos os produtos</span>
            </div>
            <div class="panel-card ep-kpi">
                <div class="flex items-start justify-between gap-3">
                    <span class="ep-kpi__label">Produtos com alunos</span>
                    <span class="ep-kpi__icon" aria-hidden="true"><Package class="h-4 w-4" :stroke-width="1.75" /></span>
                </div>
                <p class="ep-kpi__value">{{ displayNumber(stats.produtos_ativos) }}</p>
                <span class="ep-kpi__meta">com ao menos um aluno</span>
            </div>
            <div class="panel-card ep-kpi">
                <div class="flex items-start justify-between gap-3">
                    <span class="ep-kpi__label">Novos (30 dias)</span>
                    <span
                        class="ep-kpi__icon"
                        style="color: var(--ep-pos); background: var(--ep-pos-bg); border-color: color-mix(in oklab, var(--ep-pos) 28%, transparent); box-shadow: none"
                        aria-hidden="true"
                    ><UserPlus class="h-4 w-4" :stroke-width="1.75" /></span>
                </div>
                <p class="ep-kpi__value">{{ displayNumber(stats.alunos_novos_30dias) }}</p>
                <span class="ep-kpi__meta">cadastrados no último mês</span>
            </div>
        </div>

        <!-- Abas de filtro + Filtro por produto + Novo aluno -->
        <div class="flex min-w-0 flex-wrap items-start justify-between gap-3">
            <div class="flex min-w-0 max-w-full flex-col flex-wrap gap-3 sm:flex-row sm:flex-nowrap sm:items-center">
                <HorizontalScrollTabs aria-label="Filtrar alunos" nav-class="ep-tabs">
                    <button
                        v-for="opt in filterOptions"
                        :key="opt.value"
                        type="button"
                        :aria-current="filter === opt.value ? 'true' : undefined"
                        :class="['ep-tab shrink-0', filter === opt.value ? 'ep-tab--active' : '']"
                        @click="setFilter(opt.value)"
                    >
                        {{ opt.label }}
                    </button>
                </HorizontalScrollTabs>
                <div class="relative w-full sm:w-72">
                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--ep-text-4)]" :stroke-width="1.75" />
                    <input
                        v-model="search"
                        type="text"
                        name="alunos_search"
                        autocomplete="off"
                        autocapitalize="off"
                        autocorrect="off"
                        spellcheck="false"
                        class="ep-input !pl-9 !pr-9"
                        placeholder="Buscar aluno por nome ou e-mail..."
                        @input="onSearchInput"
                    />
                    <button
                        v-if="search"
                        type="button"
                        class="absolute right-2 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-md text-[var(--ep-text-4)] transition-colors duration-150 hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)]"
                        aria-label="Limpar busca"
                        @click="search = ''; applyQuery()"
                    >
                        <X class="h-4 w-4" :stroke-width="1.75" />
                    </button>
                </div>
                <div class="relative shrink-0" data-product-filter>
                    <button
                        type="button"
                        class="ep-btn-secondary"
                        :class="product_ids_filter?.length ? '!border-[color-mix(in_oklab,var(--ep-accent)_45%,transparent)] !text-[var(--ep-accent)]' : ''"
                        aria-expanded="productFilterOpen"
                        @click="productFilterOpen = !productFilterOpen"
                    >
                        <Package class="h-4 w-4 shrink-0 text-[var(--ep-text-3)]" :stroke-width="1.75" />
                        Produtos
                        <span v-if="product_ids_filter?.length" class="ep-chip ep-chip--accent !h-[18px] min-w-[18px] justify-center !px-1.5 text-[10.5px] tabular-nums">
                            {{ product_ids_filter.length }}
                        </span>
                        <ChevronDown class="h-4 w-4 shrink-0 text-[var(--ep-text-4)] transition-transform duration-200" :class="productFilterOpen && 'rotate-180'" :stroke-width="1.75" />
                    </button>
                    <div
                        v-show="productFilterOpen"
                        class="absolute left-0 top-full z-50 mt-2 max-h-64 w-64 overflow-y-auto rounded-2xl border border-[var(--ep-glass-border)] bg-[var(--ep-drawer)] p-1.5 text-left shadow-[var(--ep-shadow-pop)] backdrop-blur-2xl"
                    >
                        <div v-for="p in produtos" :key="p.id">
                            <label class="flex cursor-pointer items-center gap-2.5 rounded-[10px] px-2 py-1.5 text-left transition-colors duration-150 hover:bg-[var(--ep-hover)]">
                                <span class="shrink-0 w-fit">
                                    <Checkbox
                                        :model-value="product_ids_filter?.includes(p.id)"
                                        @update:model-value="toggleProductFilter(p.id)"
                                    />
                                </span>
                                <span class="flex-1 truncate text-left text-[13px] text-[var(--ep-text)]">{{ p.name }}</span>
                            </label>
                        </div>
                        <p v-if="!produtos.length" class="px-3 py-2 text-[12.5px] text-[var(--ep-text-4)]">
                            Nenhum produto
                        </p>
                    </div>
                </div>
                <div v-if="selectedProdutosLabels.length" class="flex w-full flex-wrap justify-start gap-1.5 sm:w-auto">
                    <span
                        v-for="p in selectedProdutosLabels"
                        :key="p.id"
                        class="ep-chip ep-chip--accent !pr-1"
                    >
                        {{ p.name }}
                        <button
                            type="button"
                            class="flex h-4 w-4 items-center justify-center rounded-full transition-colors duration-150 hover:bg-[color-mix(in_oklab,var(--ep-accent)_22%,transparent)]"
                            aria-label="Remover filtro"
                            @click="removeProductFilter(p.id)"
                        >
                            <X class="h-3 w-3" :stroke-width="2" />
                        </button>
                    </span>
                </div>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" @click="openImportModal">
                    <Upload class="h-4 w-4" :stroke-width="1.75" />
                    Importar
                </Button>
                <Button variant="primary" @click="openNovoAluno">
                    <Plus class="h-4 w-4" :stroke-width="1.75" />
                    Novo aluno
                </Button>
            </div>
        </div>

        <!-- Lista de alunos (mobile) -->
        <div v-if="alunosList.length" class="panel-card ep-data overflow-hidden sm:hidden">
            <div
                v-for="a in alunosList"
                :key="a.id"
                class="flex cursor-pointer items-center gap-3 border-b border-[var(--ep-line)] px-4 py-3 transition-colors duration-150 last:border-b-0 hover:bg-[var(--ep-hover)]"
                role="button"
                tabindex="0"
                @click="openDetail(a)"
                @keydown.enter.prevent="openDetail(a)"
                @keydown.space.prevent="openDetail(a)"
            >
                <span v-avatar="a.name" class="ep-avatar shrink-0" aria-hidden="true">{{ (a.name || '?').trim().charAt(0).toUpperCase() }}</span>
                <div class="min-w-0 flex-1">
                    <p class="break-words text-[13px] font-medium leading-snug text-[var(--ep-text)]">
                        {{ a.name }}
                    </p>
                    <p class="mt-0.5 break-words text-[12px] leading-snug text-[var(--ep-text-3)]">
                        {{ a.email }}
                    </p>
                </div>
                <div class="shrink-0 text-right">
                    <p class="text-[15px] font-semibold tabular-nums text-[var(--ep-text)]">
                        {{ a.products_count ?? 0 }}
                    </p>
                    <p class="text-[11px] text-[var(--ep-text-4)]">
                        Produtos
                    </p>
                </div>
            </div>
        </div>

        <!-- Tabela de alunos -->
        <div
            class="panel-card ep-data hidden overflow-hidden sm:block"
        >
            <div class="overflow-x-auto">
                <table class="ep-table">
                    <thead>
                        <tr>
                            <th>
                                Nome
                            </th>
                            <th>
                                E-mail
                            </th>
                            <th class="ep-num">
                                Produtos
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="a in alunosList"
                            :key="a.id"
                            class="cursor-pointer"
                            @click="openDetail(a)"
                        >
                            <td class="whitespace-nowrap">
                                <span class="flex items-center gap-3">
                                    <span v-avatar="a.name" class="ep-avatar shrink-0" aria-hidden="true">{{ (a.name || '?').trim().charAt(0).toUpperCase() }}</span>
                                    <span class="font-medium text-[var(--ep-text)]">{{ a.name }}</span>
                                </span>
                            </td>
                            <td class="whitespace-nowrap text-[var(--ep-text-2)]">
                                {{ a.email }}
                            </td>
                            <td class="ep-num">
                                <span class="ep-chip tabular-nums">{{ a.products_count ?? 0 }}</span>
                            </td>
                        </tr>
                        <tr v-if="!alunosList.length" class="hover:!bg-transparent">
                            <td colspan="3" class="!p-0">
                                <div class="ep-empty">
                                    <p class="ep-empty__title">Nenhum aluno com acesso ainda.</p>
                                    <p class="ep-empty__text">Cadastre um aluno ou importe um CSV para liberar o acesso aos seus produtos.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div
            v-if="!alunosList.length"
            class="panel-card ep-empty sm:hidden"
        >
            <p class="ep-empty__title">Nenhum aluno com acesso ainda.</p>
            <p class="ep-empty__text">Cadastre um aluno ou importe um CSV para liberar o acesso.</p>
        </div>

        <!-- Paginação -->
        <nav
            v-if="alunos?.links?.length > 3"
            class="flex flex-wrap items-center justify-center gap-1"
            aria-label="Paginação"
        >
            <a
                v-for="link in alunos.links"
                :key="link.label"
                :href="link.url"
                :aria-current="link.active ? 'page' : undefined"
                :aria-disabled="!link.url"
                :class="[
                    'relative inline-flex h-9 min-w-9 items-center justify-center rounded-xl border px-3 text-[13px] font-medium tabular-nums transition-colors duration-150',
                    link.active
                        ? 'z-10 border-[var(--ep-line-strong)] bg-[var(--ep-active)] text-[var(--ep-text)] shadow-[var(--ep-glass-highlight)]'
                        : link.url
                          ? 'border-transparent text-[var(--ep-text-3)] hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)]'
                          : 'cursor-not-allowed border-transparent text-[var(--ep-text-4)] opacity-60',
                ]"
                v-html="link.label"
                @click.prevent="link.url && router.visit(link.url, { preserveState: true })"
            />
        </nav>

        <!-- Sidebar detalhes -->
        <AlunoDetailSidebar
            :open="sidebarOpen"
            :aluno="selectedAluno"
            :produtos="produtos"
            @close="closeSidebar"
            @updated="handleAlunoUpdated"
            @deleted="handleAlunoDeleted"
        />

        <!-- Modal Novo aluno -->
        <Teleport to="body">
            <div
                v-show="novoAlunoModalOpen"
                class="fixed inset-0 z-[100001] flex items-center justify-center p-4"
                aria-modal="true"
                role="dialog"
            >
                <div
                    class="ep-scrim fixed inset-0"
                    @click="closeNovoAluno"
                />
                <div
                    class="ep-modal relative w-full max-w-md p-6"
                >
                    <h3 class="text-[17px] font-semibold tracking-[-0.02em] text-[var(--ep-text)]">
                        Cadastrar novo aluno
                    </h3>
                    <p class="mb-5 mt-1 text-[12.5px] text-[var(--ep-text-3)]">O aluno recebe acesso imediato aos produtos selecionados.</p>
                    <div class="space-y-4">
                        <div>
                            <label class="ep-label">
                                Nome
                            </label>
                            <input
                                v-model="novoAlunoForm.name"
                                type="text"
                                name="novo_aluno_name"
                                autocomplete="off"
                                autocapitalize="words"
                                autocorrect="off"
                                spellcheck="false"
                                class="ep-input"
                                placeholder="Nome do aluno"
                            />
                        </div>
                        <div>
                            <label class="ep-label">
                                E-mail
                            </label>
                            <input
                                v-model="novoAlunoForm.email"
                                type="email"
                                name="novo_aluno_email"
                                autocomplete="off"
                                autocapitalize="off"
                                autocorrect="off"
                                spellcheck="false"
                                class="ep-input"
                                placeholder="email@exemplo.com"
                            />
                        </div>
                        <div>
                            <label class="ep-label">
                                Senha
                            </label>
                            <input
                                v-model="novoAlunoForm.password"
                                type="password"
                                name="novo_aluno_password"
                                autocomplete="new-password"
                                class="ep-input"
                                placeholder="Mínimo 6 caracteres"
                            />
                        </div>
                        <div>
                            <label class="flex cursor-pointer items-center gap-2.5 rounded-[10px] px-2 py-1.5 text-left transition-colors duration-150 hover:bg-[var(--ep-hover)] -mx-2">
                                <span class="shrink-0 w-fit">
                                    <Checkbox :model-value="novoAlunoForm.send_access_email" @update:model-value="novoAlunoForm.send_access_email = $event" />
                                </span>
                                <span class="flex-1 text-left text-[13px] text-[var(--ep-text)]">Enviar e-mail de acesso ao criar</span>
                            </label>
                        </div>
                        <div>
                            <p class="ep-label">
                                Produtos com acesso <span class="font-normal text-[var(--ep-text-4)]">(opcional)</span>
                            </p>
                            <div class="max-h-40 space-y-0.5 overflow-y-auto rounded-xl border border-[var(--ep-input-border)] bg-[var(--ep-input)] p-1.5">
                                <label
                                    v-for="p in produtos"
                                    :key="p.id"
                                    class="flex cursor-pointer items-center gap-2.5 rounded-[10px] px-2 py-1.5 text-left transition-colors duration-150 hover:bg-[var(--ep-hover)]"
                                >
                                    <span class="shrink-0 w-fit">
                                        <Checkbox
                                            :model-value="novoAlunoForm.product_ids.includes(p.id)"
                                            @update:model-value="(v) => { if (v) novoAlunoForm.product_ids = [...novoAlunoForm.product_ids, p.id]; else novoAlunoForm.product_ids = novoAlunoForm.product_ids.filter(x => x !== p.id); }"
                                        />
                                    </span>
                                    <span class="flex-1 truncate text-left text-[13px] text-[var(--ep-text)]">{{ p.name }}</span>
                                </label>
                                <p v-if="!produtos.length" class="px-2 py-1.5 text-[12.5px] text-[var(--ep-text-4)]">Nenhum produto disponível</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-2 border-t border-[var(--ep-line)] pt-5">
                        <Button variant="outline" :disabled="savingNovo" @click="closeNovoAluno">
                            Cancelar
                        </Button>
                        <Button variant="primary" :disabled="savingNovo" @click="saveNovoAluno">
                            Cadastrar
                        </Button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal Importar -->
        <Teleport to="body">
            <div
                v-show="importModalOpen"
                class="fixed inset-0 z-[100001] flex items-center justify-center p-4"
                aria-modal="true"
                role="dialog"
            >
                <div class="ep-scrim fixed inset-0" @click="closeImportModal" />
                <div
                    class="ep-modal relative w-full max-w-md p-6"
                >
                    <h3 class="text-[17px] font-semibold tracking-[-0.02em] text-[var(--ep-text)]">
                        Importar alunos em massa
                    </h3>
                    <p class="mb-4 mt-1 text-[12.5px] leading-relaxed text-[var(--ep-text-3)]">
                        Envie um arquivo CSV com as colunas: <code class="rounded-md bg-[var(--ep-active)] px-1 py-0.5 font-mono text-[11.5px] text-[var(--ep-text-2)]">nome</code>, <code class="rounded-md bg-[var(--ep-active)] px-1 py-0.5 font-mono text-[11.5px] text-[var(--ep-text-2)]">email</code>, <code class="rounded-md bg-[var(--ep-active)] px-1 py-0.5 font-mono text-[11.5px] text-[var(--ep-text-2)]">senha</code> (opcional). Use <code class="rounded-md bg-[var(--ep-active)] px-1 py-0.5 font-mono text-[11.5px] text-[var(--ep-text-2)]">;</code> ou <code class="rounded-md bg-[var(--ep-active)] px-1 py-0.5 font-mono text-[11.5px] text-[var(--ep-text-2)]">,</code> como separador.
                    </p>
                    <a
                        href="/produtos/alunos/import-example"
                        download
                        class="ep-btn-ghost mb-4 !-ml-2 !h-8 !px-2 text-[var(--ep-accent)] hover:!text-[var(--ep-accent)]"
                    >
                        <Download class="h-4 w-4 shrink-0" :stroke-width="1.75" />
                        Baixar CSV de exemplo
                    </a>
                    <div class="space-y-4">
                        <div>
                            <label class="ep-label">
                                Arquivo CSV
                            </label>
                            <input
                                type="file"
                                accept=".csv,.txt"
                                class="ep-input !h-auto py-2 file:mr-3 file:rounded-lg file:border-0 file:bg-[var(--ep-active)] file:px-3 file:py-1 file:text-[12.5px] file:font-medium file:text-[var(--ep-text)]"
                                @change="onImportFileChange"
                            />
                            <p v-if="importForm.file" class="ep-help">
                                {{ importForm.file.name }}
                            </p>
                        </div>
                        <div>
                            <label class="flex cursor-pointer items-center gap-2.5 rounded-[10px] px-2 py-1.5 text-left transition-colors duration-150 hover:bg-[var(--ep-hover)] -mx-2">
                                <span class="shrink-0 w-fit">
                                    <Checkbox :model-value="importForm.send_access_email" @update:model-value="importForm.send_access_email = $event" />
                                </span>
                                <span class="flex-1 text-left text-[13px] text-[var(--ep-text)]">Enviar e-mail de acesso aos importados</span>
                            </label>
                        </div>
                        <div>
                            <p class="ep-label">
                                Produtos para dar acesso <span class="font-normal text-[var(--ep-text-4)]">(obrigatório)</span>
                            </p>
                            <div class="max-h-40 space-y-0.5 overflow-y-auto rounded-xl border border-[var(--ep-input-border)] bg-[var(--ep-input)] p-1.5">
                                <label
                                    v-for="p in produtos"
                                    :key="p.id"
                                    class="flex cursor-pointer items-center gap-2.5 rounded-[10px] px-2 py-1.5 text-left transition-colors duration-150 hover:bg-[var(--ep-hover)]"
                                >
                                    <span class="shrink-0 w-fit">
                                        <Checkbox
                                            :model-value="importForm.product_ids.includes(p.id)"
                                            @update:model-value="(v) => { if (v) importForm.product_ids = [...importForm.product_ids, p.id]; else importForm.product_ids = importForm.product_ids.filter(x => x !== p.id); }"
                                        />
                                    </span>
                                    <span class="flex-1 truncate text-left text-[13px] text-[var(--ep-text)]">{{ p.name }}</span>
                                </label>
                                <p v-if="!produtos.length" class="px-2 py-1.5 text-[12.5px] text-[var(--ep-text-4)]">Nenhum produto disponível</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-2 border-t border-[var(--ep-line)] pt-5">
                        <Button variant="outline" :disabled="importing" @click="closeImportModal">
                            Cancelar
                        </Button>
                        <Button variant="primary" :disabled="importing" @click="saveImport">
                            Importar
                        </Button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Toast -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="translate-y-2 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="translate-y-0 opacity-100"
                leave-to-class="translate-y-2 opacity-0"
            >
                <div
                    v-if="toast.message"
                    role="alert"
                    :class="[
                        'ep-modal fixed bottom-4 right-4 z-[100002] flex max-w-sm items-start gap-2.5 !rounded-2xl px-4 py-3',
                        toast.type === 'error' ? 'text-[var(--ep-neg)]' : 'text-[var(--ep-pos)]',
                    ]"
                >
                    <span class="mt-[7px] h-1.5 w-1.5 shrink-0 rounded-full bg-current" aria-hidden="true" />
                    <p class="text-[13px] font-medium text-[var(--ep-text)]">{{ toast.message }}</p>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
