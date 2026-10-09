<script setup>
import { computed, ref } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import Button from '@/components/ui/Button.vue';
import HorizontalScrollTabs from '@/components/ui/HorizontalScrollTabs.vue';
import { Users, Shield, UserPlus, Plus, Pencil, Trash2, X, ScrollText, Trash } from 'lucide-vue-next';
import PluginRenderZone from '@/components/plugins/PluginRenderZone.vue';

defineOptions({ layout: LayoutInfoprodutor });

const props = defineProps({
    products: { type: Array, default: () => [] },
    roles: { type: Array, default: () => [] },
    members: { type: Array, default: () => [] },
    logs: { type: Array, default: () => [] },
});

const page = usePage();
const role = computed(() => page.props.auth?.user?.role);

const tabs = computed(() => {
    const base = [
        { key: 'cargos', label: 'Cargos' },
        { key: 'membros', label: 'Membros' },
    ];
    if (role.value === 'admin') {
        base.push({ key: 'logs', label: 'Logs' });
    }
    return base;
});
const activeTab = ref('cargos');

const userTabs = computed(() => {
    // Admin tem as 2 abas; infoprodutor/equipe só faz sentido Equipe.
    return [
        { key: 'usuarios', label: 'Infoprodutores', href: '/usuarios', adminOnly: true },
        { key: 'equipe', label: 'Equipe', href: '/usuarios/equipe', adminOnly: false },
    ];
});
function isUsersTabActive(href) {
    // Evitar que "/usuarios" fique ativo em "/usuarios/equipe"
    if (href === '/usuarios') {
        return page.url === '/usuarios' || page.url.startsWith('/usuarios?');
    }
    return page.url === href || page.url.startsWith(href + '/') || page.url.startsWith(href + '?');
}

const corePermissionDefs = [
    { key: 'dashboard.view', label: 'Dashboard' },
    { key: 'vendas.view', label: 'Vendas' },
    { key: 'reembolsos.view', label: 'Reembolsos (ver)' },
    { key: 'reembolsos.manage', label: 'Reembolsos (aprovar/rejeitar)' },
    { key: 'produtos.view', label: 'Produtos' },
    { key: 'relatorios.view', label: 'Relatórios' },
    { key: 'integracoes.view', label: 'Integrações' },
    { key: 'email_marketing.view', label: 'E-mail Marketing' },
    { key: 'api_pagamentos.view', label: 'API de Pagamentos' },
    { key: 'financeiro.view', label: 'Financeiro (ver)' },
    { key: 'financeiro.manage', label: 'Financeiro (sacar / aprovar parceiros)' },
    { key: 'configuracoes.view', label: 'Configurações' },
    { key: 'equipe.manage', label: 'Gerenciar equipe' },
];

const pluginCapabilities = computed(() => page.props.plugin_capabilities ?? {});

const permissionDefs = computed(() => {
    const pluginDefs = Object.entries(pluginCapabilities.value).map(([key, label]) => ({
        key,
        label: typeof label === 'string' ? label : key,
        group: 'plugin',
    }));
    if (pluginDefs.length === 0) {
        return corePermissionDefs;
    }

    return [...corePermissionDefs, ...pluginDefs];
});

const showRoleModal = ref(false);
const editingRole = ref(null);

const roleForm = useForm({
    name: '',
    permissions: {},
    product_ids: [],
});

function defaultPermissions() {
    const p = {};
    for (const def of permissionDefs.value) {
        p[def.key] = false;
    }
    p['dashboard.view'] = true;
    p['vendas.view'] = true;
    return p;
}

function openCreateRole() {
    editingRole.value = null;
    roleForm.reset();
    roleForm.clearErrors();
    roleForm.name = '';
    roleForm.permissions = defaultPermissions();
    roleForm.product_ids = [];
    showRoleModal.value = true;
}

function openEditRole(role) {
    editingRole.value = role;
    roleForm.reset();
    roleForm.clearErrors();
    roleForm.name = role.name;
    roleForm.permissions = { ...defaultPermissions(), ...(role.permissions || {}) };
    roleForm.product_ids = Array.isArray(role.product_ids) ? [...role.product_ids] : [];
    showRoleModal.value = true;
}

function closeRoleModal() {
    showRoleModal.value = false;
}

function submitRole() {
    if (editingRole.value) {
        roleForm.put(`/usuarios/equipe/cargos/${editingRole.value.id}`, {
            preserveScroll: true,
            onSuccess: () => closeRoleModal(),
        });
        return;
    }
    roleForm.post('/usuarios/equipe/cargos', {
        preserveScroll: true,
        onSuccess: () => closeRoleModal(),
    });
}

function confirmDeleteRole(role) {
    if (!window.confirm(`Remover o cargo "${role.name}"?`)) return;
    router.delete(`/usuarios/equipe/cargos/${role.id}`, { preserveScroll: true });
}

const showMemberModal = ref(false);
const editingMember = ref(null);
const memberForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    team_role_id: null,
    send_access_email: true,
});

const roleOptions = computed(() => props.roles.map((r) => ({ id: r.id, name: r.name })));

function openCreateMember() {
    editingMember.value = null;
    memberForm.reset();
    memberForm.clearErrors();
    memberForm.team_role_id = roleOptions.value[0]?.id ?? null;
    memberForm.send_access_email = true;
    showMemberModal.value = true;
}

function openEditMember(m) {
    editingMember.value = m;
    memberForm.reset();
    memberForm.clearErrors();
    memberForm.name = m.name;
    memberForm.email = m.email;
    memberForm.password = '';
    memberForm.password_confirmation = '';
    memberForm.team_role_id = m.team_role_id ?? roleOptions.value[0]?.id ?? null;
    memberForm.send_access_email = false;
    showMemberModal.value = true;
}

function closeMemberModal() {
    showMemberModal.value = false;
}

function submitMember() {
    if (editingMember.value) {
        memberForm.put(`/usuarios/equipe/membros/${editingMember.value.id}`, {
            preserveScroll: true,
            onSuccess: () => closeMemberModal(),
        });
        return;
    }
    memberForm.post('/usuarios/equipe/membros', {
        preserveScroll: true,
        onSuccess: () => closeMemberModal(),
    });
}

function confirmDeleteMember(m) {
    if (!window.confirm(`Remover "${m.name}" da equipe?`)) return;
    router.delete(`/usuarios/equipe/membros/${m.id}`, { preserveScroll: true });
}

function formatDate(value) {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
}

function formatDateTime(value) {
    if (!value) return '—';
    return new Date(value).toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function confirmClearLogs() {
    if (!window.confirm('Limpar todos os logs de auditoria deste tenant?')) return;
    router.post('/usuarios/equipe/logs/clear', {}, { preserveScroll: true });
}
</script>

<template>
    <div class="space-y-5">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <h1 class="ep-page-heading">Usuários</h1>
                <p class="mt-1 text-[13px] text-[var(--ep-text-3)]">
                    Gerencie equipe e permissões.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button v-if="activeTab === 'cargos'" class="shrink-0" @click="openCreateRole">
                    <Plus class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                    Novo cargo
                </Button>
                <Button v-else-if="activeTab === 'membros'" class="shrink-0" @click="openCreateMember">
                    <UserPlus class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                    Novo membro
                </Button>
                <Button
                    v-else-if="activeTab === 'logs' && role === 'admin'"
                    variant="outline"
                    class="shrink-0"
                    @click="confirmClearLogs"
                >
                    <Trash class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                    Limpar logs
                </Button>
            </div>
        </header>
        <PluginRenderZone zone="equipe.index.after_header" />

        <!-- Abas Usuários (principal) + Abas Equipe (secundária) -->
        <div class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <HorizontalScrollTabs aria-label="Abas de usuários" nav-class="ep-tabs" wrapper-class="sm:w-auto">
                <Link
                    v-for="t in userTabs"
                    :key="t.key"
                    :href="t.href"
                    v-show="!t.adminOnly || role === 'admin'"
                    :class="['ep-tab', isUsersTabActive(t.href) ? 'ep-tab--active' : '']"
                    :aria-current="isUsersTabActive(t.href) ? 'page' : undefined"
                >
                    <Shield v-if="t.key === 'usuarios'" class="h-4 w-4 shrink-0" :stroke-width="1.75" aria-hidden="true" />
                    <Users v-else class="h-4 w-4 shrink-0" :stroke-width="1.75" aria-hidden="true" />
                    {{ t.label }}
                </Link>
            </HorizontalScrollTabs>

            <HorizontalScrollTabs aria-label="Abas de equipe" nav-class="ep-tabs" wrapper-class="sm:w-auto">
                <button
                    v-for="t in tabs"
                    :key="t.key"
                    type="button"
                    :class="['ep-tab', activeTab === t.key ? 'ep-tab--active' : '']"
                    :aria-current="activeTab === t.key ? 'page' : undefined"
                    @click="activeTab = t.key"
                >
                    <Shield v-if="t.key === 'cargos'" class="h-4 w-4 shrink-0" :stroke-width="1.75" aria-hidden="true" />
                    <Users v-else-if="t.key === 'membros'" class="h-4 w-4 shrink-0" :stroke-width="1.75" aria-hidden="true" />
                    <ScrollText v-else class="h-4 w-4 shrink-0" :stroke-width="1.75" aria-hidden="true" />
                    {{ t.label }}
                    <span
                        class="ml-0.5 rounded-full bg-[var(--ep-active)] px-1.5 text-[11px] font-medium tabular-nums leading-[18px] text-[var(--ep-text-3)]"
                    >{{ t.key === 'cargos' ? roles.length : t.key === 'membros' ? members.length : logs.length }}</span>
                </button>
            </HorizontalScrollTabs>
        </div>

        <!-- Cargos -->
        <section v-if="activeTab === 'cargos'" class="panel-card ep-data overflow-hidden" aria-labelledby="equipe-cargos">
            <div class="flex items-center justify-between gap-3 px-5 pb-3 pt-5">
                <h2 id="equipe-cargos" class="ep-section-title">Cargos e permissões</h2>
                <span class="text-[12px] tabular-nums text-[var(--ep-text-4)]">
                    {{ roles.length }} {{ roles.length === 1 ? 'cargo' : 'cargos' }}
                </span>
            </div>
            <div class="overflow-x-auto border-t border-[var(--ep-line)]" :class="{ hidden: !roles.length }">
                <table class="ep-table min-w-[720px]">
                    <thead>
                        <tr>
                            <th class="w-[28%]">Cargo</th>
                            <th>Permissões</th>
                            <th class="ep-num">Produtos</th>
                            <th class="w-[96px]"><span class="sr-only">Ações</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in roles" :key="r.id">
                            <td>
                                <div class="flex min-w-0 items-center gap-3">
                                    <span
                                        class="flex h-[30px] w-[30px] shrink-0 items-center justify-center rounded-[10px] border border-[var(--ep-glass-border)] bg-[var(--ep-card-2)] text-[var(--ep-text-3)]"
                                        aria-hidden="true"
                                    >
                                        <Shield class="h-4 w-4" :stroke-width="1.75" />
                                    </span>
                                    <span class="truncate text-[13px] font-medium text-[var(--ep-text)]">{{ r.name }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="flex min-w-0 items-start gap-2.5">
                                    <span class="ep-chip ep-chip--accent shrink-0 tabular-nums">
                                        {{ permissionDefs.filter(p => r.permissions?.[p.key]).length }}/{{ permissionDefs.length }}
                                    </span>
                                    <p class="line-clamp-2 min-w-0 max-w-[520px] pt-0.5 text-[12.5px] leading-[18px] text-[var(--ep-text-3)]">
                                        {{ permissionDefs.filter(p => r.permissions?.[p.key]).map(p => p.label).join(', ') || '—' }}
                                    </p>
                                </div>
                            </td>
                            <td class="ep-num">
                                <span class="font-medium text-[var(--ep-text)]">{{ (r.product_ids?.length ?? 0) }}</span>
                                <span class="ml-1 text-[12px] text-[var(--ep-text-4)]">produto(s)</span>
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    <button
                                        type="button"
                                        class="ep-btn-ghost ep-btn-icon !h-8 !w-8"
                                        title="Editar cargo"
                                        aria-label="Editar cargo"
                                        @click="openEditRole(r)"
                                    >
                                        <Pencil class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                                    </button>
                                    <button
                                        type="button"
                                        class="ep-btn-ghost ep-btn-icon !h-8 !w-8 hover:!bg-[var(--ep-neg-bg)] hover:!text-[var(--ep-neg)]"
                                        title="Remover cargo"
                                        aria-label="Remover cargo"
                                        @click="confirmDeleteRole(r)"
                                    >
                                        <Trash2 class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="!roles.length" class="ep-empty border-t border-[var(--ep-line)]">
                <p class="ep-empty__title">Nenhum cargo criado.</p>
                <p class="ep-empty__text">Crie um cargo em “Novo cargo” para definir permissões e produtos antes de convidar membros.</p>
            </div>
        </section>

        <!-- Membros -->
        <section v-else-if="activeTab === 'membros'" class="panel-card ep-data overflow-hidden" aria-labelledby="equipe-membros">
            <div class="flex items-center justify-between gap-3 px-5 pb-3 pt-5">
                <h2 id="equipe-membros" class="ep-section-title">Membros da equipe</h2>
                <span class="text-[12px] tabular-nums text-[var(--ep-text-4)]">
                    {{ members.length }} {{ members.length === 1 ? 'membro' : 'membros' }}
                </span>
            </div>
            <div class="overflow-x-auto border-t border-[var(--ep-line)]" :class="{ hidden: !members.length }">
                <table class="ep-table min-w-[640px]">
                    <thead>
                        <tr>
                            <th>Membro</th>
                            <th>Cargo</th>
                            <th class="ep-num">Desde</th>
                            <th class="w-[96px]"><span class="sr-only">Ações</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="m in members" :key="m.id">
                            <td>
                                <div class="flex min-w-0 items-center gap-3">
                                    <span v-avatar="m.name" class="ep-avatar shrink-0" aria-hidden="true">{{ (m.name || '?').trim().split(' ').filter(Boolean).map((p) => p[0]).slice(0, 2).join('').toUpperCase() || '?' }}</span>
                                    <div class="min-w-0">
                                        <p class="truncate text-[13px] font-medium text-[var(--ep-text)]">{{ m.name }}</p>
                                        <p class="truncate text-[12px] text-[var(--ep-text-3)]">{{ m.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="ep-chip" :class="m.team_role_name ? 'ep-chip--accent' : ''">
                                    <Users class="h-3 w-3" :stroke-width="2" aria-hidden="true" />
                                    {{ m.team_role_name || 'Sem cargo' }}
                                </span>
                            </td>
                            <td class="ep-num text-[12.5px] !text-[var(--ep-text-3)]">
                                {{ formatDate(m.created_at) }}
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    <button
                                        type="button"
                                        class="ep-btn-ghost ep-btn-icon !h-8 !w-8"
                                        title="Editar membro"
                                        aria-label="Editar membro"
                                        @click="openEditMember(m)"
                                    >
                                        <Pencil class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                                    </button>
                                    <button
                                        type="button"
                                        class="ep-btn-ghost ep-btn-icon !h-8 !w-8 hover:!bg-[var(--ep-neg-bg)] hover:!text-[var(--ep-neg)]"
                                        title="Remover membro"
                                        aria-label="Remover membro"
                                        @click="confirmDeleteMember(m)"
                                    >
                                        <Trash2 class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="!members.length" class="ep-empty border-t border-[var(--ep-line)]">
                <p class="ep-empty__title">Nenhum membro cadastrado.</p>
                <p class="ep-empty__text">Adicione alguém em “Novo membro” e escolha o cargo que define o que ele pode ver.</p>
            </div>
        </section>

        <!-- Logs (admin only) -->
        <section
            v-else
            class="panel-card ep-data overflow-hidden"
            aria-labelledby="equipe-logs"
        >
            <div class="flex items-center justify-between gap-3 px-5 pb-3 pt-5">
                <h2 id="equipe-logs" class="ep-section-title">Registro de auditoria</h2>
                <span class="text-[12px] tabular-nums text-[var(--ep-text-4)]">
                    {{ logs.length }} {{ logs.length === 1 ? 'evento' : 'eventos' }}
                </span>
            </div>
            <div class="max-h-[640px] overflow-auto border-t border-[var(--ep-line)]" :class="{ hidden: !logs.length }">
                <table class="ep-table min-w-[720px]">
                    <thead>
                        <tr>
                            <th class="w-[150px]">Quando</th>
                            <th>Usuário</th>
                            <th>Ação</th>
                            <th class="ep-num">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="l in logs" :key="l.id">
                            <td class="whitespace-nowrap text-[12.5px] tabular-nums !text-[var(--ep-text-2)]">{{ formatDateTime(l.created_at) }}</td>
                            <td>
                                <div class="truncate text-[13px] font-medium text-[var(--ep-text)]">
                                    {{ l.actor?.name || '—' }}
                                </div>
                                <div class="truncate text-[12px] text-[var(--ep-text-3)]">
                                    {{ l.actor?.email || '' }}
                                </div>
                            </td>
                            <td>
                                <code class="inline-flex rounded-md border border-[var(--ep-line)] bg-[var(--ep-card-2)] px-1.5 py-0.5 font-mono text-[11.5px] text-[var(--ep-text-2)]">
                                    {{ l.action }}
                                </code>
                                <div v-if="l.target_type || l.target_id" class="mt-1 text-[12px] tabular-nums text-[var(--ep-text-4)]">
                                    {{ l.target_type }} {{ l.target_id ? `#${l.target_id}` : '' }}
                                </div>
                            </td>
                            <td class="ep-num font-mono text-[12px] !text-[var(--ep-text-3)]">
                                {{ l.ip || '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="!logs.length" class="ep-empty border-t border-[var(--ep-line)]">
                <p class="ep-empty__title">Nenhum log registrado ainda.</p>
                <p class="ep-empty__text">Os eventos de auditoria da equipe aparecem aqui assim que forem registrados.</p>
            </div>
        </section>
    </div>

    <!-- Modal: Cargo -->
    <Teleport to="body">
        <div
            v-if="showRoleModal"
            class="fixed inset-0 z-[100002] flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
        >
            <div class="ep-scrim fixed inset-0" aria-hidden="true" @click="closeRoleModal" />
            <div class="ep-modal relative flex max-h-[calc(100vh-2rem)] w-full max-w-2xl flex-col overflow-hidden">
                <div class="flex shrink-0 items-start justify-between gap-4 px-6 pt-6">
                    <div class="min-w-0">
                        <h2 class="text-[16px] font-semibold tracking-[-0.02em] text-[var(--ep-text)]">
                            {{ editingRole ? 'Editar cargo' : 'Novo cargo' }}
                        </h2>
                        <p class="mt-1 text-[12.5px] text-[var(--ep-text-3)]">Defina o que este cargo pode ver e quais produtos acessa.</p>
                    </div>
                    <button
                        type="button"
                        class="ep-btn-ghost ep-btn-icon -mr-2 -mt-1 shrink-0"
                        aria-label="Fechar"
                        @click="closeRoleModal"
                    >
                        <X class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                    </button>
                </div>
                <form class="flex min-h-0 flex-1 flex-col" @submit.prevent="submitRole">
                    <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 pb-2 pt-5">
                        <div>
                            <label class="ep-label">Nome do cargo</label>
                            <input
                                v-model="roleForm.name"
                                type="text"
                                required
                                class="ep-input"
                            />
                            <p v-if="roleForm.errors.name" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">{{ roleForm.errors.name }}</p>
                        </div>

                        <div class="grid gap-4 lg:grid-cols-2">
                            <div class="rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4">
                                <div class="flex items-center justify-between gap-2">
                                    <h3 class="ep-section-title">Permissões</h3>
                                    <span class="ep-chip ep-chip--accent tabular-nums">
                                        {{ permissionDefs.filter((d) => roleForm.permissions[d.key]).length }}/{{ permissionDefs.length }}
                                    </span>
                                </div>
                                <div class="-mx-2 mt-3 space-y-0.5">
                                    <template v-for="p in permissionDefs.filter((d) => !d.group)" :key="p.key">
                                        <label class="flex cursor-pointer items-center gap-2.5 rounded-lg px-2 py-1.5 text-[13px] text-[var(--ep-text-2)] transition-colors duration-150 hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)]">
                                            <input v-model="roleForm.permissions[p.key]" type="checkbox" class="h-4 w-4 shrink-0 rounded accent-[var(--ep-accent)]" />
                                            <span>{{ p.label }}</span>
                                        </label>
                                    </template>
                                    <template v-if="permissionDefs.some((d) => d.group === 'plugin')">
                                        <p class="px-2 pb-1 pt-3 text-[11.5px] font-medium text-[var(--ep-text-4)]">Plugins</p>
                                        <label
                                            v-for="p in permissionDefs.filter((d) => d.group === 'plugin')"
                                            :key="p.key"
                                            class="flex cursor-pointer items-center gap-2.5 rounded-lg px-2 py-1.5 text-[13px] text-[var(--ep-text-2)] transition-colors duration-150 hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)]"
                                        >
                                            <input v-model="roleForm.permissions[p.key]" type="checkbox" class="h-4 w-4 shrink-0 rounded accent-[var(--ep-accent)]" />
                                            <span>{{ p.label }}</span>
                                        </label>
                                    </template>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4">
                                <div class="flex items-center justify-between gap-2">
                                    <h3 class="ep-section-title">Produtos permitidos</h3>
                                    <span class="ep-chip tabular-nums">
                                        {{ roleForm.product_ids.length }}/{{ products.length }}
                                    </span>
                                </div>
                                <p class="ep-help !mt-1">
                                    Afeta todos os módulos por produto (Dashboard, Vendas, Produtos, etc.).
                                </p>
                                <div class="-mx-2 mt-3 max-h-[260px] space-y-0.5 overflow-auto pr-1">
                                    <label v-for="p in products" :key="p.id" class="flex cursor-pointer items-center gap-2.5 rounded-lg px-2 py-1.5 text-[13px] text-[var(--ep-text-2)] transition-colors duration-150 hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)]">
                                        <input
                                            :value="p.id"
                                            v-model="roleForm.product_ids"
                                            type="checkbox"
                                            class="h-4 w-4 shrink-0 rounded accent-[var(--ep-accent)]"
                                        />
                                        <span class="truncate">{{ p.name }}</span>
                                    </label>
                                </div>
                                <p v-if="roleForm.errors.product_ids" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">{{ roleForm.errors.product_ids }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex shrink-0 justify-end gap-2 border-t border-[var(--ep-line)] px-6 py-4">
                        <Button type="button" variant="outline" @click="closeRoleModal">
                            Cancelar
                        </Button>
                        <Button type="submit" :disabled="roleForm.processing">
                            Salvar
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <!-- Modal: Membro -->
    <Teleport to="body">
        <div
            v-if="showMemberModal"
            class="fixed inset-0 z-[100002] flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
        >
            <div class="ep-scrim fixed inset-0" aria-hidden="true" @click="closeMemberModal" />
            <div class="ep-modal relative w-full max-w-md">
                <div class="flex items-start justify-between gap-4 px-6 pt-6">
                    <div class="min-w-0">
                        <h2 class="text-[16px] font-semibold tracking-[-0.02em] text-[var(--ep-text)]">
                            {{ editingMember ? 'Editar membro' : 'Novo membro' }}
                        </h2>
                        <p class="mt-1 text-[12.5px] text-[var(--ep-text-3)]">O cargo define o que este membro pode ver no painel.</p>
                    </div>
                    <button
                        type="button"
                        class="ep-btn-ghost ep-btn-icon -mr-2 -mt-1 shrink-0"
                        aria-label="Fechar"
                        @click="closeMemberModal"
                    >
                        <X class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                    </button>
                </div>
                <form class="space-y-4 px-6 pb-6 pt-5" @submit.prevent="submitMember">
                    <div>
                        <label class="ep-label">Nome</label>
                        <input v-model="memberForm.name" type="text" required autocomplete="name" class="ep-input" />
                        <p v-if="memberForm.errors.name" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">{{ memberForm.errors.name }}</p>
                    </div>
                    <div>
                        <label class="ep-label">E-mail</label>
                        <input v-model="memberForm.email" type="email" required autocomplete="email" class="ep-input" />
                        <p v-if="memberForm.errors.email" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">{{ memberForm.errors.email }}</p>
                    </div>
                    <div>
                        <label class="ep-label">Cargo</label>
                        <select v-model="memberForm.team_role_id" required class="ep-input">
                            <option v-for="r in roleOptions" :key="r.id" :value="r.id">{{ r.name }}</option>
                        </select>
                        <p v-if="memberForm.errors.team_role_id" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">{{ memberForm.errors.team_role_id }}</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="ep-label">
                                {{ editingMember ? 'Nova senha (opcional)' : 'Senha' }}
                            </label>
                            <input v-model="memberForm.password" type="password" :required="!editingMember" minlength="8" autocomplete="new-password" class="ep-input" />
                        </div>
                        <div>
                            <label class="ep-label">Confirmar senha</label>
                            <input v-model="memberForm.password_confirmation" type="password" :required="!editingMember && !!memberForm.password" minlength="8" autocomplete="new-password" class="ep-input" />
                        </div>
                    </div>
                    <p v-if="memberForm.errors.password" class="-mt-2 text-[12px] text-[var(--ep-neg)]">{{ memberForm.errors.password }}</p>
                    <div v-if="!editingMember" class="rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-3.5">
                        <label class="flex cursor-pointer items-center gap-2.5 text-[13px] font-medium text-[var(--ep-text)]">
                            <input v-model="memberForm.send_access_email" type="checkbox" class="h-4 w-4 shrink-0 rounded accent-[var(--ep-accent)]" />
                            <span>Enviar e-mail de acesso com login e senha</span>
                        </label>
                        <p class="ep-help !mt-1 pl-[26px]">
                            O e-mail será enviado para o endereço informado acima.
                        </p>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-[var(--ep-line)] pt-4">
                        <Button type="button" variant="outline" @click="closeMemberModal">
                            Cancelar
                        </Button>
                        <Button type="submit" :disabled="memberForm.processing">
                            Salvar
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>

