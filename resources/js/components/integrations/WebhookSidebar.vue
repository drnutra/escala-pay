<script setup>
import { computed, ref, watch } from 'vue';
import axios from 'axios';
import Button from '@/components/ui/Button.vue';
import Checkbox from '@/components/ui/Checkbox.vue';
import Toggle from '@/components/ui/Toggle.vue';
import WebhookKpiStrip from '@/components/integrations/WebhookKpiStrip.vue';
import WebhookPayloadDocsModal from '@/components/integrations/WebhookPayloadDocsModal.vue';
import {
    X,
    Plus,
    Trash2,
    Send,
    ArrowLeft,
    Loader2,
    BookOpen,
    Settings,
    Clock,
} from 'lucide-vue-next';

const props = defineProps({
    open: { type: Boolean, default: false },
    webhooks: { type: Array, default: () => [] },
    webhookEvents: { type: Object, default: () => ({}) },
    webhookEventCatalog: {
        type: Object,
        default: () => ({ groups: [], events: [] }),
    },
    products: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'saved']);

const editingWebhook = ref(null);
const isCreating = ref(false);

const showingForm = computed(
    () => editingWebhook.value !== null || isCreating.value
);

const currentView = computed(() => {
    if (showingForm.value) {
        return 'form';
    }
    if (logsWebhook.value) {
        return 'logs';
    }
    return 'hub';
});

const headerTitle = computed(() => {
    if (currentView.value === 'form') {
        return editingWebhook.value ? 'Editar webhook' : 'Novo webhook';
    }
    if (currentView.value === 'logs' && logsWebhook.value) {
        return `Logs — ${logsWebhook.value.name}`;
    }
    return 'Webhooks';
});

const statsByWebhookId = computed(() => {
    const map = {};
    for (const row of dashboardData.value?.webhooks || []) {
        map[row.id] = row.stats;
    }
    return map;
});

const filteredLogs = computed(() => {
    const id = logsWebhook.value?.id;
    if (!id) {
        return [];
    }
    let list = logsByWebhookId.value[id] || [];
    if (logFilterStatus.value === 'success') {
        list = list.filter((l) => l.success);
    } else if (logFilterStatus.value === 'failed') {
        list = list.filter((l) => !l.success);
    }
    const q = logSearchQuery.value.trim().toLowerCase();
    if (q) {
        list = list.filter(
            (l) =>
                (l.event || '').toLowerCase().includes(q) ||
                (l.event_label || '').toLowerCase().includes(q),
        );
    }
    return list;
});

const form = ref({
    name: '',
    url: '',
    bearer_token: '',
    events: [],
    is_active: true,
    product_ids: [],
});
const saving = ref(false);
const deleting = ref(null);
const confirmingDeleteId = ref(null);
const testing = ref(null);
const testMessage = ref(null);
const testSuccess = ref(null);
const errorMessage = ref(null);

const showTestModal = ref(false);
const testTargetWebhook = ref(null);
const selectedTestEvent = ref('');

const logsByWebhookId = ref({});
const loadingLogs = ref(null);
const logsWebhook = ref(null);
const logFilterStatus = ref('all');
const logSearchQuery = ref('');

const dashboardData = ref(null);
const loadingDashboard = ref(false);
const showPayloadDocsModal = ref(false);
const testWebhookAfterDocs = ref(null);

const logDetailModal = ref(false);
const selectedLogDetail = ref(null);
const loadingLogDetail = ref(false);
const logCopyFeedback = ref('');
const logRequestPreRef = ref(null);
const logResponsePreRef = ref(null);
const resendingLog = ref(false);
const resendMessage = ref('');
const resendSuccess = ref(null);

const eventEntries = ref([]);

watch(
    () => props.webhookEvents,
    (events) => {
        eventEntries.value = Object.entries(events || {});
    },
    { immediate: true }
);

watch(
    () => [props.open, props.webhooks],
    () => {
        if (!props.open) {
            resetForm();
            logsWebhook.value = null;
            showPayloadDocsModal.value = false;
        } else {
            fetchDashboard();
        }
    }
);

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            fetchDashboard();
        }
    },
);

async function fetchDashboard() {
    loadingDashboard.value = true;
    try {
        const { data } = await axios.get('/integracoes/webhooks/dashboard-stats');
        dashboardData.value = data;
    } catch {
        dashboardData.value = null;
    } finally {
        loadingDashboard.value = false;
    }
}

function statsForWebhook(w) {
    return (
        statsByWebhookId.value[w.id] || {
            sent: 0,
            delivered: 0,
            failed: 0,
            success_rate: 0,
            last_sent_at: null,
        }
    );
}

function formatRelativeTime(iso) {
    if (!iso) {
        return 'Nenhum envio nas últimas 24h';
    }
    const d = new Date(iso);
    const diffMs = Date.now() - d.getTime();
    const mins = Math.floor(diffMs / 60000);
    if (mins < 1) {
        return 'Último envio: agora';
    }
    if (mins < 60) {
        return `Último envio: há ${mins} min`;
    }
    const hours = Math.floor(mins / 60);
    if (hours < 24) {
        return `Último envio: há ${hours}h`;
    }
    return `Último envio: ${formatLogDate(iso)}`;
}

function openLogsView(w) {
    logsWebhook.value = w;
    logFilterStatus.value = 'all';
    logSearchQuery.value = '';
    if (!logsByWebhookId.value[w.id]) {
        fetchLogs(w.id);
    }
}

function backToHub() {
    logsWebhook.value = null;
}

function openPayloadDocs() {
    showPayloadDocsModal.value = true;
}

function onPayloadDocsSendTest() {
    const active = props.webhooks.find((w) => w.is_active) || props.webhooks[0];
    if (active) {
        openTestModal(active);
    }
}

function resetForm() {
    editingWebhook.value = null;
    isCreating.value = false;
    logsWebhook.value = null;
    confirmingDeleteId.value = null;
    form.value = {
        name: '',
        url: '',
        bearer_token: '',
        events: [],
        is_active: true,
        product_ids: [],
    };
    errorMessage.value = null;
    testMessage.value = null;
}

function startNew() {
    logsWebhook.value = null;
    editingWebhook.value = null;
    isCreating.value = true;
    form.value = {
        name: '',
        url: '',
        bearer_token: '',
        events: [],
        is_active: true,
        product_ids: [],
    };
    errorMessage.value = null;
}

function editWebhook(w) {
    logsWebhook.value = null;
    isCreating.value = false;
    editingWebhook.value = w;
    form.value = {
        name: w.name,
        url: w.url,
        bearer_token: '',
        events: [...(w.events || [])],
        is_active: w.is_active ?? true,
        product_ids: (w.products || []).map(p => p.id),
    };
    errorMessage.value = null;
}

function cancelEdit() {
    resetForm();
}

function toggleEvent(eventClass) {
    const idx = form.value.events.indexOf(eventClass);
    if (idx >= 0) {
        form.value.events.splice(idx, 1);
    } else {
        form.value.events.push(eventClass);
    }
}

function isEventSelected(eventClass) {
    return form.value.events.includes(eventClass);
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

async function save() {
    errorMessage.value = null;
    if (!form.value.name?.trim()) {
        errorMessage.value = 'Informe o nome do webhook.';
        return;
    }
    if (!form.value.url?.trim()) {
        errorMessage.value = 'Informe a URL do webhook.';
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
        if (form.value.bearer_token?.trim()) {
            payload.bearer_token = form.value.bearer_token.trim();
        }

        if (editingWebhook.value) {
            await axios.put(
                `/integracoes/webhooks/${editingWebhook.value.id}`,
                payload
            );
        } else {
            await axios.post('/integracoes/webhooks', payload);
        }
        emit('saved');
        resetForm();
        await fetchDashboard();
    } catch (err) {
        errorMessage.value =
            err.response?.data?.message || 'Erro ao salvar webhook.';
    } finally {
        saving.value = false;
    }
}

function openTestModal(w) {
    testTargetWebhook.value = w;
    selectedTestEvent.value = eventEntries.value.length ? eventEntries.value[0][0] : '';
    testMessage.value = null;
    showTestModal.value = true;
}

function closeTestModal() {
    showTestModal.value = false;
    testTargetWebhook.value = null;
}

async function confirmTestSend() {
    if (!testTargetWebhook.value) return;
    const w = testTargetWebhook.value;
    testing.value = w.id;
    testMessage.value = null;
    closeTestModal();
    try {
        const { data } = await axios.post(`/integracoes/webhooks/${w.id}/test`, {
            event: selectedTestEvent.value || undefined,
        });
        testSuccess.value = data.success;
        testMessage.value = data.message || (data.success ? 'Evento enviado com sucesso!' : 'Falha ao enviar.');
        await fetchLogs(w.id);
        await fetchDashboard();
    } catch (err) {
        testSuccess.value = false;
        testMessage.value =
            err.response?.data?.message || 'Erro ao disparar evento de teste.';
    } finally {
        testing.value = null;
    }
}

async function fetchLogs(webhookId) {
    loadingLogs.value = webhookId;
    try {
        const { data } = await axios.get(`/integracoes/webhooks/${webhookId}/logs`);
        logsByWebhookId.value[webhookId] = data.logs || [];
    } catch {
        logsByWebhookId.value[webhookId] = [];
    } finally {
        loadingLogs.value = null;
    }
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

async function openLogDetail(webhookId, logId) {
    loadingLogDetail.value = true;
    selectedLogDetail.value = null;
    resendMessage.value = '';
    resendSuccess.value = null;
    logDetailModal.value = true;
    try {
        const { data } = await axios.get(
            `/integracoes/webhooks/${webhookId}/logs/${logId}`
        );
        selectedLogDetail.value = data.log;
    } catch {
        selectedLogDetail.value = null;
    } finally {
        loadingLogDetail.value = false;
    }
}

function closeLogDetail() {
    logDetailModal.value = false;
    selectedLogDetail.value = null;
    logCopyFeedback.value = '';
    resendMessage.value = '';
    resendSuccess.value = null;
}

function formatLogSource(source) {
    if (source === 'test') return 'Teste manual';
    if (source === 'resend') return 'Reenvio manual';
    return 'Automático';
}

async function resendSelectedLog() {
    const webhookId = logsWebhook.value?.id;
    const logId = selectedLogDetail.value?.id;
    if (!webhookId || !logId || resendingLog.value) return;

    resendingLog.value = true;
    resendMessage.value = '';
    resendSuccess.value = null;

    try {
        const { data } = await axios.post(
            `/integracoes/webhooks/${webhookId}/logs/${logId}/resend`,
        );
        resendSuccess.value = true;
        resendMessage.value = data.message || 'Webhook reenviado com sucesso.';
    } catch (err) {
        resendSuccess.value = false;
        resendMessage.value =
            err.response?.data?.message || 'Não foi possível reenviar o webhook.';
    } finally {
        await Promise.all([fetchLogs(webhookId), fetchDashboard()]);
        resendingLog.value = false;
    }
}

function formatPayload(obj) {
    if (obj == null) {
        return '–';
    }
    try {
        if (typeof obj === 'string') {
            const trimmed = obj.trim();
            if (trimmed === '') {
                return '–';
            }
            if (
                (trimmed.startsWith('{') && trimmed.endsWith('}'))
                || (trimmed.startsWith('[') && trimmed.endsWith(']'))
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

function fallbackCopy(text) {
    try {
        const el = document.createElement('textarea');
        el.value = text;
        el.setAttribute('readonly', '');
        el.style.position = 'fixed';
        el.style.top = '0';
        el.style.left = '-9999px';
        el.style.width = '1px';
        el.style.height = '1px';
        el.style.padding = '0';
        el.style.border = 'none';
        el.style.outline = 'none';
        el.style.opacity = '0';
        document.body.appendChild(el);
        el.focus({ preventScroll: true });
        el.select();
        el.setSelectionRange(0, text.length);
        const ok = document.execCommand('copy');
        document.body.removeChild(el);
        return ok;
    } catch {
        return false;
    }
}

function showLogCopyFeedback(feedbackKey) {
    logCopyFeedback.value = feedbackKey;
    setTimeout(() => {
        if (logCopyFeedback.value === feedbackKey) {
            logCopyFeedback.value = '';
        }
    }, 2000);
}

function copyLogText(text, feedbackKey) {
    const s = (text ?? '').trim();
    if (!s || s === '–') {
        return false;
    }

    if (fallbackCopy(s)) {
        showLogCopyFeedback(feedbackKey);
        return true;
    }

    if (navigator.clipboard?.writeText) {
        navigator.clipboard
            .writeText(s)
            .then(() => showLogCopyFeedback(feedbackKey))
            .catch(() => {
                if (fallbackCopy(s)) {
                    showLogCopyFeedback(feedbackKey);
                }
            });
        return true;
    }

    return false;
}

function textFromPre(preRef) {
    const el = preRef.value;
    if (!el) {
        return '';
    }
    return (el.innerText || el.textContent || '').trim();
}

function copyLogRequest() {
    let text = textFromPre(logRequestPreRef);
    if (!text || text === '–') {
        text = formatPayload(selectedLogDetail.value?.request_payload);
        if (text === '–') {
            text = '';
        }
    }
    copyLogText(text, 'payload');
}

function copyLogResponse() {
    const detail = selectedLogDetail.value;
    const parts = [];

    if (detail?.response_status != null && detail.response_status !== '') {
        parts.push(`HTTP ${detail.response_status}`);
    }

    let body = textFromPre(logResponsePreRef);
    if (!body || body === '–') {
        const raw = detail?.response_body;
        if (raw != null && String(raw).trim() !== '') {
            body = formatPayload(raw);
            if (body === '–') {
                body = String(raw).trim();
            }
        }
    }
    if (body && body !== '–') {
        parts.push(body);
    }

    if (parts.length === 0 && detail?.error_message) {
        parts.push(String(detail.error_message).trim());
    }

    copyLogText(parts.join('\n\n'), 'response');
}

function requestDelete(w) {
    confirmingDeleteId.value = w.id;
}

function cancelDelete() {
    confirmingDeleteId.value = null;
}

async function confirmRemoveWebhook(w) {
    deleting.value = w.id;
    confirmingDeleteId.value = null;
    try {
        await axios.delete(`/integracoes/webhooks/${w.id}`);
        emit('saved');
        if (logsWebhook.value?.id === w.id) {
            logsWebhook.value = null;
        }
        if (editingWebhook.value?.id === w.id) {
            resetForm();
        }
        await fetchDashboard();
    } catch (err) {
        errorMessage.value =
            err.response?.data?.message || 'Erro ao excluir webhook.';
    } finally {
        deleting.value = null;
    }
}

function close() {
    emit('close');
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
                @click="close"
            />
            <aside
                class="ep-drawer relative flex h-full w-full max-w-4xl flex-col"
            >
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
                        <div class="min-w-0">
                            <h2 class="truncate text-[17px] font-semibold tracking-[-0.02em] text-[var(--ep-text)]">
                                {{ headerTitle }}
                            </h2>
                            <p
                                v-if="currentView === 'hub'"
                                class="mt-0.5 flex items-center gap-1.5 text-[12.5px] text-[var(--ep-text-3)]"
                            >
                                <Clock class="h-3.5 w-3.5" :stroke-width="1.75" />
                                Dados das últimas 24 horas
                            </p>
                            <p
                                v-else-if="currentView === 'logs' && logsWebhook"
                                class="mt-0.5 truncate font-mono text-[12px] text-[var(--ep-text-3)]"
                                :title="logsWebhook.url"
                            >
                                {{ truncateUrl(logsWebhook.url, 56) }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="ep-btn-ghost ep-btn-icon shrink-0"
                        aria-label="Fechar"
                        @click="close"
                    >
                        <X class="h-[18px] w-[18px]" :stroke-width="1.75" />
                    </button>
                </header>

                <div class="flex flex-1 flex-col overflow-y-auto">
                    <!-- Hub: dashboard + lista -->
                    <template v-if="currentView === 'hub'">
                        <div class="space-y-5 px-6 pt-5">
                            <p class="max-w-2xl text-[13px] leading-relaxed text-[var(--ep-text-3)]">
                                Envie eventos da plataforma para a URL configurada. O POST inclui
                                <code class="rounded-md border border-[var(--ep-line)] bg-[var(--ep-input)] px-1.5 py-0.5 font-mono text-[11.5px] text-[var(--ep-text-2)]">event</code>,
                                <code class="rounded-md border border-[var(--ep-line)] bg-[var(--ep-input)] px-1.5 py-0.5 font-mono text-[11.5px] text-[var(--ep-text-2)]">payload</code>
                                (pedido, produto, oferta, cliente em texto claro) e
                                <code class="rounded-md border border-[var(--ep-line)] bg-[var(--ep-input)] px-1.5 py-0.5 font-mono text-[11.5px] text-[var(--ep-text-2)]">timestamp</code>.
                            </p>

                            <WebhookKpiStrip
                                :summary="dashboardData?.summary || {}"
                                :sparkline="dashboardData?.sparkline || {}"
                                :loading="loadingDashboard"
                            />

                            <div class="flex flex-wrap gap-2">
                                <Button @click="startNew">
                                    <Plus class="h-4 w-4" :stroke-width="1.75" />
                                    Novo webhook
                                </Button>
                                <Button variant="outline" @click="openPayloadDocs">
                                    <BookOpen class="h-4 w-4" :stroke-width="1.75" />
                                    Ver payloads
                                </Button>
                            </div>
                        </div>

                        <div class="flex-1 px-6 pb-6 pt-6">
                            <h3 class="ep-section-title mb-3 flex items-center gap-2">
                                Meus webhooks
                                <span class="ep-chip tabular-nums">{{ webhooks.length }}</span>
                            </h3>
                            <ul
                                v-if="webhooks.length > 0"
                                class="panel-card ep-data divide-y divide-[var(--ep-line)] overflow-hidden"
                            >
                                <li
                                    v-for="w in webhooks"
                                    :key="w.id"
                                    class="transition-colors duration-150 hover:bg-[var(--ep-hover)]"
                                >
                                    <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-[14px] font-medium tracking-[-0.01em] text-[var(--ep-text)]">
                                                    {{ w.name }}
                                                </span>
                                                <span
                                                    class="ep-chip"
                                                    :class="
                                                        w.is_active
                                                            ? 'ep-chip--pos'
                                                            : 'ep-chip--warn'
                                                    "
                                                >
                                                    <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                                                    {{ w.is_active ? 'Ativo' : 'Inativo' }}
                                                </span>
                                            </div>
                                            <div
                                                class="mt-1 truncate font-mono text-[12px] text-[var(--ep-text-3)]"
                                                :title="w.url"
                                            >
                                                {{ truncateUrl(w.url, 52) }}
                                            </div>
                                            <div class="mt-2.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-[12px] text-[var(--ep-text-4)]">
                                                <span class="tabular-nums">{{ formatRelativeTime(statsForWebhook(w).last_sent_at) }}</span>
                                                <span class="tabular-nums">
                                                    Taxa de sucesso:
                                                    <strong
                                                        class="font-semibold text-[var(--ep-text-2)]"
                                                        :class="
                                                            statsForWebhook(w).success_rate >= 80
                                                                ? '!text-[var(--ep-pos)]'
                                                                : statsForWebhook(w).sent > 0
                                                                  ? '!text-[var(--ep-neg)]'
                                                                  : ''
                                                        "
                                                    >{{ statsForWebhook(w).success_rate }}%</strong>
                                                    ({{ statsForWebhook(w).sent }} envios)
                                                </span>
                                                <span class="tabular-nums">{{ (w.events || []).length }} evento(s)</span>
                                            </div>
                                        </div>
                                        <div class="flex shrink-0 flex-wrap items-center gap-1">
                                            <template v-if="confirmingDeleteId === w.id">
                                                <span class="mr-1 text-[12.5px] font-medium text-[var(--ep-text-2)]">Excluir?</span>
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
                                                    :disabled="deleting === w.id"
                                                    @click.stop="confirmRemoveWebhook(w)"
                                                >
                                                    <Loader2 v-if="deleting === w.id" class="h-3.5 w-3.5 animate-spin" :stroke-width="1.75" />
                                                    <Trash2 v-else class="h-3.5 w-3.5" :stroke-width="1.75" />
                                                    {{ deleting === w.id ? 'Excluindo...' : 'Excluir' }}
                                                </button>
                                            </template>
                                            <template v-else>
                                                <button
                                                    type="button"
                                                    class="ep-btn-ghost !h-8 !px-3 text-[12.5px]"
                                                    @click.stop="openLogsView(w)"
                                                >
                                                    Ver logs
                                                </button>
                                                <button
                                                    type="button"
                                                    class="ep-btn-ghost !h-8 !gap-1.5 !px-3 text-[12.5px] !text-[var(--ep-accent)]"
                                                    title="Disparar evento de teste"
                                                    :disabled="testing === w.id"
                                                    @click.stop="openTestModal(w)"
                                                >
                                                    <Loader2
                                                        v-if="testing === w.id"
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
                                                    @click.stop="editWebhook(w)"
                                                >
                                                    <Settings class="h-4 w-4" :stroke-width="1.75" />
                                                </button>
                                                <button
                                                    type="button"
                                                    class="ep-btn-ghost ep-btn-icon !h-8 !w-8 hover:!text-[var(--ep-neg)]"
                                                    title="Excluir"
                                                    :disabled="deleting === w.id"
                                                    @click.stop="requestDelete(w)"
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
                                <p class="ep-empty__title">Nenhum webhook configurado</p>
                                <p class="ep-empty__text">Clique em "Novo webhook" para criar.</p>
                            </div>
                            <p
                                v-if="testMessage"
                                :class="[
                                    'mt-3 rounded-xl border px-3.5 py-2.5 text-[13px]',
                                    testSuccess
                                        ? 'border-[color-mix(in_oklab,var(--ep-pos)_30%,transparent)] bg-[var(--ep-pos-bg)] text-[var(--ep-pos)]'
                                        : 'border-[color-mix(in_oklab,var(--ep-neg)_30%,transparent)] bg-[var(--ep-neg-bg)] text-[var(--ep-neg)]',
                                ]"
                            >
                                {{ testMessage }}
                            </p>
                        </div>
                    </template>

                    <!-- Logs de um webhook -->
                    <template v-else-if="currentView === 'logs' && logsWebhook">
                        <div class="space-y-4 px-6 pt-5">
                            <div
                                v-if="statsForWebhook(logsWebhook).sent > 0"
                                class="grid grid-cols-3 gap-3"
                            >
                                <div class="panel-card ep-kpi !p-4">
                                    <p class="ep-kpi__label">Enviados</p>
                                    <p class="ep-kpi__value !text-[22px]">{{ statsForWebhook(logsWebhook).sent }}</p>
                                </div>
                                <div class="panel-card ep-kpi !p-4">
                                    <p class="ep-kpi__label flex items-center gap-1.5">
                                        <span class="h-1.5 w-1.5 rounded-full bg-[var(--ep-pos)]" aria-hidden="true" />
                                        OK
                                    </p>
                                    <p class="ep-kpi__value !text-[22px] !text-[var(--ep-pos)]">{{ statsForWebhook(logsWebhook).delivered }}</p>
                                </div>
                                <div class="panel-card ep-kpi !p-4">
                                    <p class="ep-kpi__label flex items-center gap-1.5">
                                        <span class="h-1.5 w-1.5 rounded-full bg-[var(--ep-neg)]" aria-hidden="true" />
                                        Falhas
                                    </p>
                                    <p class="ep-kpi__value !text-[22px] !text-[var(--ep-neg)]">{{ statsForWebhook(logsWebhook).failed }}</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <select
                                    v-model="logFilterStatus"
                                    class="ep-input !w-40 shrink-0"
                                >
                                    <option value="all">Todos</option>
                                    <option value="success">Sucesso</option>
                                    <option value="failed">Falha</option>
                                </select>
                                <input
                                    v-model="logSearchQuery"
                                    type="search"
                                    placeholder="Buscar evento..."
                                    class="ep-input !w-auto min-w-[140px] flex-1"
                                />
                            </div>
                        </div>
                        <div class="flex-1 px-6 pb-6 pt-4">
                            <div
                                v-if="loadingLogs === logsWebhook.id"
                                class="flex items-center justify-center gap-2 py-12 text-[13px] text-[var(--ep-text-3)]"
                            >
                                <Loader2 class="h-[18px] w-[18px] animate-spin" :stroke-width="1.75" />
                                Carregando logs...
                            </div>
                            <div
                                v-else-if="filteredLogs.length === 0"
                                class="panel-card ep-empty"
                            >
                                <p class="ep-empty__title">Nenhum registro encontrado.</p>
                                <p class="ep-empty__text">Ajuste o filtro de status ou a busca para ver outros envios.</p>
                            </div>
                            <div v-else class="panel-card ep-data overflow-hidden">
                                <table class="ep-table">
                                    <thead>
                                        <tr>
                                            <th>Horário</th>
                                            <th>Evento</th>
                                            <th>Status</th>
                                            <th>Origem</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="log in filteredLogs"
                                            :key="log.id"
                                            class="cursor-pointer"
                                            @click="openLogDetail(logsWebhook.id, log.id)"
                                        >
                                            <td class="whitespace-nowrap tabular-nums !text-[var(--ep-text-3)]">
                                                {{ formatLogDate(log.created_at) }}
                                            </td>
                                            <td class="font-medium">
                                                {{ log.event_label || log.event }}
                                            </td>
                                            <td>
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
                                            <td class="!text-[var(--ep-text-3)]">
                                                {{ formatLogSource(log.source) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>

                    <!-- Formulário (criar/editar) -->
                    <div
                        v-else-if="currentView === 'form'"
                        class="flex flex-1 flex-col gap-4 px-6 py-5"
                    >
                        <div class="space-y-4">
                            <section class="panel-card space-y-4 p-5">
                                <h3 class="ep-section-title">Destino</h3>
                                <div>
                                    <label class="ep-label">
                                        Nome
                                    </label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        placeholder="Ex: Minha integração"
                                        class="ep-input"
                                    />
                                </div>
                                <div>
                                    <label class="ep-label">
                                        URL
                                    </label>
                                    <input
                                        v-model="form.url"
                                        type="url"
                                        placeholder="https://seu-endpoint.com/webhook"
                                        class="ep-input font-mono !text-[13px]"
                                    />
                                </div>
                                <div>
                                    <label class="ep-label">
                                        Bearer token
                                        <span class="font-normal text-[var(--ep-text-4)]"
                                            >(opcional)</span
                                        >
                                    </label>
                                    <input
                                        v-model="form.bearer_token"
                                        type="password"
                                        :placeholder="
                                            editingWebhook ? 'Deixe em branco para manter' : 'Token de autenticação'
                                        "
                                        autocomplete="new-password"
                                        class="ep-input"
                                    />
                                    <p class="ep-help">
                                        Por segurança, o valor do token salvo não é
                                        exibido neste campo.
                                    </p>
                                    <p
                                        v-if="editingWebhook?.has_bearer_token && !form.bearer_token"
                                        class="ep-help flex items-center gap-1.5 !text-[var(--ep-pos)]"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                                        Token já está salvo. Deixe em branco para manter.
                                    </p>
                                </div>
                            </section>
                            <section class="panel-card p-5">
                                <div class="mb-3 flex items-center justify-between gap-3">
                                    <label class="ep-section-title">
                                        Eventos
                                    </label>
                                    <span class="ep-chip tabular-nums">{{ form.events.length }}</span>
                                </div>
                                <div
                                    class="max-h-48 space-y-2.5 overflow-y-auto rounded-xl border border-[var(--ep-input-border)] bg-[var(--ep-input)] p-3.5"
                                >
                                    <Checkbox
                                        v-for="[eventClass, label] in eventEntries"
                                        :key="eventClass"
                                        :model-value="isEventSelected(eventClass)"
                                        :label="label"
                                        class="block"
                                        @update:model-value="
                                            toggleEvent(eventClass)
                                        "
                                    />
                                </div>
                            </section>
                            <section class="panel-card p-5">
                                <label class="ep-section-title mb-3 block">
                                    Produtos
                                    <span class="font-normal text-[var(--ep-text-4)]">
                                        (opcional - deixe vazio para todos)
                                    </span>
                                </label>
                                <div
                                    class="max-h-48 space-y-2.5 overflow-y-auto rounded-xl border border-[var(--ep-input-border)] bg-[var(--ep-input)] p-3.5"
                                >
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
                            <Button :disabled="saving" @click="save">
                                {{ saving ? 'Salvando...' : 'Salvar' }}
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Modal: escolher evento para teste -->
                <div
                    v-if="showTestModal && testTargetWebhook"
                    class="ep-scrim absolute inset-0 z-10 flex items-center justify-center p-4"
                    @click.self="closeTestModal"
                >
                    <div
                        class="ep-modal w-full max-w-sm p-6"
                        role="dialog"
                        aria-labelledby="test-modal-title"
                    >
                        <h3 id="test-modal-title" class="text-[15px] font-semibold tracking-[-0.015em] text-[var(--ep-text)]">
                            Enviar evento de teste
                        </h3>
                        <p class="mb-5 mt-1 truncate text-[12.5px] text-[var(--ep-text-3)]">
                            {{ testTargetWebhook.name }}
                        </p>
                        <div class="mb-5">
                            <label class="ep-label">
                                Evento
                            </label>
                            <select
                                v-model="selectedTestEvent"
                                class="ep-input"
                            >
                                <option
                                    v-for="[eventClass, label] in eventEntries"
                                    :key="eventClass"
                                    :value="eventClass"
                                >
                                    {{ label }}
                                </option>
                            </select>
                        </div>
                        <div class="flex justify-end gap-2">
                            <Button variant="outline" size="sm" @click="closeTestModal">
                                Cancelar
                            </Button>
                            <Button size="sm" @click="confirmTestSend">
                                Enviar
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Modal: detalhe do log (payload, resposta, etc.) -->
                <div
                    v-if="logDetailModal"
                    class="ep-scrim absolute inset-0 z-10 flex items-center justify-center p-4"
                    @click.self="closeLogDetail"
                >
                    <div
                        class="ep-modal flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden"
                        role="dialog"
                        aria-labelledby="log-detail-title"
                    >
                        <div class="flex items-center justify-between gap-3 border-b border-[var(--ep-line)] px-6 py-4">
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
                        <div class="flex-1 overflow-y-auto px-6 py-5">
                            <div v-if="loadingLogDetail" class="flex items-center justify-center py-12">
                                <Loader2 class="h-6 w-6 animate-spin text-[var(--ep-text-3)]" :stroke-width="1.75" />
                            </div>
                            <template v-else-if="selectedLogDetail">
                                <div class="mb-5 flex flex-wrap items-center gap-2">
                                    <span class="mr-1 text-[14px] font-medium tracking-[-0.01em] text-[var(--ep-text)]">
                                        {{ selectedLogDetail.event_label || selectedLogDetail.event }}
                                    </span>
                                    <span
                                        :class="[
                                            'ep-chip tabular-nums',
                                            selectedLogDetail.success
                                                ? 'ep-chip--pos'
                                                : 'ep-chip--neg',
                                        ]"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                                        {{ selectedLogDetail.success ? 'Sucesso' : 'Falha' }}
                                        <span v-if="selectedLogDetail.response_status != null">
                                            (HTTP {{ selectedLogDetail.response_status }})
                                        </span>
                                    </span>
                                    <span class="ep-chip">
                                        {{ formatLogSource(selectedLogDetail.source) }}
                                    </span>
                                    <span class="text-[12px] tabular-nums text-[var(--ep-text-4)]">
                                        {{ formatLogDate(selectedLogDetail.created_at) }}
                                    </span>
                                </div>
                                <p
                                    v-if="selectedLogDetail.error_message"
                                    class="mb-4 rounded-xl border border-[color-mix(in_oklab,var(--ep-neg)_30%,transparent)] bg-[var(--ep-neg-bg)] px-3.5 py-2.5 text-[13px] text-[var(--ep-neg)]"
                                >
                                    {{ selectedLogDetail.error_message }}
                                </p>
                                <p
                                    v-if="resendMessage"
                                    class="mb-4 rounded-xl border px-3.5 py-2.5 text-[13px]"
                                    :class="
                                        resendSuccess
                                            ? 'border-[color-mix(in_oklab,var(--ep-pos)_30%,transparent)] bg-[var(--ep-pos-bg)] text-[var(--ep-pos)]'
                                            : 'border-[color-mix(in_oklab,var(--ep-neg)_30%,transparent)] bg-[var(--ep-neg-bg)] text-[var(--ep-neg)]'
                                    "
                                >
                                    {{ resendMessage }}
                                </p>
                                <div class="space-y-5">
                                    <div>
                                        <div class="mb-2 flex items-center justify-between">
                                            <span class="ep-section-title">
                                                Payload enviado (request)
                                            </span>
                                            <button
                                                type="button"
                                                class="ep-btn-ghost !h-7 !px-2.5 text-[12px]"
                                                :class="
                                                    logCopyFeedback === 'payload'
                                                        ? '!text-[var(--ep-pos)]'
                                                        : ''
                                                "
                                                @click.stop="copyLogRequest"
                                            >
                                                {{ logCopyFeedback === 'payload' ? 'Copiado!' : 'Copiar' }}
                                            </button>
                                        </div>
                                        <pre
                                            ref="logRequestPreRef"
                                            class="max-h-64 overflow-auto rounded-xl border border-[var(--ep-input-border)] bg-[var(--ep-input)] p-4 font-mono text-[12px] leading-relaxed text-[var(--ep-text-2)]"
                                        >{{ formatPayload(selectedLogDetail.request_payload) }}</pre>
                                    </div>
                                    <div>
                                        <div class="mb-2 flex items-center justify-between">
                                            <span class="ep-section-title">
                                                Resposta do servidor (response)
                                            </span>
                                            <button
                                                type="button"
                                                class="ep-btn-ghost !h-7 !px-2.5 text-[12px]"
                                                :class="
                                                    logCopyFeedback === 'response'
                                                        ? '!text-[var(--ep-pos)]'
                                                        : ''
                                                "
                                                @click.stop="copyLogResponse"
                                            >
                                                {{ logCopyFeedback === 'response' ? 'Copiado!' : 'Copiar' }}
                                            </button>
                                        </div>
                                        <p v-if="selectedLogDetail.response_status != null" class="mb-2 text-[12px] tabular-nums text-[var(--ep-text-3)]">
                                            Status: {{ selectedLogDetail.response_status }}
                                        </p>
                                        <pre
                                            ref="logResponsePreRef"
                                            class="max-h-64 overflow-auto rounded-xl border border-[var(--ep-input-border)] bg-[var(--ep-input)] p-4 font-mono text-[12px] leading-relaxed text-[var(--ep-text-2)]"
                                        >{{ formatPayload(selectedLogDetail.response_body) }}</pre>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <div class="flex flex-col-reverse gap-2 border-t border-[var(--ep-line)] px-6 py-4 sm:flex-row sm:justify-end">
                            <Button variant="outline" size="sm" class="w-full sm:w-auto" :disabled="resendingLog" @click="closeLogDetail">
                                Fechar
                            </Button>
                            <Button size="sm" class="w-full sm:w-auto" :disabled="resendingLog || !selectedLogDetail" @click="resendSelectedLog">
                                <Loader2 v-if="resendingLog" class="h-4 w-4 animate-spin" :stroke-width="1.75" />
                                <Send v-else class="h-4 w-4" :stroke-width="1.75" />
                                {{ resendingLog ? 'Reenviando…' : 'Reenviar webhook' }}
                            </Button>
                        </div>
                    </div>
                </div>
            </aside>

            <WebhookPayloadDocsModal
                :open="showPayloadDocsModal"
                :catalog="webhookEventCatalog"
                @close="showPayloadDocsModal = false"
                @send-test="onPayloadDocsSendTest"
            />
        </div>
    </Teleport>
</template>
