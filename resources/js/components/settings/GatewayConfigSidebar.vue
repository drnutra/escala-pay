<script setup>
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import Button from '@/components/ui/Button.vue';
import { X, ExternalLink, Copy, Check, ChevronDown, ChevronUp } from 'lucide-vue-next';
import { Loader2 } from 'lucide-vue-next';

const props = defineProps({
    open: { type: Boolean, default: false },
    gatewaySlug: { type: String, default: null },
});

const emit = defineEmits(['close', 'saved']);

function getCsrfToken() {
    return typeof document !== 'undefined'
        ? (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
            document.querySelector('meta[name="X-XSRF-TOKEN"]')?.getAttribute('content') ||
            '')
        : '';
}

const gateway = ref(null);
const loading = ref(false);
const saving = ref(false);
const testing = ref(false);
const testMessage = ref(null);
const testSuccess = ref(null);
const credentialValues = ref({});
const secretMaskPlaceholders = ref({});
const certificateFile = ref(null);

const SECRET_FIELD_KEYS = ['secret_key', 'webhook_secret', 'webhook_signing_secret'];

function isSecretCredentialField(field) {
    const key = field?.key;
    const type = field?.type || 'text';
    return type === 'password' || (key && SECRET_FIELD_KEYS.includes(key));
}

function isMaskPlaceholder(key, value) {
    const mask = secretMaskPlaceholders.value[key];
    return !!mask && String(value ?? '').trim() === mask;
}

function hasSecretValue(key, value) {
    const trimmed = String(value ?? '').trim();
    return trimmed !== '' && (isMaskPlaceholder(key, trimmed) || !trimmed.includes('••••'));
}

function resolveSecretForSubmit(key, value) {
    if (isMaskPlaceholder(key, value)) {
        return '';
    }
    return value != null ? String(value).trim() : '';
}

function secretInputType(field, key) {
    if (!isSecretCredentialField(field)) {
        return field.type === 'password' ? 'password' : 'text';
    }
    if (isMaskPlaceholder(key, credentialValues.value[key])) {
        return 'text';
    }
    return 'password';
}

function onSecretFocus(key) {
    if (isMaskPlaceholder(key, credentialValues.value[key])) {
        credentialValues.value[key] = '';
    }
}

function onSecretBlur(key) {
    const v = credentialValues.value[key];
    if ((v == null || String(v).trim() === '') && secretMaskPlaceholders.value[key]) {
        credentialValues.value[key] = secretMaskPlaceholders.value[key];
    }
}

function buildCredentialInitial(keys, saved) {
    const initial = {};
    const masks = {};
    for (const k of keys) {
        if ((k.type || 'text') === 'file') continue;
        const key = k.key;
        if (key == null) continue;
        const v = saved[key];
        if (k.type === 'boolean') {
            initial[key] = v === true || v === '1' || v === 'true';
        } else if (k.type === 'select') {
            const str = v != null && v !== '' ? String(v) : '';
            const fallback = k.options?.[0]?.value != null ? String(k.options[0].value) : '';
            initial[key] = str || fallback;
        } else {
            const str = v != null && v !== '' ? String(v) : '';
            initial[key] = str;
            if (isSecretCredentialField(k) && str.includes('••••')) {
                masks[key] = str;
            }
        }
    }
    secretMaskPlaceholders.value = masks;
    return initial;
}
const webhookCopied = ref(false);
const webhookCopiedSecondary = ref(false);
const disconnecting = ref(false);
const fees = ref({
    pix: { percent: 0, fixed_cents: 0 },
    card: { percent: 0, fixed_cents: 0 },
    boleto: { percent: 0, fixed_cents: 0 },
});
const savingFees = ref(false);
const feesMessage = ref('');
const feesPanelOpen = ref(false);
const advancedPanelOpen = ref(false);
const rotatingWebhook = ref(false);
const acceptingParcelado = ref(false);
const parceladoMessage = ref('');

const feeMethodLabels = {
    pix: 'PIX',
    card: 'Cartão',
    boleto: 'Boleto',
};

const isCajuPay = computed(() => (props.gatewaySlug || gateway.value?.slug || '').toLowerCase() === 'cajupay');

const standardCredentialFields = computed(() => {
    const keys = gateway.value?.credential_keys || [];
    return keys.filter((k) => !k.advanced);
});

const advancedCredentialFields = computed(() => {
    const keys = gateway.value?.credential_keys || [];
    return keys.filter((k) => k.advanced);
});

const hasAdvancedCredentialFields = computed(() => advancedCredentialFields.value.length > 0);

const cajupayWebhookNeedsAttention = computed(() => {
    if (!isCajuPay.value || !gateway.value) return false;
    const status = gateway.value.webhook_setup_status;
    if (!status || typeof status !== 'object') return false;
    return status.has_enabled_endpoint === false || status.subscribes_checkout_events === false;
});

async function reloadGateway(slug) {
    const { data } = await axios.get(
        `/configuracoes/gateways/${encodeURIComponent(slug)}`,
        { params: { t: Date.now() } }
    );
    gateway.value = data;
    const keys = data.credential_keys || [];
    const saved = data.credential_values || {};
    credentialValues.value = { ...buildCredentialInitial(keys, saved) };
}

async function rotateWebhookSecret() {
    const url = gateway.value?.webhook_rotate_url;
    if (!url) return;
    rotatingWebhook.value = true;
    testMessage.value = null;
    try {
        const { data } = await axios.post(
            url,
            {},
            { headers: { 'X-XSRF-TOKEN': getCsrfToken(), Accept: 'application/json' } }
        );
        testSuccess.value = data.success;
        const parts = [data.message, data.webhook_warning].filter(Boolean);
        testMessage.value = parts.join(' ');
        if (gateway.value?.slug) {
            await reloadGateway(gateway.value.slug);
        }
    } catch (err) {
        testSuccess.value = false;
        testMessage.value = err.response?.data?.message || 'Erro ao rotacionar token do webhook.';
    } finally {
        rotatingWebhook.value = false;
    }
}

async function acceptPixParceladoEnrollment() {
    const url = gateway.value?.pix_parcelado_accept_url;
    if (!url) return;
    acceptingParcelado.value = true;
    parceladoMessage.value = '';
    try {
        const { data } = await axios.post(
            url,
            {},
            { headers: { 'X-XSRF-TOKEN': getCsrfToken(), Accept: 'application/json' } },
        );
        testSuccess.value = data.success;
        parceladoMessage.value = data.message || 'Contrato aceito.';
        if (gateway.value?.slug) {
            await reloadGateway(gateway.value.slug);
        }
    } catch (err) {
        testSuccess.value = false;
        parceladoMessage.value = err.response?.data?.message || 'Erro ao aceitar contrato PIX Parcelado.';
    } finally {
        acceptingParcelado.value = false;
    }
}

async function loadFees(slug) {
    try {
        const { data } = await axios.get(`/configuracoes/gateways/${encodeURIComponent(slug)}/fees`);
        if (data.fees) {
            fees.value = { ...fees.value, ...data.fees };
        }
    } catch {
        feesMessage.value = '';
    }
}

async function saveFees() {
    if (!props.gatewaySlug) return;
    savingFees.value = true;
    feesMessage.value = '';
    try {
        await axios.put(`/configuracoes/gateways/${encodeURIComponent(props.gatewaySlug)}/fees`, { fees: fees.value });
        feesMessage.value = 'Taxas salvas.';
    } catch {
        feesMessage.value = 'Erro ao salvar taxas.';
    } finally {
        savingFees.value = false;
    }
}

async function copyWebhookUrl() {
    const url = gateway.value?.webhook_url;
    if (!url) return;
    try {
        await navigator.clipboard.writeText(url);
        webhookCopied.value = true;
        setTimeout(() => { webhookCopied.value = false; }, 2000);
    } catch {
        webhookCopied.value = false;
    }
}

async function copyWebhookUrlSecondary() {
    const url = gateway.value?.webhook_url_secondary;
    if (!url) return;
    try {
        await navigator.clipboard.writeText(url);
        webhookCopiedSecondary.value = true;
        setTimeout(() => { webhookCopiedSecondary.value = false; }, 2000);
    } catch {
        webhookCopiedSecondary.value = false;
    }
}

watch(
    () => [props.open, props.gatewaySlug],
    async ([open, slug]) => {
        if (open && slug) {
            loading.value = true;
            testMessage.value = null;
            feesPanelOpen.value = false;
            advancedPanelOpen.value = false;
            feesMessage.value = '';
            webhookCopied.value = false;
            webhookCopiedSecondary.value = false;
            credentialValues.value = {};
            secretMaskPlaceholders.value = {};
            try {
                const { data } = await axios.get(
                    `/configuracoes/gateways/${encodeURIComponent(slug)}`,
                    { params: { t: Date.now() } }
                );
                gateway.value = data;
                await loadFees(slug);
                const keys = data.credential_keys || [];
                const saved = data.credential_values || {};
                credentialValues.value = { ...buildCredentialInitial(keys, saved) };
                certificateFile.value = null;
            } catch {
                gateway.value = null;
            } finally {
                loading.value = false;
            }
        } else {
            gateway.value = null;
        }
    },
    { immediate: true }
);

const inputClass =
    'block w-full rounded-xl border-2 border-zinc-200 bg-white px-4 py-2.5 text-zinc-900 placeholder-zinc-400 transition focus:border-[var(--color-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-zinc-500';

function buildTestPayload() {
    const keys = gateway.value?.credential_keys || [];
    const payload = {};
    for (const k of keys) {
        if ((k.type || 'text') === 'file') continue;
        const v = credentialValues.value[k.key];
        if (k.type === 'boolean') {
            payload[k.key] = v === true || v === '1' || v === 'true';
        } else if (isSecretCredentialField(k)) {
            const resolved = resolveSecretForSubmit(k.key, v);
            if (resolved !== '') {
                payload[k.key] = resolved;
            }
        } else if (v != null && String(v).trim() !== '') {
            payload[k.key] = String(v).trim();
        }
    }
    return payload;
}

async function testConnection() {
    if (!gateway.value?.slug) return;
    const keys = gateway.value.credential_keys || [];
    const certificateKey = gateway.value.certificate_key;
    for (const k of keys) {
        if (k.key === certificateKey) continue;
        if ((k.type || 'text') === 'boolean') continue;
        if (k.optional) continue;
        if (
            gateway.value.slug === 'cajupay'
            && (k.key === 'secret_key' || k.key === 'public_key')
            && gateway.value.is_configured
        ) {
            continue;
        }
        const v = credentialValues.value[k.key];
        const satisfied = isSecretCredentialField(k)
            ? hasSecretValue(k.key, v)
            : (v != null && String(v).trim() !== '');
        if (!satisfied) {
            testMessage.value = 'Preencha todas as credenciais obrigatórias para testar.';
            testSuccess.value = false;
            return;
        }
    }
    if (certificateKey && !gateway.value.certificate_configured && !certificateFile.value) {
        testMessage.value = 'Envie e salve o certificado P12 antes de testar.';
        testSuccess.value = false;
        return;
    }
    const payload = buildTestPayload();
    testing.value = true;
    testMessage.value = null;
    try {
        const { data } = await axios.post(
            `/configuracoes/gateways/${encodeURIComponent(gateway.value.slug)}/test`,
            payload,
            { headers: { 'X-XSRF-TOKEN': getCsrfToken(), Accept: 'application/json' } }
        );
        testSuccess.value = data.success;
        const parts = [data.message || (data.success ? 'Conexão OK.' : 'Falha.'), data.webhook_warning].filter(Boolean);
        testMessage.value = parts.join(' ');
    } catch (err) {
        testSuccess.value = false;
        testMessage.value =
            err.response?.data?.message || 'Erro ao testar conexão.';
    } finally {
        testing.value = false;
    }
}

async function save() {
    if (!gateway.value?.slug) return;
    saving.value = true;
    testMessage.value = null;
    try {
        const keys = gateway.value.credential_keys || [];
        const certificateKey = gateway.value.certificate_key;

        // 1) Salva sempre as credenciais (sem arquivo) em JSON
        const payload = {};
        for (const k of keys) {
            if (k.key === certificateKey) continue;
            const v = credentialValues.value[k.key];
            if (k.type === 'boolean') {
                payload[k.key] = v === true || v === '1' || v === 'true';
            } else if (isSecretCredentialField(k)) {
                payload[k.key] = resolveSecretForSubmit(k.key, v);
            } else {
                payload[k.key] = v != null ? String(v).trim() : '';
            }
        }
        const { data } = await axios.put(
            `/configuracoes/gateways/${encodeURIComponent(gateway.value.slug)}`,
            payload,
            { headers: { 'X-XSRF-TOKEN': getCsrfToken(), 'Content-Type': 'application/json', Accept: 'application/json' } }
        );

        // 2) Se tiver certificado, envia em chamada separada
        if (certificateKey && certificateFile.value) {
            const form = new FormData();
            form.append(certificateKey, certificateFile.value);
            await axios.post(
                `/configuracoes/gateways/${encodeURIComponent(gateway.value.slug)}/certificate`,
                form,
                { headers: { 'X-XSRF-TOKEN': getCsrfToken(), Accept: 'application/json' } }
            );
        }

        certificateFile.value = null;
        testSuccess.value = true;
        const saveParts = [data?.message || 'Credenciais salvas.', data?.webhook_warning].filter(Boolean);
        testMessage.value = saveParts.join(' ');
        if (gateway.value?.slug) {
            await reloadGateway(gateway.value.slug);
        }
        emit('saved');
        setTimeout(() => {
            emit('close');
        }, 1500);
    } catch (err) {
        testSuccess.value = false;
        const res = err.response?.data;
        let msg = res?.message || 'Erro ao salvar.';
        if (res?.errors && typeof res.errors === 'object') {
            const parts = Object.values(res.errors).flat().filter(Boolean);
            if (parts.length) msg = parts.join(' ');
        }
        testMessage.value = msg;
    } finally {
        saving.value = false;
    }
}

function close() {
    emit('close');
}

async function disconnectOAuth() {
    const url = gateway.value?.oauth_disconnect_url;
    if (!url) return;
    disconnecting.value = true;
    testMessage.value = null;
    try {
        await axios.post(
            url,
            {},
            { headers: { 'X-XSRF-TOKEN': getCsrfToken(), Accept: 'application/json' } }
        );
        testSuccess.value = true;
        testMessage.value = 'Conta desconectada.';
        emit('saved');
        const slug = gateway.value?.slug;
        if (slug) {
            const { data } = await axios.get(
                `/configuracoes/gateways/${encodeURIComponent(slug)}`,
                { params: { t: Date.now() } }
            );
            gateway.value = data;
        }
    } catch (err) {
        testSuccess.value = false;
        testMessage.value =
            err.response?.data?.message || 'Não foi possível desconectar.';
    } finally {
        disconnecting.value = false;
    }
}

const hasManualCredentialFields = computed(() => {
    const keys = gateway.value?.credential_keys || [];
    return keys.length > 0 || !!gateway.value?.certificate_key;
});

const canTestConnection = computed(() => {
    if (!gateway.value) return false;
    if (gateway.value.uses_oauth && !gateway.value.oauth_connected) {
        return false;
    }
    return true;
});
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
                class="ep-drawer relative flex h-full w-full max-w-md flex-col sm:rounded-l-[22px]"
            >
                <div
                    class="flex items-start justify-between gap-3 border-b border-[var(--ep-line)] px-5 py-4 sm:rounded-tl-[22px]"
                >
                    <div class="flex min-w-0 flex-1 flex-col gap-1.5">
                        <p class="text-[11.5px] font-medium text-[var(--ep-text-4)]">Gateway de pagamento</p>
                        <div class="flex min-w-0 flex-wrap items-center gap-2">
                            <h2 class="truncate text-[17px] font-semibold tracking-[-0.02em] text-[var(--ep-text)]">
                                {{ gateway?.name || 'Gateway' }}
                            </h2>
                            <span
                                class="ep-chip"
                                :class="[
                                    gateway ? '' : 'hidden',
                                    gateway?.is_connected ? 'ep-chip--pos' : gateway?.is_configured ? 'ep-chip--warn' : '',
                                ]"
                            >
                                <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                                {{ gateway?.is_connected ? 'Conectado' : gateway?.is_configured ? 'Configurado' : 'Não configurado' }}
                            </span>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="ep-btn-ghost ep-btn-icon -mr-1.5 shrink-0"
                        aria-label="Fechar"
                        @click="close"
                    >
                        <X class="h-[18px] w-[18px]" :stroke-width="1.75" />
                    </button>
                </div>

                <div v-if="loading" class="flex flex-1 flex-col items-center justify-center gap-3 p-8">
                    <Loader2 class="h-5 w-5 animate-spin text-[var(--ep-accent)]" :stroke-width="1.75" aria-hidden="true" />
                    <p class="text-[12.5px] text-[var(--ep-text-3)]">Carregando...</p>
                </div>

                <div v-else-if="gateway" class="flex flex-1 flex-col overflow-y-auto px-5 pt-5">
                    <!-- Criar conta -->
                    <a
                        v-if="gateway.signup_url"
                        :href="gateway.signup_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="group mb-5 flex items-center gap-2.5 rounded-xl border border-[color-mix(in_oklab,var(--ep-accent)_35%,transparent)] bg-[color-mix(in_oklab,var(--ep-accent)_10%,transparent)] px-4 py-3 text-[13px] font-medium text-[var(--ep-accent)] transition-colors duration-150 hover:bg-[color-mix(in_oklab,var(--ep-accent)_16%,transparent)]"
                    >
                        <ExternalLink class="h-4 w-4 shrink-0" :stroke-width="1.75" />
                        Criar conta no {{ gateway.name }}
                    </a>

                    <!-- Webhook: URL(s) no painel do gateway + token nas credenciais (CajuPay, etc.) -->
                    <div
                        v-if="gateway.webhook_url"
                        class="mb-5 rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4"
                    >
                        <h3 class="ep-section-title mb-2">
                            Webhook (URL no {{ gateway.name }})
                        </h3>
                        <template v-if="gateway.slug === 'cajupay'">
                            <p class="mb-3 text-[12px] leading-relaxed text-[var(--ep-text-3)]">
                                Ao salvar as chaves de API, o Getfy registra o webhook na CajuPay automaticamente
                                (eventos <code class="rounded-md border border-[var(--ep-line)] bg-[var(--ep-input)] px-1 py-px font-mono text-[11px] text-[var(--ep-text)]">checkout.payment.*</code>
                                e <code class="rounded-md border border-[var(--ep-line)] bg-[var(--ep-input)] px-1 py-px font-mono text-[11px] text-[var(--ep-text)]">pix.payment.*</code>).
                                Não é necessário configurar manualmente no painel CajuPay.
                            </p>
                            <div
                                v-if="gateway.webhook_auto_configured"
                                class="mb-3 flex flex-wrap items-center gap-2 rounded-xl border border-[color-mix(in_oklab,var(--ep-pos)_30%,transparent)] bg-[var(--ep-pos-bg)] px-3 py-2"
                            >
                                <Check class="h-4 w-4 shrink-0 text-[var(--ep-pos)]" :stroke-width="1.75" />
                                <span class="text-[12px] font-medium text-[var(--ep-pos)]">Webhook já configurado</span>
                            </div>
                            <p
                                v-if="cajupayWebhookNeedsAttention"
                                class="mb-3 rounded-xl border border-[color-mix(in_oklab,var(--ep-warn)_30%,transparent)] bg-[var(--ep-warn-bg)] px-3 py-2 text-[12px] leading-relaxed text-[var(--ep-text-2)]"
                            >
                                A CajuPay indica que o webhook pode estar incompleto. Salve as credenciais novamente ou use “Rotacionar token” na configuração avançada.
                            </p>
                        </template>
                        <p v-else-if="gateway.slug === 'paypal'" class="mb-3 text-[12px] leading-relaxed text-[var(--ep-text-3)]">
                            Copie esta URL e cadastre como Webhook no
                            <a href="https://developer.paypal.com/dashboard/webhooks" target="_blank" rel="noopener noreferrer" class="font-medium text-[var(--ep-accent)] underline underline-offset-2">PayPal Developer Dashboard</a>.
                            Depois cole o Webhook ID no campo abaixo. Eventos:
                            <code class="rounded-md border border-[var(--ep-line)] bg-[var(--ep-input)] px-1 py-px font-mono text-[11px] text-[var(--ep-text)]">PAYMENT.CAPTURE.COMPLETED</code>,
                            <code class="rounded-md border border-[var(--ep-line)] bg-[var(--ep-input)] px-1 py-px font-mono text-[11px] text-[var(--ep-text)]">DENIED</code>,
                            <code class="rounded-md border border-[var(--ep-line)] bg-[var(--ep-input)] px-1 py-px font-mono text-[11px] text-[var(--ep-text)]">REFUNDED</code>.
                        </p>
                        <p v-else class="mb-3 text-[12px] leading-relaxed text-[var(--ep-text-3)]">
                            Configure esta URL no painel do {{ gateway.name }} (notificações de pagamento).
                        </p>

                        <div
                            v-if="gateway.slug === 'cajupay' && gateway.is_connected"
                            class="mb-4 rounded-xl border border-[var(--ep-line)] bg-[var(--ep-glass)] p-4"
                        >
                            <h3 class="ep-section-title mb-2">
                                PIX Parcelado
                            </h3>
                            <p class="mb-3 text-[12px] leading-relaxed text-[var(--ep-text-3)]">
                                Adesão necessária para oferecer PIX Parcelado no checkout. Status:
                                <strong class="font-medium text-[var(--ep-text)]">{{ gateway.pix_parcelado_enrollment?.status || 'desconhecido' }}</strong>
                            </p>
                            <div
                                v-if="gateway.pix_parcelado_enrolled"
                                class="mb-3 flex items-center gap-2 rounded-xl border border-[color-mix(in_oklab,var(--ep-pos)_30%,transparent)] bg-[var(--ep-pos-bg)] px-3 py-2"
                            >
                                <Check class="h-4 w-4 shrink-0 text-[var(--ep-pos)]" :stroke-width="1.75" />
                                <span class="text-[12px] font-medium text-[var(--ep-pos)]">Adesão ativa</span>
                            </div>
                            <button
                                v-else-if="gateway.pix_parcelado_accept_url"
                                type="button"
                                class="ep-btn w-full"
                                :disabled="acceptingParcelado"
                                @click="acceptPixParceladoEnrollment"
                            >
                                {{ acceptingParcelado ? 'Aceitando…' : 'Aceitar contrato PIX Parcelado' }}
                            </button>
                            <p v-if="parceladoMessage" class="mt-2 text-[12px]" :class="testSuccess ? 'text-[var(--ep-pos)]' : 'text-[var(--ep-neg)]'">
                                {{ parceladoMessage }}
                            </p>
                        </div>

                        <p class="mb-1.5 text-[11.5px] font-medium text-[var(--ep-text-3)]">URL principal</p>
                        <div class="mb-3 flex gap-2">
                            <input
                                :value="gateway.webhook_url"
                                type="text"
                                readonly
                                class="ep-input !h-9 min-w-0 flex-1 font-mono !text-[11.5px] text-[var(--ep-text-2)]"
                            />
                            <button
                                type="button"
                                class="ep-btn-secondary !h-9 shrink-0 !px-3 text-[12.5px]"
                                @click="copyWebhookUrl"
                            >
                                <Check v-if="webhookCopied" class="h-4 w-4 text-[var(--ep-pos)]" :stroke-width="1.75" />
                                <Copy v-else class="h-4 w-4" :stroke-width="1.75" />
                                {{ webhookCopied ? 'Copiado!' : 'Copiar' }}
                            </button>
                        </div>
                        <template v-if="gateway.webhook_url_secondary && gateway.slug !== 'cajupay'">
                            <p class="mb-1.5 text-[11.5px] font-medium text-[var(--ep-text-3)]">URL alternativa (mesmo endpoint)</p>
                            <div class="flex gap-2">
                                <input
                                    :value="gateway.webhook_url_secondary"
                                    type="text"
                                    readonly
                                    class="ep-input !h-9 min-w-0 flex-1 font-mono !text-[11.5px] text-[var(--ep-text-2)]"
                                />
                                <button
                                    type="button"
                                    class="ep-btn-secondary !h-9 shrink-0 !px-3 text-[12.5px]"
                                    @click="copyWebhookUrlSecondary"
                                >
                                    <Check v-if="webhookCopiedSecondary" class="h-4 w-4 text-[var(--ep-pos)]" :stroke-width="1.75" />
                                    <Copy v-else class="h-4 w-4" :stroke-width="1.75" />
                                    {{ webhookCopiedSecondary ? 'Copiado!' : 'Copiar' }}
                                </button>
                            </div>
                        </template>
                    </div>

                    <h3
                        v-if="hasManualCredentialFields"
                        class="ep-section-title mb-3"
                    >
                        Credenciais
                    </h3>
                    <div v-if="hasManualCredentialFields" class="space-y-4">
                        <div
                            v-for="field in standardCredentialFields"
                            :key="field.key"
                        >
                            <label
                                class="ep-label"
                            >
                                {{ field.label }}
                                <span v-if="field.optional" class="ml-1 font-normal text-[var(--ep-text-4)]">(opcional)</span>
                            </label>
                            <p
                                v-if="field.hint"
                                class="mb-2 text-[12px] leading-relaxed text-[var(--ep-text-4)]"
                            >
                                {{ field.hint }}
                            </p>
                            <template v-if="field.type === 'file'">
                                <input
                                    type="file"
                                    accept=".p12"
                                    class="block w-full rounded-xl border border-dashed border-[var(--ep-line-strong)] bg-[var(--ep-input)] p-1.5 text-[12.5px] text-[var(--ep-text-3)] file:mr-3 file:h-8 file:cursor-pointer file:rounded-[10px] file:border file:border-solid file:border-[var(--ep-glass-border)] file:bg-[var(--ep-glass-strong)] file:px-3 file:text-[12.5px] file:font-medium file:text-[var(--ep-text)] file:transition-colors hover:file:border-[var(--ep-line-strong)]"
                                    @change="certificateFile = $event.target.files?.[0] || null"
                                />
                                <p
                                    v-if="gateway.certificate_configured && !certificateFile"
                                    class="ep-help"
                                >
                                    <span v-if="gateway.certificate_filename" class="font-medium text-[var(--ep-text-2)]">Em uso: {{ gateway.certificate_filename }}</span>
                                    <template v-else>Certificado já enviado.</template>
                                    <span> Envie novamente para substituir.</span>
                                </p>
                            </template>
                            <template v-else-if="field.type === 'boolean'">
                                <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] px-3 py-2.5 transition-colors duration-150 hover:bg-[var(--ep-hover)]">
                                    <input
                                        v-model="credentialValues[field.key]"
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-[var(--ep-line-strong)] accent-[var(--ep-accent)]"
                                    />
                                    <span class="text-[13px] text-[var(--ep-text-2)]">
                                        {{ field.key === 'sandbox' ? 'Ativar' : 'Sim (somente para testes)' }}
                                    </span>
                                </label>
                                <p
                                    v-if="field.key === 'sandbox' && gateway?.slug === 'cajupay' && credentialValues.sandbox"
                                    class="mt-2 rounded-xl border border-[color-mix(in_oklab,var(--ep-warn)_30%,transparent)] bg-[var(--ep-warn-bg)] px-3 py-2 text-[12px] leading-relaxed text-[var(--ep-text-2)]"
                                >
                                    Modo teste: use chaves <code class="font-mono text-[var(--ep-text)]">gpk_test_</code> / <code class="font-mono text-[var(--ep-text)]">gsk_test_</code>.
                                    Cartão digitado funciona em HTTP; wallets ainda exigem HTTPS. Não libere produto real com sandbox.
                                </p>
                            </template>
                            <select
                                v-else-if="field.type === 'select'"
                                v-model="credentialValues[field.key]"
                                class="ep-input"
                            >
                                <option
                                    v-for="opt in (field.options || [])"
                                    :key="String(opt.value ?? opt)"
                                    :value="String(opt.value ?? opt)"
                                >
                                    {{ opt.label ?? opt.value ?? opt }}
                                </option>
                            </select>
                            <input
                                v-else
                                v-model="credentialValues[field.key]"
                                :type="secretInputType(field, field.key)"
                                :placeholder="isSecretCredentialField(field) && secretMaskPlaceholders[field.key] ? 'Clique para substituir' : field.label"
                                class="ep-input"
                                :class="isMaskPlaceholder(field.key, credentialValues[field.key]) ? 'font-mono !text-[12.5px] tracking-wide' : ''"
                                autocomplete="off"
                                @focus="() => { if (isSecretCredentialField(field)) onSecretFocus(field.key); }"
                                @blur="() => { if (isSecretCredentialField(field)) onSecretBlur(field.key); }"
                            />
                        </div>
                    </div>

                    <div
                        v-if="hasAdvancedCredentialFields"
                        class="mt-5 border-t border-[var(--ep-line)] pt-4"
                    >
                        <button
                            type="button"
                            class="flex w-full items-center justify-between gap-3 rounded-lg text-left"
                            :aria-expanded="advancedPanelOpen"
                            @click="advancedPanelOpen = !advancedPanelOpen"
                        >
                            <h3 class="text-[13px] font-medium text-[var(--ep-text)]">
                                Configuração avançada
                            </h3>
                            <ChevronUp v-if="advancedPanelOpen" class="h-4 w-4 shrink-0 text-[var(--ep-text-3)]" :stroke-width="1.75" />
                            <ChevronDown v-else class="h-4 w-4 shrink-0 text-[var(--ep-text-3)]" :stroke-width="1.75" />
                        </button>
                        <div v-show="advancedPanelOpen" class="mt-3 space-y-4">
                            <div
                                v-for="field in advancedCredentialFields"
                                :key="field.key"
                            >
                                <label
                                    class="ep-label"
                                >
                                    {{ field.label }}
                                    <span v-if="field.optional" class="ml-1 font-normal text-[var(--ep-text-4)]">(opcional)</span>
                                </label>
                                <p
                                    v-if="field.hint"
                                    class="mb-2 text-[12px] leading-relaxed text-[var(--ep-text-4)]"
                                >
                                    {{ field.hint }}
                                </p>
                                <input
                                    v-model="credentialValues[field.key]"
                                    :type="secretInputType(field, field.key)"
                                    :placeholder="secretMaskPlaceholders[field.key] ? 'Clique para substituir' : (field.key === 'webhook_signing_secret' ? 'Configurado automaticamente ao salvar' : field.label)"
                                    class="ep-input"
                                    :class="isMaskPlaceholder(field.key, credentialValues[field.key]) ? 'font-mono !text-[12.5px] tracking-wide' : ''"
                                    autocomplete="off"
                                    @focus="onSecretFocus(field.key)"
                                    @blur="onSecretBlur(field.key)"
                                />
                            </div>
                            <Button
                                v-if="isCajuPay && gateway.webhook_rotate_url"
                                type="button"
                                variant="outline"
                                class="w-full"
                                :disabled="rotatingWebhook"
                                @click="rotateWebhookSecret"
                            >
                                {{ rotatingWebhook ? 'Rotacionando…' : 'Rotacionar token do webhook' }}
                            </Button>
                        </div>
                    </div>

                    <div
                        v-if="gateway.uses_oauth"
                        class="mb-5 mt-5 rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4"
                    >
                        <h3 class="ep-section-title mb-3">
                            Conectar via OAuth
                        </h3>
                        <div class="mb-4 flex flex-col gap-2">
                            <Button
                                v-if="gateway.oauth_start_url && !gateway.oauth_connected"
                                as="a"
                                :href="gateway.oauth_start_url"
                                variant="primary"
                                class="w-full justify-center text-center no-underline sm:w-full"
                            >
                                <ExternalLink class="h-4 w-4 shrink-0" :stroke-width="1.75" aria-hidden="true" />
                                Conectar
                            </Button>
                            <p
                                v-if="gateway.oauth_start_url && !gateway.oauth_connected"
                                class="text-center text-[11.5px] text-[var(--ep-text-4)]"
                            >
                                Abre o fluxo de autorização do gateway e, após o consentimento, salva o token no Getfy.
                            </p>
                            <Button
                                v-if="gateway.oauth_disconnect_url && gateway.oauth_connected"
                                type="button"
                                variant="outline"
                                class="w-full justify-center sm:w-auto"
                                :disabled="disconnecting"
                                @click="disconnectOAuth"
                            >
                                {{ disconnecting ? 'Desconectando...' : 'Desconectar' }}
                            </Button>
                        </div>
                        <p
                            v-if="!gateway.oauth_client_configured"
                            class="mb-3 rounded-xl border border-[color-mix(in_oklab,var(--ep-warn)_30%,transparent)] bg-[var(--ep-warn-bg)] px-3 py-2 text-[12px] leading-relaxed text-[var(--ep-text-2)]"
                        >
                            A identificação do aplicativo OAuth ainda não está configurada neste servidor (variáveis de ambiente ou registro do gateway).
                        </p>
                        <template v-else>
                            <p
                                v-if="gateway.oauth_callback_url && !gateway.oauth_connected"
                                class="mb-3 text-[12px] leading-relaxed text-[var(--ep-text-3)]"
                            >
                                Na primeira conexão, cadastre a URL de callback no painel do integrador, se solicitado.
                            </p>
                            <p
                                v-if="gateway.oauth_callback_url"
                                class="mb-1.5 text-[11.5px] font-medium text-[var(--ep-text-3)]"
                            >
                                URL de redirecionamento (callback)
                            </p>
                            <p
                                v-if="gateway.oauth_callback_url"
                                class="mb-3 break-all rounded-xl border border-[var(--ep-input-border)] bg-[var(--ep-input)] px-3 py-2 font-mono text-[11.5px] text-[var(--ep-text)]"
                            >
                                {{ gateway.oauth_callback_url }}
                            </p>
                        </template>
                        <p
                            v-if="gateway.oauth_connected"
                            class="flex items-center gap-2 text-[12px] text-[var(--ep-pos)]"
                        >
                            <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-current" aria-hidden="true" />
                            Conta autorizada. Teste a conexão abaixo ou desconecte.
                        </p>
                    </div>

                    <div class="mt-5 border-t border-[var(--ep-line)] pt-4">
                        <button
                            type="button"
                            class="flex w-full items-start justify-between gap-3 rounded-lg text-left"
                            :aria-expanded="feesPanelOpen"
                            @click="feesPanelOpen = !feesPanelOpen"
                        >
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-[13px] font-medium text-[var(--ep-text)]">
                                        Taxas para comissões (líquido)
                                    </h3>
                                    <span class="ep-chip !h-5 !text-[10.5px]">
                                        Opcional
                                    </span>
                                </div>
                                <p class="mt-1 text-[12px] leading-relaxed text-[var(--ep-text-4)]">
                                    Só necessário se você usa co-produção ou afiliados e quer descontar a taxa do gateway no valor líquido.
                                    Usado também como estimativa nos relatórios quando o gateway não informar a taxa real no webhook.
                                </p>
                                <p v-if="isCajuPay && !feesPanelOpen" class="mt-1 text-[12px] tabular-nums text-[var(--ep-text-3)]">
                                    Padrão CajuPay PIX: 0% + R$ 0,99 fixo.
                                </p>
                            </div>
                            <ChevronUp v-if="feesPanelOpen" class="mt-0.5 h-4 w-4 shrink-0 text-[var(--ep-text-3)]" :stroke-width="1.75" />
                            <ChevronDown v-else class="mt-0.5 h-4 w-4 shrink-0 text-[var(--ep-text-3)]" :stroke-width="1.75" />
                        </button>

                        <div v-show="feesPanelOpen" class="mt-4 space-y-3">
                            <div
                                v-for="method in ['pix', 'card', 'boleto']"
                                :key="method"
                                class="grid grid-cols-[72px_1fr_1fr] items-end gap-2"
                            >
                                <span class="flex h-9 items-center gap-1.5 text-[12.5px] font-medium text-[var(--ep-text-2)]">
                                    <span
                                        class="h-2 w-2 shrink-0 rounded-full"
                                        :class="method === 'pix' ? 'bg-[var(--ep-pix)]' : method === 'card' ? 'bg-[var(--ep-cartao)]' : 'bg-[var(--ep-boleto)]'"
                                        aria-hidden="true"
                                    />
                                    {{ feeMethodLabels[method] }}
                                </span>
                                <div>
                                    <label class="mb-1 block text-[11px] text-[var(--ep-text-4)]">Percentual (%)</label>
                                    <input
                                        v-model.number="fees[method].percent"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="ep-input !h-9 text-right tabular-nums"
                                    />
                                </div>
                                <div>
                                    <label class="mb-1 block text-[11px] text-[var(--ep-text-4)]">Fixo (centavos)</label>
                                    <input
                                        v-model.number="fees[method].fixed_cents"
                                        type="number"
                                        min="0"
                                        class="ep-input !h-9 text-right tabular-nums"
                                    />
                                </div>
                            </div>
                            <p class="ep-help tabular-nums">
                                Ex.: 99 centavos = R$ 0,99. Deixe em branco (0) se não quiser usar taxa fixa.
                            </p>
                            <Button type="button" class="w-full" variant="outline" :disabled="savingFees" @click="saveFees">
                                {{ savingFees ? 'Salvando…' : 'Salvar taxas' }}
                            </Button>
                            <p v-if="feesMessage" class="text-center text-[12px] text-[var(--ep-text-3)]">{{ feesMessage }}</p>
                        </div>
                    </div>

                    <p
                        v-if="testMessage"
                        :class="[
                            'mt-5 flex items-start gap-2 rounded-xl border px-3 py-2.5 text-[12.5px] leading-relaxed',
                            testSuccess
                                ? 'border-[color-mix(in_oklab,var(--ep-pos)_30%,transparent)] bg-[var(--ep-pos-bg)] text-[var(--ep-pos)]'
                                : 'border-[color-mix(in_oklab,var(--ep-neg)_30%,transparent)] bg-[var(--ep-neg-bg)] text-[var(--ep-neg)]',
                        ]"
                    >
                        {{ testMessage }}
                    </p>

                    <div class="min-h-0 flex-1" aria-hidden="true" />
                    <div class="sticky bottom-0 -mx-5 mt-6 flex flex-col gap-2 border-t border-[var(--ep-line)] bg-[var(--ep-drawer)] px-5 pb-5 pt-4 backdrop-blur-xl">
                        <Button
                            variant="outline"
                            :disabled="testing || !canTestConnection"
                            @click="testConnection"
                        >
                            {{ testing ? 'Testando...' : 'Testar conexão' }}
                        </Button>
                        <Button
                            v-if="hasManualCredentialFields"
                            :disabled="saving"
                            @click="save"
                        >
                            {{ saving ? 'Salvando...' : 'Salvar' }}
                        </Button>
                    </div>
                </div>
            </aside>
        </div>
    </Teleport>
</template>
