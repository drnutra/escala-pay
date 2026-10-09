<script setup>
import { ref, computed } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import Button from '@/components/ui/Button.vue';
import HorizontalScrollTabs from '@/components/ui/HorizontalScrollTabs.vue';
import { UserPlus, Trash2, Shield, User, Pencil, X, Lock } from 'lucide-vue-next';

defineOptions({ layout: LayoutInfoprodutor });

const props = defineProps({
    users: { type: Array, default: () => [] },
});

const page = usePage();

const userTabs = [
    { key: 'usuarios', label: 'Infoprodutores', href: '/usuarios' },
    { key: 'equipe', label: 'Equipe', href: '/usuarios/equipe' },
];
function isUsersTabActive(href) {
    // Evitar que "/usuarios" fique ativo em "/usuarios/equipe"
    if (href === '/usuarios') {
        return page.url === '/usuarios' || page.url.startsWith('/usuarios?');
    }
    return page.url === href || page.url.startsWith(href + '/') || page.url.startsWith(href + '?');
}

const showCreateModal = ref(false);
const editUser = ref(null);
const deletingId = ref(null);

const createForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const editForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const isCreateModalOpen = computed(() => showCreateModal.value);
const isEditModalOpen = computed(() => editUser.value !== null);

function openCreateModal() {
    createForm.reset();
    createForm.clearErrors();
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
}

function openEditModal(u) {
    editUser.value = u;
    editForm.name = u.name;
    editForm.email = u.email;
    editForm.password = '';
    editForm.password_confirmation = '';
    editForm.clearErrors();
}

function closeEditModal() {
    editUser.value = null;
}

function submitCreate() {
    createForm.post('/usuarios', {
        preserveScroll: true,
        onSuccess: () => closeCreateModal(),
    });
}

function submitEdit() {
    if (!editUser.value) return;
    editForm.put(`/usuarios/${editUser.value.id}`, {
        preserveScroll: true,
        onSuccess: () => closeEditModal(),
    });
}

function formatDate(value) {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
}

function confirmDelete(u) {
    if (u.is_master) return;
    if (!window.confirm(`Excluir "${u.name}"? Esta ação não pode ser desfeita.`)) return;
    deletingId.value = u.id;
    router.delete(`/usuarios/${u.id}`, {
        preserveScroll: true,
        onFinish: () => { deletingId.value = null; },
    });
}
</script>

<template>
    <div class="space-y-5">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <h1 class="ep-page-heading">Usuários</h1>
                <p class="mt-1 text-[13px] text-[var(--ep-text-3)]">
                    Conta Master e infoprodutores da plataforma.
                </p>
            </div>
            <Button class="shrink-0" @click="openCreateModal">
                <UserPlus class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                Novo infoprodutor
            </Button>
        </header>

        <!-- Abas Usuários -->
        <HorizontalScrollTabs aria-label="Abas de usuários" nav-class="ep-tabs">
            <Link
                v-for="t in userTabs"
                :key="t.key"
                :href="t.href"
                :class="['ep-tab', isUsersTabActive(t.href) ? 'ep-tab--active' : '']"
                :aria-current="isUsersTabActive(t.href) ? 'page' : undefined"
            >
                <Shield v-if="t.key === 'usuarios'" class="h-4 w-4 shrink-0" :stroke-width="1.75" aria-hidden="true" />
                <User v-else class="h-4 w-4 shrink-0" :stroke-width="1.75" aria-hidden="true" />
                {{ t.label }}
            </Link>
        </HorizontalScrollTabs>

        <section class="panel-card ep-data overflow-hidden" aria-labelledby="usuarios-lista">
            <div class="flex items-center justify-between gap-3 px-5 pb-3 pt-5">
                <h2 id="usuarios-lista" class="ep-section-title">Contas com acesso</h2>
                <span class="text-[12px] tabular-nums text-[var(--ep-text-4)]">
                    {{ users.length }} {{ users.length === 1 ? 'conta' : 'contas' }}
                </span>
            </div>
            <div class="overflow-x-auto border-t border-[var(--ep-line)]" :class="{ hidden: !users.length }">
                <table class="ep-table min-w-[640px]">
                    <thead>
                        <tr>
                            <th>Usuário</th>
                            <th>Papel</th>
                            <th class="ep-num !text-right">Criado em</th>
                            <th class="w-[104px]"><span class="sr-only">Ações</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="u in users" :key="u.id">
                            <td>
                                <div class="flex min-w-0 items-center gap-3">
                                    <span
                                        v-if="u.avatar_url"
                                        class="h-[30px] w-[30px] shrink-0 overflow-hidden rounded-full border border-[var(--ep-glass-border)] bg-[var(--ep-card-2)]"
                                    >
                                        <img :src="u.avatar_url" :alt="u.name" class="h-full w-full object-cover" />
                                    </span>
                                    <span
                                        v-else
                                        v-avatar="u.name" class="ep-avatar shrink-0"
                                        :class="u.is_master ? 'ring-2 ring-[color-mix(in_oklab,var(--ep-accent)_45%,transparent)] ring-offset-0' : ''"
                                        aria-hidden="true"
                                    >{{ (u.name || '?').trim().split(' ').filter(Boolean).map((p) => p[0]).slice(0, 2).join('').toUpperCase() || '?' }}</span>
                                    <div class="min-w-0">
                                        <p class="truncate text-[13px] font-medium text-[var(--ep-text)]">{{ u.name }}</p>
                                        <p class="truncate text-[12px] text-[var(--ep-text-3)]">{{ u.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="ep-chip" :class="u.is_master ? 'ep-chip--accent' : ''">
                                    <Shield v-if="u.is_master" class="h-3 w-3" :stroke-width="2" aria-hidden="true" />
                                    <User v-else class="h-3 w-3" :stroke-width="2" aria-hidden="true" />
                                    {{ u.is_master ? 'Master' : 'Infoprodutor' }}
                                </span>
                            </td>
                            <td class="ep-num text-[12.5px] !text-[var(--ep-text-3)]">
                                {{ formatDate(u.created_at) }}
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    <button
                                        type="button"
                                        class="ep-btn-ghost ep-btn-icon !h-8 !w-8"
                                        title="Editar usuário"
                                        aria-label="Editar usuário"
                                        @click="openEditModal(u)"
                                    >
                                        <Pencil class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                                    </button>
                                    <button
                                        v-if="!u.is_master"
                                        type="button"
                                        :disabled="deletingId === u.id"
                                        class="ep-btn-ghost ep-btn-icon !h-8 !w-8 hover:!bg-[var(--ep-neg-bg)] hover:!text-[var(--ep-neg)]"
                                        title="Excluir usuário"
                                        aria-label="Excluir usuário"
                                        @click="confirmDelete(u)"
                                    >
                                        <Trash2 class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                                    </button>
                                    <span
                                        v-if="u.is_master"
                                        class="inline-flex h-8 w-8 items-center justify-center text-[var(--ep-text-4)]"
                                        title="A conta Master não pode ser excluída"
                                    >
                                        <Lock class="h-3.5 w-3.5" :stroke-width="1.75" aria-hidden="true" />
                                    </span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="!users.length" class="ep-empty border-t border-[var(--ep-line)]">
                <p class="ep-empty__title">Nenhum usuário cadastrado.</p>
                <p class="ep-empty__text">Use “Novo infoprodutor” para dar acesso ao painel a uma nova conta.</p>
            </div>
        </section>
    </div>

    <!-- Modal: Novo usuário -->
    <Teleport to="body">
        <div
            v-if="isCreateModalOpen"
            class="fixed inset-0 z-[100002] flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="modal-create-title"
        >
            <div
                class="ep-scrim fixed inset-0"
                aria-hidden="true"
                @click="closeCreateModal"
            />
            <div class="ep-modal relative w-full max-w-md">
                <div class="flex items-start justify-between gap-4 px-6 pt-6">
                    <div class="min-w-0">
                        <h2 id="modal-create-title" class="text-[16px] font-semibold tracking-[-0.02em] text-[var(--ep-text)]">
                            Novo infoprodutor
                        </h2>
                        <p class="mt-1 text-[12.5px] text-[var(--ep-text-3)]">A conta terá acesso ao painel como infoprodutor.</p>
                    </div>
                    <button
                        type="button"
                        class="ep-btn-ghost ep-btn-icon -mr-2 -mt-1 shrink-0"
                        aria-label="Fechar"
                        @click="closeCreateModal"
                    >
                        <X class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                    </button>
                </div>
                <form class="space-y-4 px-6 pb-6 pt-5" @submit.prevent="submitCreate">
                    <div>
                        <label for="create-name" class="ep-label">Nome</label>
                        <input
                            id="create-name"
                            v-model="createForm.name"
                            type="text"
                            required
                            autocomplete="name"
                            class="ep-input"
                        />
                        <p v-if="createForm.errors.name" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">{{ createForm.errors.name }}</p>
                    </div>
                    <div>
                        <label for="create-email" class="ep-label">E-mail</label>
                        <input
                            id="create-email"
                            v-model="createForm.email"
                            type="email"
                            required
                            autocomplete="email"
                            class="ep-input"
                        />
                        <p v-if="createForm.errors.email" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">{{ createForm.errors.email }}</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="create-password" class="ep-label">Senha</label>
                            <input
                                id="create-password"
                                v-model="createForm.password"
                                type="password"
                                required
                                minlength="8"
                                autocomplete="new-password"
                                class="ep-input"
                            />
                        </div>
                        <div>
                            <label for="create-password_confirmation" class="ep-label">Confirmar senha</label>
                            <input
                                id="create-password_confirmation"
                                v-model="createForm.password_confirmation"
                                type="password"
                                required
                                minlength="8"
                                autocomplete="new-password"
                                class="ep-input"
                            />
                        </div>
                    </div>
                    <p v-if="createForm.errors.password" class="-mt-2 text-[12px] text-[var(--ep-neg)]">{{ createForm.errors.password }}</p>
                    <p class="ep-help !mt-0">Mínimo de 8 caracteres.</p>
                    <div class="flex justify-end gap-2 border-t border-[var(--ep-line)] pt-4">
                        <Button type="button" variant="outline" @click="closeCreateModal">
                            Cancelar
                        </Button>
                        <Button type="submit" :disabled="createForm.processing">
                            Cadastrar
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <!-- Modal: Editar usuário -->
    <Teleport to="body">
        <div
            v-if="isEditModalOpen"
            class="fixed inset-0 z-[100002] flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="modal-edit-title"
        >
            <div
                class="ep-scrim fixed inset-0"
                aria-hidden="true"
                @click="closeEditModal"
            />
            <div class="ep-modal relative w-full max-w-md">
                <div class="flex items-start justify-between gap-4 px-6 pt-6">
                    <div class="min-w-0">
                        <h2 id="modal-edit-title" class="text-[16px] font-semibold tracking-[-0.02em] text-[var(--ep-text)]">
                            Editar usuário
                        </h2>
                        <p class="mt-1 truncate text-[12.5px] text-[var(--ep-text-3)]">{{ editUser?.email }}</p>
                    </div>
                    <button
                        type="button"
                        class="ep-btn-ghost ep-btn-icon -mr-2 -mt-1 shrink-0"
                        aria-label="Fechar"
                        @click="closeEditModal"
                    >
                        <X class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                    </button>
                </div>
                <form class="space-y-4 px-6 pb-6 pt-5" @submit.prevent="submitEdit">
                    <div>
                        <label for="edit-name" class="ep-label">Nome</label>
                        <input
                            id="edit-name"
                            v-model="editForm.name"
                            type="text"
                            required
                            autocomplete="name"
                            class="ep-input"
                        />
                        <p v-if="editForm.errors.name" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">{{ editForm.errors.name }}</p>
                    </div>
                    <div>
                        <label for="edit-email" class="ep-label">E-mail</label>
                        <input
                            id="edit-email"
                            v-model="editForm.email"
                            type="email"
                            required
                            autocomplete="email"
                            class="ep-input"
                        />
                        <p v-if="editForm.errors.email" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">{{ editForm.errors.email }}</p>
                    </div>
                    <div class="rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4">
                        <p class="ep-section-title">Alterar senha</p>
                        <p class="ep-help !mt-0.5">Deixe em branco para não alterar.</p>
                        <div class="mt-3 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="edit-password" class="ep-label">Nova senha</label>
                                <input
                                    id="edit-password"
                                    v-model="editForm.password"
                                    type="password"
                                    minlength="8"
                                    autocomplete="new-password"
                                    class="ep-input"
                                />
                            </div>
                            <div>
                                <label for="edit-password_confirmation" class="ep-label">Confirmar nova senha</label>
                                <input
                                    id="edit-password_confirmation"
                                    v-model="editForm.password_confirmation"
                                    type="password"
                                    minlength="8"
                                    autocomplete="new-password"
                                    class="ep-input"
                                />
                            </div>
                        </div>
                        <p v-if="editForm.errors.password" class="mt-2 text-[12px] text-[var(--ep-neg)]">{{ editForm.errors.password }}</p>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-[var(--ep-line)] pt-4">
                        <Button type="button" variant="outline" @click="closeEditModal">
                            Cancelar
                        </Button>
                        <Button type="submit" :disabled="editForm.processing">
                            Salvar
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
