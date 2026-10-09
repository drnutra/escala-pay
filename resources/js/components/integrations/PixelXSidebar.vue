<script setup>
import { computed, ref, watch } from 'vue';
import axios from 'axios';
import Button from '@/components/ui/Button.vue';
import Checkbox from '@/components/ui/Checkbox.vue';
import Toggle from '@/components/ui/Toggle.vue';
import {
    X,
    Plus,
    Trash2,
    Send,
    ArrowLeft,
    Loader2,
    Settings,
} from 'lucide-vue-next';

const props = defineProps({
    open: { type: Boolean, default: false },
    pixel_x_integrations: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'saved']);

const PIXEL_X_EVENTS = [
    { slug: 'pedido_pendente', label: 'Pedido pendente' },
    { slug: 'pedido_pago', label: 'Pedido pago' },
    { slug: 'pagamento_recusado', label: 'Pagamento recusado' },
    { slug: 'reembolso', label: 'Reembolso' },
    { slug: 'pix_gerado', label: 'Pix gerado' },
    { slug: 'boleto_gerado', label: 'Boleto gerado' },
    { slug: 'carrinho_abandonado', label: 'Carrinho abandonado' },
    { slug: 'assinatura_criada', label: 'Assinatura criada' },
    { slug: 'assinatura_renovada', label: 'Assinatura renovada' },
    { slug: 'assinatura_cancelada', label: 'Assinatura cancelada' },
];

const editingIntegration = ref(null);
const isCreating = ref(false);
const logsIntegration = ref(null);
const selectedLog = ref(null);

const form = ref({
    name: '',
    url: '',
    token: '',
    is_active: true,
    events: [],
    product_ids: [],
});

const saving = ref(false);
const deleting = ref(null);
const confirmingDeleteId = ref(null);
const errorMessage = ref(null);
const logs = ref([]);
const loadingLogs = ref(false);
const testingIntegrationId = ref(null);
const testResult = ref(null);
const loadingLogDetail = ref(false);
const logDetailModal = ref(false);

const showingForm = computed(
    () => editingIntegration.value !== null || isCreating.value
);

const currentView = computed(() => {
    if (showingForm.value) {
        return 'form';
    }
    if (logsIntegration.value) {
        return 'logs';
    }
    return 'hub';
});

const headerTitle = computed(() => {
    if (currentView.value === 'form') {
        return editingIntegration.value ? 'Editar integração' : 'Nova integração';
    }
    if (currentView.value === 'logs' && logsIntegration.value) {
        return `Logs — ${logsIntegration.value.name}`;
    }
    return 'Pixel X';
});

const activeStatus = computed(() =>
    (props.pixel_x_integrations || []).some((i) => i.configured && i.is_active)
);

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) {
            resetForm();
            logsIntegration.value = null;
            testResult.value = null;
        }
    }
);

function resetForm() {
    editingIntegration.value = null;
    isCreating.value = false;
    confirmingDeleteId.value = null;
    form.value = {
        name: '',
        url: '',
        token: '',
        is_active: true,
        events: [],
        product_ids: [],
    };
    errorMessage.value = null;
}

function startNew() {
    logsIntegration.value = null;
    editingIntegration.value = null;
    isCreating.value = true;
    form.value = {
        name: '',
        url: '',
        token: '',
        is_active: true,
        events: [],
        product_ids: [],
    };
    errorMessage.value = null;
    testResult.value = null;
}

function editIntegration(integration) {
    logsIntegration.value = null;
    isCreating.value = false;
    editingIntegration.value = integration;
    form.value = {
        name: integration.name,
        url: integration.url,
        token: '', // Nunca pré-popular o token por segurança
        is_active: integration.is_active ?? true,
        events: [...(integration.events || [])],
        product_ids: (integration.product_ids || []),
    };
    errorMessage.value = null;
    testResult.value = null;
}

function cancelEdit() {
    resetForm();
}

function toggleEvent(slug) {
    const idx = form.value.events.indexOf(slug);
    if (idx >= 0) {
        form.value.events.splice(idx, 1);
    } else {
        form.value.events.push(slug);
    }
}

function isEventSelected(slug) {
    return form.value.events.includes(slug);
}

function toggleProduct(productId) {
    const idx = form.value.product_ids.indexOf(productId);
    if (idx >= 0) {
        form.value.product_ids.splice(idx, 1);
    } else {
        form.value.product_ids.push(productId);
    }
}

function isProductSelected(productId) {
    return form.value.product_ids.includes(productId);
}

async function saveIntegration() {
    errorMessage.value = null;
    if (!form.value.name?.trim()) {
        errorMessage.value = 'Informe o nome da integração.';
        return;
    }
    if (!form.value.url?.trim()) {
        errorMessage.value = 'Informe a URL do webhook da Pixel X.';
        return;
    }
    if (!editingIntegration.value && !form.value.token?.trim()) {
        errorMessage.value = 'Informe o token da Pixel X.';
        return;
    }
    if (form.value.events.length === 0) {
        errorMessage.value = 'Selecione pelo menos um evento.';
        return;
    }

    saving.value = true;
    try {
        const payload = {
            name: form.value.name.trim(),
            url: form.value.url.trim(),
            events: form.value.events,
            is_active: form.value.is_active,
            product_ids: form.value.product_ids,
        };
        // Enviar token apenas se preenchido (ao editar, em branco = manter o atual)
        if (form.value.token?.trim()) {
            payload.token = form.value.token.trim();
        }

        if (editingIntegration.value) {
            await axios.put(
                `/integracoes/pixel-x/${editingIntegration.value.id}`,
                payload
            );
        } else {
            await axios.post('/integracoes/pixel-x', payload);
        }
        emit('saved');
        resetForm();
    } catch (err) {
        const data = err.response?.data;
        const firstValidationError = data?.errors
            ? Object.values(data.errors).flat().find(Boolean)
            : null;
        errorMessage.value =
            firstValidationError ||
            data?.message ||
            'Erro ao salvar integração.';
    } finally {
        saving.value = false;
    }
}

async function deleteIntegration(id) {
    deleting.value = id;
    confirmingDeleteId.value = null;
    try {
        await axios.delete(`/integracoes/pixel-x/${id}`);
        emit('saved');
        if (logsIntegration.value?.id === id) {
            logsIntegration.value = null;
        }
        if (editingIntegration.value?.id === id) {
            resetForm();
        }
    } catch (err) {
        errorMessage.value =
            err.response?.data?.message || 'Erro ao excluir integração.';
    } finally {
        deleting.value = null;
    }
}

function requestDelete(integration) {
    confirmingDeleteId.value = integration.id;
}

function cancelDelete() {
    confirmingDeleteId.value = null;
}

function openLogs(integration) {
    logsIntegration.value = integration;
    logs.value = [];
    loadLogs();
}

function backToHub() {
    logsIntegration.value = null;
    logs.value = [];
}

async function loadLogs() {
    if (!logsIntegration.value) return;
    loadingLogs.value = true;
    try {
        const { data } = await axios.get(
            `/integracoes/pixel-x/${logsIntegration.value.id}/logs`
        );
        logs.value = data.logs || [];
    } catch {
        logs.value = [];
    } finally {
        loadingLogs.value = false;
    }
}

async function openLogDetail(log) {
    if (log.request_payload !== undefined && log.response_body !== undefined) {
        selectedLog.value = log;
        logDetailModal.value = true;
        return;
    }
    loadingLogDetail.value = true;
    selectedLog.value = null;
    logDetailModal.value = true;
    try {
        const { data } = await axios.get(
            `/integracoes/pixel-x/${logsIntegration.value.id}/logs/${log.id}`
        );
        selectedLog.value = data.log;
    } catch {
        selectedLog.value = null;
    } finally {
        loadingLogDetail.value = false;
    }
}

function closeLogDetail() {
    logDetailModal.value = false;
    selectedLog.value = null;
}

async function testIntegration(id) {
    testingIntegrationId.value = id;
    testResult.value = null;
    try {
        const { data } = await axios.post(`/integracoes/pixel-x/${id}/test`);
        testResult.value = { success: data.success, message: data.message };
    } catch (err) {
        testResult.value = {
            success: false,
            message: err.response?.data?.message || 'Erro ao disparar evento de teste.',
        };
    } finally {
        testingIntegrationId.value = null;
    }
}

function closeSidebar() {
    emit('close');
    resetForm();
}

function formatLogDate(iso) {
    if (!iso) return '–';
    const d = new Date(iso);
    return d.toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatPayload(obj) {
    if (obj == null) return '–';
    try {
        if (typeof obj === 'string') {
            const trimmed = obj.trim();
            if (trimmed === '') return '–';
            if (
                (trimmed.startsWith('{') && trimmed.endsWith('}')) ||
                (trimmed.startsWith('[') && trimmed.endsWith(']'))
            ) {
                return JSON.stringify(JSON.parse(trimmed), null, 2);
            }
            return obj;
        }
        return JSON.stringify(obj, null, 2);
    } catch {
        return String(obj);
    }
}

function truncateUrl(url, max = 40) {
    if (!url) return '';
    if (url.length <= max) return url;
    return url.slice(0, max) + '…';
}
</script>

<template>
    <Teleport to="body">
        <div
            v-show="open"
            class="fixed inset-0 z-[100000] flex justify-end"
            aria-modal="true"
            role="dialog"
        >
            <div
                class="ep-scrim fixed inset-0"
                aria-hidden="true"
                @click="closeSidebar"
            />
            <aside
                class="ep-drawer relative flex h-full w-full max-w-md flex-col"
            >
                <!-- Header -->
                <header
                    class="flex items-center justify-between gap-3 border-b border-[var(--ep-line)] px-6 py-4"
                >
                    <div class="flex min-w-0 items-center gap-2">
                        <button
                            v-if="currentView !== 'hub'"
                            type="button"
                            class="ep-btn-ghost ep-btn-icon -ml-2 shrink-0"
                            title="Voltar"
                            @click="currentView === 'logs' ? backToHub() : cancelEdit()"
                        >
                            <ArrowLeft class="h-[18px] w-[18px]" :stroke-width="1.75" />
                        </button>
                        <h2 class="truncate text-[17px] font-semibold tracking-[-0.02em] text-[var(--ep-text)]">
                            {{ headerTitle }}
                        </h2>
                    </div>
                    <button
                        type="button"
                        class="ep-btn-ghost ep-btn-icon shrink-0"
                        aria-label="Fechar"
                        @click="closeSidebar"
                    >
                        <X class="h-[18px] w-[18px]" :stroke-width="1.75" />
                    </button>
                </header>

                <div class="flex flex-1 flex-col overflow-y-auto">
                    <!-- View: hub -->
                    <template v-if="currentView === 'hub'">
                        <div class="space-y-4 px-6 pt-5">
                            <p class="text-[13px] leading-relaxed text-[var(--ep-text-3)]">
                                Envie os eventos mapeados para a Pixel X com token e payload proprietário.
                            </p>

                            <Button @click="startNew">
                                <Plus class="h-4 w-4" :stroke-width="1.75" />
                                Nova integração
                            </Button>
                        </div>

                        <div class="flex-1 px-6 pb-6 pt-6">
                            <h3 class="ep-section-title mb-3 flex items-center gap-2">
                                Minhas integrações
                                <span class="ep-chip tabular-nums">{{ pixel_x_integrations.length }}</span>
                            </h3>

                            <ul
                                v-if="pixel_x_integrations.length > 0"
                                class="panel-card ep-data divide-y divide-[var(--ep-line)] overflow-hidden"
                            >
                                <li
                                    v-for="integration in pixel_x_integrations"
                                    :key="integration.id"
                                    class="transition-colors duration-150 hover:bg-[var(--ep-hover)]"
                                >
                                    <div class="flex flex-col gap-3 px-4 py-4">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-[14px] font-medium tracking-[-0.01em] text-[var(--ep-text)]">
                                                    {{ integration.name }}
                                                </span>
                                                <span
                                                    class="ep-chip"
                                                    :class="
                                                        integration.is_active
                                                            ? 'ep-chip--pos'
                                                            : 'ep-chip--warn'
                                                    "
                                                >
                                                    <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                                                    {{ integration.is_active ? 'Ativo' : 'Inativo' }}
                                                </span>
                                            </div>
                                            <div
                                                class="mt-1 truncate font-mono text-[12px] text-[var(--ep-text-3)]"
                                                :title="integration.url"
                                            >
                                                {{ truncateUrl(integration.url, 52) }}
                                            </div>
                                            <div class="mt-1.5 text-[12px] tabular-nums text-[var(--ep-text-4)]">
                                                {{ (integration.events || []).length }} evento(s)
                                            </div>
                                        </div>

                                        <div class="-ml-2 flex shrink-0 flex-wrap items-center gap-1">
                                            <template v-if="confirmingDeleteId === integration.id">
                                                <span class="ml-2 mr-1 text-[12.5px] font-medium text-[var(--ep-text-2)]">Excluir?</span>
                                                <button
                                                    type="button"
                                                    class="ep-btn-ghost !h-8 !px-3 text-[12.5px]"
                                                    @click.stop="cancelDelete()"
                                                >
                                                    Cancelar
                                                </button>
                                                <button
                                                    type="button"
                                                    class="ep-btn-danger !h-8 !gap-1.5 !px-3 text-[12.5px]"
                                                    :disabled="deleting === integration.id"
                                                    @click.stop="deleteIntegration(integration.id)"
                                                >
                                                    <Loader2 v-if="deleting === integration.id" class="h-3.5 w-3.5 animate-spin" :stroke-width="1.75" />
                                                    <Trash2 v-else class="h-3.5 w-3.5" :stroke-width="1.75" />
                                                    {{ deleting === integration.id ? 'Excluindo...' : 'Excluir' }}
                                                </button>
                                            </template>
                                            <template v-else>
                                                <button
                                                    type="button"
                                                    class="ep-btn-ghost !h-8 !px-3 text-[12.5px]"
                                                    @click.stop="openLogs(integration)"
                                                >
                                                    Ver logs
                                                </button>
                                                <button
                                                    type="button"
                                                    class="ep-btn-ghost !h-8 !gap-1.5 !px-3 text-[12.5px] !text-[var(--ep-accent)]"
                                                    title="Disparar evento de teste"
                                                    :disabled="testingIntegrationId === integration.id"
                                                    @click.stop="testIntegration(integration.id)"
                                                >
                                                    <Loader2
                                                        v-if="testingIntegrationId === integration.id"
                                                        class="h-3.5 w-3.5 animate-spin"
                                                        :stroke-width="1.75"
                                                    />
                                                    <Send v-else class="h-3.5 w-3.5" :stroke-width="1.75" />
                                                    Testar
                                                </button>
                                                <button
                                                    type="button"
                                                    class="ep-btn-ghost ep-btn-icon !h-8 !w-8"
                                                    title="Configurar"
                                                    @click.stop="editIntegration(integration)"
                                                >
                                                    <Settings class="h-4 w-4" :stroke-width="1.75" />
                                                </button>
                                                <button
                                                    type="button"
                                                    class="ep-btn-ghost ep-btn-icon !h-8 !w-8 hover:!text-[var(--ep-neg)]"
                                                    title="Excluir"
                                                    :disabled="deleting === integration.id"
                                                    @click.stop="requestDelete(integration)"
                                                >
                                                    <Trash2 class="h-4 w-4" :stroke-width="1.75" />
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </li>
                            </ul>

                            <div
                                v-else
                                class="panel-card ep-empty"
                            >
                                <p class="ep-empty__title">Nenhuma integração configurada</p>
                                <p class="ep-empty__text">Clique em "Nova integração" para criar.</p>
                            </div>

                            <p
                                v-if="testResult"
                                :class="[
                                    'mt-3 rounded-xl border px-3.5 py-2.5 text-[13px]',
                                    testResult.success
                                        ? 'border-[color-mix(in_oklab,var(--ep-pos)_30%,transparent)] bg-[var(--ep-pos-bg)] text-[var(--ep-pos)]'
                                        : 'border-[color-mix(in_oklab,var(--ep-neg)_30%,transparent)] bg-[var(--ep-neg-bg)] text-[var(--ep-neg)]',
                                ]"
                            >
                                {{ testResult.message }}
                            </p>
                        </div>
                    </template>

                    <!-- View: logs -->
                    <template v-else-if="currentView === 'logs' && logsIntegration">
                        <div class="flex-1 px-6 pb-6 pt-5">
                            <div
                                v-if="loadingLogs"
                                class="flex items-center justify-center gap-2 py-12 text-[13px] text-[var(--ep-text-3)]"
                            >
                                <Loader2 class="h-[18px] w-[18px] animate-spin" :stroke-width="1.75" />
                                Carregando logs...
                            </div>
                            <div
                                v-else-if="logs.length === 0"
                                class="panel-card ep-empty"
                            >
                                <p class="ep-empty__title">Nenhum log encontrado.</p>
                                <p class="ep-empty__text">Use "Testar" na lista de integrações para gerar o primeiro envio.</p>
                            </div>
                            <div v-else class="panel-card ep-data overflow-hidden">
                                <table class="ep-table">
                                    <thead>
                                        <tr>
                                            <th class="!px-3">Horário</th>
                                            <th class="!px-3">Evento</th>
                                            <th class="!px-3">Status</th>
                                            <th class="!px-3">Origem</th>
                                            <th class="!px-3"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="log in logs"
                                            :key="log.id"
                                            class="cursor-pointer"
                                            @click="openLogDetail(log)"
                                        >
                                            <td class="whitespace-nowrap !px-3 text-[12px] tabular-nums !text-[var(--ep-text-3)]">
                                                {{ formatLogDate(log.created_at) }}
                                            </td>
                                            <td class="!px-3 font-medium">
                                                {{ log.event_label || log.event }}
                                            </td>
                                            <td class="!px-3">
                                                <span
                                                    class="ep-chip tabular-nums"
                                                    :class="
                                                        log.success
                                                            ? 'ep-chip--pos'
                                                            : 'ep-chip--neg'
                                                    "
                                                >
                                                    {{ log.success ? (log.response_status || 'OK') : (log.response_status || 'Erro') }}
                                                </span>
                                            </td>
                                            <td class="!px-3 !text-[var(--ep-text-3)]">
                                                {{ log.source === 'test' ? 'Teste' : 'Automático' }}
                                            </td>
                                            <td class="!px-3 text-right">
                                                <button
                                                    type="button"
                                                    class="ep-btn-ghost !h-7 !px-2.5 text-[12px]"
                                                    @click.stop="openLogDetail(log)"
                                                >
                                                    Detalhes
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>

                    <!-- View: form (criar/editar) -->
                    <div
                        v-else-if="currentView === 'form'"
                        class="flex flex-1 flex-col gap-4 px-6 py-5"
                    >
                        <div class="space-y-4">
                            <section class="panel-card space-y-4 p-5">
                                <h3 class="ep-section-title">Conexão</h3>
                                <!-- Nome -->
                                <div>
                                    <label class="ep-label">
                                        Nome
                                    </label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        placeholder="Ex: Pixel X Principal"
                                        class="ep-input"
                                    />
                                </div>

                                <!-- URL do Webhook -->
                                <div>
                                    <label class="ep-label">
                                        URL do Webhook
                                    </label>
                                    <input
                                        v-model="form.url"
                                        type="url"
                                        placeholder="https://app.pixelx.com.br/api/..."
                                        class="ep-input font-mono !text-[13px]"
                                    />
                                </div>

                                <!-- Token -->
                                <div>
                                    <label class="ep-label">
                                        Token
                                        <span v-if="editingIntegration" class="font-normal text-[var(--ep-text-4)]">(opcional)</span>
                                    </label>
                                    <input
                                        v-model="form.token"
                                        type="password"
                                        :placeholder="editingIntegration ? 'Deixe vazio para manter o token atual' : 'Token da Pixel X'"
                                        autocomplete="new-password"
                                        class="ep-input"
                                    />
                                    <p v-if="editingIntegration" class="ep-help">
                                        Por segurança, o token salvo não é exibido. Deixe em branco para manter o atual.
                                    </p>
                                </div>
                            </section>

                            <!-- Eventos -->
                            <section class="panel-card p-5">
                                <div class="mb-3 flex items-center justify-between gap-3">
                                    <label class="ep-section-title">
                                        Eventos
                                    </label>
                                    <span class="ep-chip tabular-nums">{{ form.events.length }}</span>
                                </div>
                                <div class="max-h-56 space-y-2.5 overflow-y-auto rounded-xl border border-[var(--ep-input-border)] bg-[var(--ep-input)] p-3.5">
                                    <Checkbox
                                        v-for="event in PIXEL_X_EVENTS"
                                        :key="event.slug"
                                        :model-value="isEventSelected(event.slug)"
                                        :label="event.label"
                                        class="block"
                                        @update:model-value="toggleEvent(event.slug)"
                                    />
                                </div>
                            </section>

                            <!-- Produtos -->
                            <section class="panel-card p-5">
                                <label class="ep-section-title mb-3 block">
                                    Produtos
                                    <span class="font-normal text-[var(--ep-text-4)]">(opcional - deixe vazio para todos)</span>
                                </label>
                                <div class="max-h-44 space-y-2.5 overflow-y-auto rounded-xl border border-[var(--ep-input-border)] bg-[var(--ep-input)] p-3.5">
                                    <template v-if="products.length > 0">
                                        <Checkbox
                                            v-for="product in products"
                                            :key="product.id"
                                            :model-value="isProductSelected(product.id)"
                                            :label="product.name"
                                            class="block"
                                            @update:model-value="toggleProduct(product.id)"
                                        />
                                    </template>
                                    <p
                                        v-else
                                        class="py-2 text-center text-[12.5px] text-[var(--ep-text-4)]"
                                    >
                                        Nenhum produto cadastrado
                                    </p>
                                </div>
                            </section>

                            <!-- Toggle ativo/inativo -->
                            <section class="panel-card px-5 py-4">
                                <Toggle
                                    v-model="form.is_active"
                                    label="Ativo"
                                />
                            </section>
                        </div>

                        <p
                            v-if="errorMessage"
                            class="rounded-xl border border-[color-mix(in_oklab,var(--ep-neg)_30%,transparent)] bg-[var(--ep-neg-bg)] px-3.5 py-2.5 text-[13px] text-[var(--ep-neg)]"
                        >
                            {{ errorMessage }}
                        </p>

                        <div class="mt-auto flex justify-end gap-2 border-t border-[var(--ep-line)] pt-4">
                            <Button
                                variant="outline"
                                :disabled="saving"
                                @click="cancelEdit"
                            >
                                Cancelar
                            </Button>
                            <Button :disabled="saving" @click="saveIntegration">
                                {{ saving ? 'Salvando...' : 'Salvar' }}
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Modal: detalhe do log -->
                <div
                    v-if="logDetailModal"
                    class="ep-scrim absolute inset-0 z-10 flex items-center justify-center p-4"
                    @click.self="closeLogDetail"
                >
                    <div
                        class="ep-modal flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden"
                        role="dialog"
                        aria-labelledby="log-detail-title"
                    >
                        <div class="flex items-center justify-between gap-3 border-b border-[var(--ep-line)] px-5 py-4">
                            <h3 id="log-detail-title" class="text-[15px] font-semibold tracking-[-0.015em] text-[var(--ep-text)]">
                                Detalhe do envio
                            </h3>
                            <button
                                type="button"
                                class="ep-btn-ghost ep-btn-icon -mr-2"
                                aria-label="Fechar"
                                @click="closeLogDetail"
                            >
                                <X class="h-[18px] w-[18px]" :stroke-width="1.75" />
                            </button>
                        </div>
                        <div class="flex-1 overflow-y-auto px-5 py-5">
                            <div v-if="loadingLogDetail" class="flex items-center justify-center py-12">
                                <Loader2 class="h-6 w-6 animate-spin text-[var(--ep-text-3)]" :stroke-width="1.75" />
                            </div>
                            <template v-else-if="selectedLog">
                                <div class="mb-5 flex flex-wrap items-center gap-2">
                                    <span class="mr-1 text-[14px] font-medium tracking-[-0.01em] text-[var(--ep-text)]">
                                        {{ selectedLog.event_label || selectedLog.event }}
                                    </span>
                                    <span
                                        :class="[
                                            'ep-chip tabular-nums',
                                            selectedLog.success
                                                ? 'ep-chip--pos'
                                                : 'ep-chip--neg',
                                        ]"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                                        {{ selectedLog.success ? 'Sucesso' : 'Falha' }}
                                        <span v-if="selectedLog.response_status != null">
                                            (HTTP {{ selectedLog.response_status }})
                                        </span>
                                    </span>
                                    <span v-if="selectedLog.source === 'test'" class="ep-chip">
                                        Teste manual
                                    </span>
                                    <span class="text-[12px] tabular-nums text-[var(--ep-text-4)]">
                                        {{ formatLogDate(selectedLog.created_at) }}
                                    </span>
                                </div>
                                <p
                                    v-if="selectedLog.error_message"
                                    class="mb-4 rounded-xl border border-[color-mix(in_oklab,var(--ep-neg)_30%,transparent)] bg-[var(--ep-neg-bg)] px-3.5 py-2.5 text-[13px] text-[var(--ep-neg)]"
                                >
                                    {{ selectedLog.error_message }}
                                </p>
                                <div class="space-y-5">
                                    <div>
                                        <span class="ep-section-title mb-2 block">
                                            Payload enviado (request)
                                        </span>
                                        <pre class="max-h-64 overflow-auto rounded-xl border border-[var(--ep-input-border)] bg-[var(--ep-input)] p-4 font-mono text-[12px] leading-relaxed text-[var(--ep-text-2)]">{{ formatPayload(selectedLog.request_payload) }}</pre>
                                    </div>
                                    <div>
                                        <span class="ep-section-title mb-2 block">
                                            Resposta do servidor (response)
                                        </span>
                                        <p v-if="selectedLog.response_status != null" class="mb-2 text-[12px] tabular-nums text-[var(--ep-text-3)]">
                                            Status: {{ selectedLog.response_status }}
                                        </p>
                                        <pre class="max-h-64 overflow-auto rounded-xl border border-[var(--ep-input-border)] bg-[var(--ep-input)] p-4 font-mono text-[12px] leading-relaxed text-[var(--ep-text-2)]">{{ formatPayload(selectedLog.response_body) }}</pre>
                                    </div>
                                </div>
                            </template>
                            <div v-else class="ep-empty">
                                <p class="ep-empty__title">Não foi possível carregar o detalhe do log.</p>
                            </div>
                        </div>
                        <div class="flex justify-end border-t border-[var(--ep-line)] px-5 py-4">
                            <Button variant="outline" size="sm" class="w-full sm:w-auto" @click="closeLogDetail">
                                Fechar
                            </Button>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </Teleport>
</template>
