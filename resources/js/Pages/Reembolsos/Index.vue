<script setup>
import { ref, reactive } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import Button from '@/components/ui/Button.vue';
import { ChevronDown } from 'lucide-vue-next';

defineOptions({ layout: LayoutInfoprodutor });

const props = defineProps({
    requests: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    products: { type: Array, default: () => [] },
    status_options: { type: Array, default: () => [] },
    can_manage: { type: Boolean, default: false },
});

const page = usePage();
const localFilters = reactive({
    status: props.filters.status ?? 'all',
    product_id: props.filters.product_id ?? '',
});
const actionId = ref(null);
const notesModalOpen = ref(false);
const notesModalAction = ref('approve');
const notesModalRequestId = ref(null);

const notesForm = useForm({ admin_notes: '' });

function applyFilters() {
    router.get('/reembolsos', {
        status: localFilters.status,
        product_id: localFilters.product_id || undefined,
    }, { preserveState: true, replace: true });
}

function onStatusChange(e) {
    localFilters.status = e.target.value;
    applyFilters();
}

function onProductChange(e) {
    localFilters.product_id = e.target.value;
    applyFilters();
}

function openNotesModal(requestId, action) {
    notesModalRequestId.value = requestId;
    notesModalAction.value = action;
    notesForm.admin_notes = '';
    notesForm.clearErrors();
    notesModalOpen.value = true;
}

function closeNotesModal() {
    notesModalOpen.value = false;
}

function submitNotesAction() {
    if (!notesModalRequestId.value) return;
    const url = notesModalAction.value === 'approve'
        ? `/reembolsos/${notesModalRequestId.value}/approve`
        : `/reembolsos/${notesModalRequestId.value}/reject`;
    actionId.value = notesModalRequestId.value;
    notesForm.post(url, {
        preserveScroll: true,
        onFinish: () => {
            actionId.value = null;
            closeNotesModal();
        },
    });
}

function formatMoney(amount, currency) {
    const cur = (currency || 'BRL').toUpperCase();
    try {
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: cur }).format(amount);
    } catch {
        return `R$ ${Number(amount).toFixed(2)}`;
    }
}
</script>

<template>
    <div class="space-y-5">
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-[22px] font-semibold tracking-[-0.025em] text-[var(--ep-text)]">Reembolsos</h1>
                <p class="mt-1 text-[13px] text-[var(--ep-text-3)]">Solicitações de reembolso feitas pelos alunos na área de membros.</p>
            </div>
        </header>

        <div
            v-if="page.props.flash?.success"
            class="flex items-start gap-2 rounded-xl border border-[color-mix(in_oklab,var(--ep-pos)_30%,transparent)] bg-[var(--ep-pos-bg)] px-4 py-2.5 text-[13px] text-[var(--ep-pos)]"
            role="status"
        >
            <span class="mt-[7px] h-1.5 w-1.5 shrink-0 rounded-full bg-current" aria-hidden="true" />
            {{ page.props.flash.success }}
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <div class="relative">
                <select
                    :value="localFilters.status"
                    aria-label="Filtrar por status"
                    class="ep-input w-auto min-w-[170px] appearance-none pr-9"
                    @change="onStatusChange"
                >
                    <option v-for="opt in status_options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
                <ChevronDown class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--ep-text-4)]" :stroke-width="1.75" aria-hidden="true" />
            </div>
            <div class="relative">
                <select
                    :value="localFilters.product_id"
                    aria-label="Filtrar por produto"
                    class="ep-input w-auto min-w-[220px] appearance-none pr-9"
                    @change="onProductChange"
                >
                    <option value="">Todos os produtos</option>
                    <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
                <ChevronDown class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--ep-text-4)]" :stroke-width="1.75" aria-hidden="true" />
            </div>
        </div>

        <div class="panel-card ep-data overflow-hidden">
            <div class="overflow-x-auto">
                <table class="ep-table min-w-[880px]">
                    <thead>
                        <tr>
                            <th>Solicitado em</th>
                            <th>Aluno</th>
                            <th>Produto</th>
                            <th>Pedido</th>
                            <th>Status</th>
                            <th>Motivo</th>
                            <th v-if="can_manage" class="ep-num">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in requests.data" :key="row.id">
                            <td class="whitespace-nowrap text-[12.5px] tabular-nums text-[var(--ep-text-3)]">
                                {{ row.created_at ? new Date(row.created_at).toLocaleString('pt-BR') : '—' }}
                            </td>
                            <td>
                                <div class="flex min-w-0 items-center gap-3">
                                    <span v-avatar="row.user?.name" class="ep-avatar shrink-0" aria-hidden="true">{{ String(row.user?.name || '?').trim().charAt(0).toUpperCase() }}</span>
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-[var(--ep-text)]">{{ row.user?.name }}</p>
                                        <p class="truncate text-[12px] text-[var(--ep-text-4)]">{{ row.user?.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="text-[var(--ep-text-2)]">{{ row.product?.name }}</td>
                            <td class="whitespace-nowrap">
                                <span class="font-medium tabular-nums text-[var(--ep-text)]">#{{ row.order?.id }}</span>
                                <span class="block text-[12px] tabular-nums text-[var(--ep-text-3)]">
                                    {{ formatMoney(row.order?.amount, row.order?.currency) }} · {{ row.order?.payment_label }}
                                </span>
                            </td>
                            <td>
                                <span
                                    class="ep-chip"
                                    :class="{
                                        'ep-chip--warn': row.status === 'pending',
                                        'ep-chip--accent': row.status === 'processing',
                                        'ep-chip--pos': row.status === 'completed',
                                        'ep-chip--neg': row.status === 'rejected' || row.status === 'failed',
                                    }"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                                    {{ row.status_label }}
                                </span>
                                <span
                                    v-if="row.needs_manual_gateway"
                                    class="mt-1.5 block max-w-[220px] text-[11.5px] text-[var(--ep-warn)]"
                                >
                                    Aguardando estorno manual no gateway
                                </span>
                                <span v-if="row.failure_reason" class="mt-1.5 block max-w-[220px] text-[11.5px] text-[var(--ep-neg)]">{{ row.failure_reason }}</span>
                            </td>
                            <td class="max-w-xs text-[var(--ep-text-3)]">
                                <p class="line-clamp-3 text-[12.5px] leading-relaxed">{{ row.reason }}</p>
                            </td>
                            <td v-if="can_manage" class="ep-num">
                                <div v-if="row.can_approve || row.can_reject" class="flex justify-end gap-2">
                                    <Button
                                        v-if="row.can_approve"
                                        type="button"
                                        size="sm"
                                        :disabled="actionId === row.id"
                                        @click="openNotesModal(row.id, 'approve')"
                                    >
                                        Aprovar
                                    </Button>
                                    <Button
                                        v-if="row.can_reject"
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        :disabled="actionId === row.id"
                                        @click="openNotesModal(row.id, 'reject')"
                                    >
                                        Rejeitar
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!requests.data?.length" class="hover:!bg-transparent">
                            <td :colspan="can_manage ? 7 : 6">
                                <div class="ep-empty">
                                    <p class="ep-empty__title">Nenhuma solicitação encontrada.</p>
                                    <p class="ep-empty__text">Ajuste os filtros de status ou produto. Novos pedidos de reembolso dos alunos aparecem aqui.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <nav v-if="requests.links?.length > 3" class="flex flex-wrap items-center gap-1" aria-label="Paginação">
            <template v-for="(link, i) in requests.links" :key="i">
                <button
                    v-if="link.url"
                    type="button"
                    class="inline-flex h-8 min-w-8 items-center justify-center rounded-[10px] border px-3 text-[13px] font-medium tabular-nums transition-colors duration-150"
                    :class="link.active
                        ? 'border-[var(--ep-line-strong)] bg-[var(--ep-active)] text-[var(--ep-text)] shadow-[var(--ep-glass-highlight)]'
                        : 'border-transparent text-[var(--ep-text-3)] hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)]'"
                    v-html="link.label"
                    @click="router.get(link.url, {}, { preserveState: true })"
                />
            </template>
        </nav>

        <div
            v-if="notesModalOpen"
            class="ep-scrim fixed inset-0 z-50 flex items-center justify-center p-4"
            @click.self="closeNotesModal"
        >
            <div
                class="ep-modal w-full max-w-md p-6"
                role="dialog"
                aria-modal="true"
                aria-labelledby="reembolso-modal-title"
            >
                <h3 id="reembolso-modal-title" class="text-[17px] font-semibold tracking-[-0.02em] text-[var(--ep-text)]">
                    {{ notesModalAction === 'approve' ? 'Aprovar reembolso' : 'Rejeitar solicitação' }}
                </h3>
                <label for="reembolso-admin-notes" class="ep-label mt-5">Observação (opcional)</label>
                <textarea
                    id="reembolso-admin-notes"
                    v-model="notesForm.admin_notes"
                    rows="3"
                    class="ep-input"
                />
                <div v-if="notesForm.errors.admin_notes" class="mt-2 text-[12.5px] text-[var(--ep-neg)]">{{ notesForm.errors.admin_notes }}</div>
                <div class="mt-6 flex gap-2">
                    <Button type="button" variant="outline" class="flex-1" @click="closeNotesModal">Cancelar</Button>
                    <Button type="button" class="flex-1" :disabled="notesForm.processing" @click="submitNotesAction">
                        Confirmar
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
