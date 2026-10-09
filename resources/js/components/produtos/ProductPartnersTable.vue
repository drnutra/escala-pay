<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { MoreVertical, Users } from 'lucide-vue-next';

const props = defineProps({
    rows: { type: Array, default: () => [] },
    emptyLabel: { type: String, default: 'Nenhum registro encontrado.' },
    statusLabels: { type: Object, default: () => ({}) },
    statusBadgeClasses: { type: Object, default: () => ({}) },
    showProductColumn: { type: Boolean, default: true },
    selectable: { type: Boolean, default: true },
});

const openMenuId = ref(null);
const menuAnchorEl = ref(null);
const menuEl = ref(null);
const menuPos = ref({ top: 0, left: 0 });
const selectedIds = ref(new Set());

const allSelected = computed(() => {
    if (!props.rows.length) return false;
    return props.rows.every((r) => selectedIds.value.has(r.id));
});

const defaultStatusLabels = {
    approved: 'Ativo',
    active: 'Ativo',
    pending: 'Pendente',
    rejected: 'Rejeitado',
    removed: 'Removido',
    revoked: 'Revogado',
    expired: 'Expirado',
};

const defaultStatusClasses = {
    approved: 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300',
    active: 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300',
    pending: 'bg-amber-500/15 text-amber-800 dark:text-amber-300',
    rejected: 'bg-red-500/15 text-red-700 dark:text-red-300',
    removed: 'bg-zinc-500/15 text-zinc-600 dark:text-zinc-400',
    revoked: 'bg-zinc-500/15 text-zinc-600 dark:text-zinc-400',
    expired: 'bg-zinc-500/15 text-zinc-600 dark:text-zinc-400',
};

function statusLabel(status) {
    return props.statusLabels[status] ?? defaultStatusLabels[status] ?? status ?? '—';
}

function statusClass(status) {
    return props.statusBadgeClasses[status] ?? defaultStatusClasses[status] ?? 'bg-zinc-500/15 text-zinc-600 dark:text-zinc-400';
}

function formatDate(iso) {
    if (!iso) return '—';
    return new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short' }).format(new Date(iso));
}

function formatCommission(value) {
    if (value === null || value === undefined || value === '') return '—';
    const n = Number(value);
    if (Number.isNaN(n)) return '—';
    return `${n % 1 === 0 ? n : n.toFixed(2).replace('.', ',')}%`;
}

function toggleSelectAll() {
    if (allSelected.value) {
        selectedIds.value = new Set();
        return;
    }
    selectedIds.value = new Set(props.rows.map((r) => r.id));
}

function toggleRow(id) {
    const next = new Set(selectedIds.value);
    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }
    selectedIds.value = next;
}

async function updateMenuPosition() {
    const anchor = menuAnchorEl.value;
    if (!anchor || openMenuId.value == null) return;

    const rect = anchor.getBoundingClientRect();
    const minMargin = 8;
    const desiredWidth = 192;
    const viewportW = window.innerWidth || 0;
    const viewportH = window.innerHeight || 0;

    let left = rect.right - desiredWidth;
    left = Math.max(minMargin, Math.min(left, Math.max(minMargin, viewportW - desiredWidth - minMargin)));

    let top = rect.bottom + 4;
    menuPos.value = { top, left };

    await nextTick();
    const menu = menuEl.value;
    if (!menu) return;

    const menuRect = menu.getBoundingClientRect();
    const spaceBelow = viewportH - rect.bottom;
    const spaceAbove = rect.top;
    if (menuRect.height + 8 > spaceBelow && spaceAbove >= menuRect.height + 8) {
        menuPos.value = { top: Math.max(minMargin, rect.top - menuRect.height - 4), left };
    }
}

async function toggleMenu(id, event) {
    if (openMenuId.value === id) {
        closeMenu();
        return;
    }
    openMenuId.value = id;
    menuAnchorEl.value = event?.currentTarget ?? null;
    await nextTick();
    await updateMenuPosition();
}

function closeMenu() {
    openMenuId.value = null;
    menuAnchorEl.value = null;
}

function handleClickOutside(event) {
    if (openMenuId.value == null) return;
    const el = document.querySelector(`[data-partner-menu="${openMenuId.value}"]`);
    const menu = menuEl.value;
    if (el?.contains(event.target)) return;
    if (menu?.contains(event.target)) return;
    closeMenu();
}

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));

defineExpose({ closeMenu });
</script>

<template>
    <div class="panel-card ep-data overflow-hidden">
        <div class="overflow-x-auto">
            <table class="ep-table min-w-full">
                <thead>
                    <tr>
                        <th v-if="selectable" class="w-10 !pr-0">
                            <input
                                type="checkbox"
                                class="h-4 w-4 cursor-pointer rounded-[5px] align-middle accent-[var(--ep-accent)] disabled:cursor-not-allowed disabled:opacity-40"
                                :checked="allSelected"
                                :disabled="!rows.length"
                                aria-label="Selecionar todos"
                                @change="toggleSelectAll"
                            />
                        </th>
                        <th class="whitespace-nowrap">
                            Data
                        </th>
                        <th class="min-w-[180px]">
                            Nome
                        </th>
                        <th>
                            E-mail
                        </th>
                        <th
                            v-if="showProductColumn"
                        >
                            Produto
                        </th>
                        <th class="ep-num">
                            Comissão
                        </th>
                        <th class="whitespace-nowrap">
                            Status
                        </th>
                        <th class="w-12 !px-2">
                            <span class="sr-only">Ações</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in rows"
                        :key="row.id"
                        :class="selectedIds.has(row.id) ? 'bg-[color-mix(in_oklab,var(--ep-accent)_8%,transparent)]' : ''"
                    >
                        <td v-if="selectable" class="!pr-0">
                            <input
                                type="checkbox"
                                class="h-4 w-4 cursor-pointer rounded-[5px] align-middle accent-[var(--ep-accent)]"
                                :checked="selectedIds.has(row.id)"
                                :aria-label="`Selecionar ${row.name || row.email}`"
                                @change="toggleRow(row.id)"
                            />
                        </td>
                        <td class="whitespace-nowrap !text-[12.5px] tabular-nums !text-[var(--ep-text-3)]">
                            {{ formatDate(row.created_at) }}
                        </td>
                        <td>
                            <div class="flex min-w-0 items-center gap-3">
                                <span v-avatar="row.name || row.email" class="ep-avatar shrink-0 !h-8 !w-8" aria-hidden="true">
                                    {{ String(row.name || row.email || '?').trim().split(/\s+/).filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase() || '?' }}
                                </span>
                                <span class="truncate text-[13px] font-medium text-[var(--ep-text)]">
                                    {{ row.name || '—' }}
                                </span>
                            </div>
                        </td>
                        <td class="max-w-[220px] truncate">
                            <a
                                v-if="row.email"
                                :href="`mailto:${row.email}`"
                                class="text-[12.5px] text-[var(--ep-text-2)] transition-colors duration-150 hover:text-[var(--ep-accent)]"
                                :title="row.email"
                            >
                                {{ row.email }}
                            </a>
                            <span v-else class="text-[var(--ep-text-4)]">—</span>
                        </td>
                        <td
                            v-if="showProductColumn"
                            class="max-w-[200px] truncate !text-[var(--ep-text-2)]"
                            :title="row.product_name"
                        >
                            {{ row.product_name || '—' }}
                        </td>
                        <td class="ep-num">
                            <span class="text-[13.5px] font-semibold tracking-[-0.01em] text-[var(--ep-text)]">{{ formatCommission(row.commission_percent) }}</span>
                        </td>
                        <td class="whitespace-nowrap">
                            <span
                                class="ep-chip"
                                :class="statusBadgeClasses[row.status] ?? ({ approved: 'ep-chip--pos', active: 'ep-chip--pos', pending: 'ep-chip--warn', rejected: 'ep-chip--neg' })[row.status]"
                            >
                                <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                                {{ statusLabel(row.status) }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap !px-2 text-right">
                            <div class="relative inline-flex" :data-partner-menu="row.id">
                                <button
                                    type="button"
                                    class="ep-btn-ghost ep-btn-icon !h-8 !w-8 !rounded-[10px] aria-expanded:bg-[var(--ep-active)] aria-expanded:text-[var(--ep-text)]"
                                    aria-label="Abrir menu de ações"
                                    :aria-expanded="openMenuId === row.id"
                                    @click="toggleMenu(row.id, $event)"
                                >
                                    <MoreVertical class="h-4 w-4" stroke-width="1.75" aria-hidden="true" />
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!rows.length" class="hover:!bg-transparent">
                        <td
                            :colspan="selectable ? (showProductColumn ? 8 : 7) : (showProductColumn ? 7 : 6)"
                            class="!p-0"
                        >
                            <div class="ep-empty">
                                <span class="ep-kpi__icon mb-1.5" aria-hidden="true">
                                    <Users class="h-4 w-4" stroke-width="1.75" />
                                </span>
                                <p class="ep-empty__title">{{ emptyLabel }}</p>
                                <p class="ep-empty__text">Assim que alguém entrar, aparece aqui com comissão e status.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Teleport to="body">
            <div
                v-if="openMenuId != null"
                ref="menuEl"
                class="fixed z-[100000] w-48 overflow-hidden rounded-[14px] border border-[var(--ep-glass-border)] border-t-[var(--ep-glass-border-top)] bg-[var(--ep-drawer)] p-1 text-[13px] shadow-[var(--ep-shadow-pop)] backdrop-blur-2xl backdrop-saturate-150 [&>button]:rounded-[9px]"
                :style="{ top: `${menuPos.top}px`, left: `${menuPos.left}px` }"
                role="menu"
            >
                <slot name="menu" :row="rows.find((r) => r.id === openMenuId)" :close="closeMenu" />
            </div>
        </Teleport>
    </div>
</template>
