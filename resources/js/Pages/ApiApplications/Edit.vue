<script setup>
import { ref, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import Button from '@/components/ui/Button.vue';
import GatewayRedundancySidebar from '@/components/produtos/GatewayRedundancySidebar.vue';
import { Settings2, KeyRound, Copy, RefreshCw, X, Check, ImagePlus, Trash2, Palette } from 'lucide-vue-next';
import { ArrowLeft, Webhook, ShieldCheck, Lock, Layers, Tag, RotateCcw, Plus, Target, AlertTriangle } from 'lucide-vue-next';

defineOptions({ layout: LayoutInfoprodutor });

const props = defineProps({
    application: { type: Object, required: true },
    gateways_by_method: { type: Object, default: () => ({}) },
    api_key_reveal: { type: String, default: null },
    webhook_secret_mask: { type: String, default: '' },
});

function randomClientId() {
    try {
        return crypto?.randomUUID?.() || Math.random().toString(36).slice(2);
    } catch {
        return Math.random().toString(36).slice(2);
    }
}

const ENTRY_FLAGS = {
    fire_purchase_on_pix: true,
    fire_purchase_on_boleto: true,
    disable_order_bump_events: false,
};

function newMetaEntry() {
    return { id: randomClientId(), pixel_id: '', access_token: '', ...ENTRY_FLAGS };
}
function newTiktokEntry() {
    return { id: randomClientId(), pixel_id: '', access_token: '', ...ENTRY_FLAGS };
}
function newGoogleAdsEntry() {
    return { id: randomClientId(), conversion_id: '', conversion_label: '', ...ENTRY_FLAGS };
}
function newGaEntry() {
    return { id: randomClientId(), measurement_id: '', ...ENTRY_FLAGS };
}

const DEFAULT_CONVERSION_PIXELS = {
    meta: { enabled: false, entries: [] },
    tiktok: { enabled: false, entries: [] },
    google_ads: { enabled: false, entries: [] },
    google_analytics: { enabled: false, entries: [] },
    custom_script: [],
};

function mergeConversionPixels(raw) {
    if (!raw || typeof raw !== 'object') return JSON.parse(JSON.stringify(DEFAULT_CONVERSION_PIXELS));
    const out = JSON.parse(JSON.stringify(DEFAULT_CONVERSION_PIXELS));

    function normalizeMetaLike(block, newEntryFn) {
        const enabled = !!block?.enabled;
        if (Array.isArray(block?.entries)) {
            return {
                enabled,
                entries: block.entries
                    .filter((e) => e && typeof e === 'object')
                    .map((e) => ({ ...newEntryFn(), ...e, id: e.id || randomClientId() })),
            };
        }
        if (block?.pixel_id != null || block?.access_token != null) {
            const pixel_id = String(block.pixel_id ?? '').trim();
            const access_token = String(block.access_token ?? '').trim();
            if (pixel_id || access_token) {
                return {
                    enabled,
                    entries: [
                        {
                            id: randomClientId(),
                            pixel_id,
                            access_token,
                            fire_purchase_on_pix: block.fire_purchase_on_pix !== false,
                            fire_purchase_on_boleto: block.fire_purchase_on_boleto !== false,
                            disable_order_bump_events: !!block.disable_order_bump_events,
                        },
                    ],
                };
            }
        }
        return { enabled, entries: [] };
    }

    function normalizeGoogleAdsBlock(block) {
        const enabled = !!block?.enabled;
        if (Array.isArray(block?.entries)) {
            return {
                enabled,
                entries: block.entries
                    .filter((e) => e && typeof e === 'object')
                    .map((e) => ({ ...newGoogleAdsEntry(), ...e, id: e.id || randomClientId() })),
            };
        }
        const conversion_id = String(block?.conversion_id ?? '').trim();
        if (conversion_id) {
            return {
                enabled,
                entries: [
                    {
                        id: randomClientId(),
                        conversion_id,
                        conversion_label: String(block.conversion_label ?? '').trim(),
                        fire_purchase_on_pix: block.fire_purchase_on_pix !== false,
                        fire_purchase_on_boleto: block.fire_purchase_on_boleto !== false,
                        disable_order_bump_events: !!block.disable_order_bump_events,
                    },
                ],
            };
        }
        return { enabled, entries: [] };
    }

    function normalizeGaBlock(block) {
        const enabled = !!block?.enabled;
        if (Array.isArray(block?.entries)) {
            return {
                enabled,
                entries: block.entries
                    .filter((e) => e && typeof e === 'object')
                    .map((e) => ({ ...newGaEntry(), ...e, id: e.id || randomClientId() })),
            };
        }
        const measurement_id = String(block?.measurement_id ?? '').trim();
        if (measurement_id) {
            return {
                enabled,
                entries: [
                    {
                        id: randomClientId(),
                        measurement_id,
                        fire_purchase_on_pix: block.fire_purchase_on_pix !== false,
                        fire_purchase_on_boleto: block.fire_purchase_on_boleto !== false,
                        disable_order_bump_events: !!block.disable_order_bump_events,
                    },
                ],
            };
        }
        return { enabled, entries: [] };
    }

    if (raw.meta && typeof raw.meta === 'object') out.meta = normalizeMetaLike(raw.meta, newMetaEntry);
    if (raw.tiktok && typeof raw.tiktok === 'object') out.tiktok = normalizeMetaLike(raw.tiktok, newTiktokEntry);
    if (raw.google_ads && typeof raw.google_ads === 'object') out.google_ads = normalizeGoogleAdsBlock(raw.google_ads);
    if (raw.google_analytics && typeof raw.google_analytics === 'object') out.google_analytics = normalizeGaBlock(raw.google_analytics);
    out.custom_script = Array.isArray(raw.custom_script)
        ? raw.custom_script
            .filter((s) => s && typeof s === 'object')
            .map((s) => ({ id: s.id || randomClientId(), name: s.name || '', script: s.script || '' }))
        : [];

    return out;
}

const pg = props.application.payment_gateways || {};
const form = useForm({
    name: props.application.name,
    conversion_pixels: mergeConversionPixels(props.application.conversion_pixels),
    payment_gateways: {
        pix: pg.pix ?? '',
        pix_redundancy: Array.isArray(pg.pix_redundancy) ? pg.pix_redundancy : [],
        card: pg.card ?? '',
        card_redundancy: Array.isArray(pg.card_redundancy) ? pg.card_redundancy : [],
        boleto: pg.boleto ?? '',
        boleto_redundancy: Array.isArray(pg.boleto_redundancy) ? pg.boleto_redundancy : [],
        pix_auto: pg.pix_auto ?? '',
        pix_auto_redundancy: Array.isArray(pg.pix_auto_redundancy) ? pg.pix_auto_redundancy : [],
        apple_pay: pg.apple_pay ?? '',
        apple_pay_redundancy: Array.isArray(pg.apple_pay_redundancy) ? pg.apple_pay_redundancy : [],
        google_pay: pg.google_pay ?? '',
        google_pay_redundancy: Array.isArray(pg.google_pay_redundancy) ? pg.google_pay_redundancy : [],
        crypto: pg.crypto ?? '',
        crypto_redundancy: Array.isArray(pg.crypto_redundancy) ? pg.crypto_redundancy : [],
    },
    webhook_url: props.application.webhook_url ?? '',
    default_return_url: props.application.default_return_url ?? '',
    webhook_secret: props.application.webhook_secret ?? '',
    allowed_ips: props.application.allowed_ips ?? '',
    is_active: props.application.is_active !== false,
    checkout_sidebar_bg: props.application.checkout_sidebar_bg ?? '',
});

const showKeyModal = ref(!!props.api_key_reveal);
const revealedKey = ref(props.api_key_reveal ?? '');
const copyKeyFeedback = ref(false);

const logoUrl = ref(props.application.logo_url ?? null);
const logoUploading = ref(false);
const logoError = ref(null);
const logoInputRef = ref(null);

watch(() => props.application.logo_url, (v) => {
    logoUrl.value = v ?? null;
}, { immediate: true });

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

async function onLogoFileChange(ev) {
    const file = ev.target?.files?.[0];
    if (!file) return;
    logoError.value = null;
    logoUploading.value = true;
    try {
        const formData = new FormData();
        formData.append('image', file);
        const res = await fetch(`/aplicacoes-api/${props.application.id}/logo`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            credentials: 'same-origin',
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            logoError.value = data.message || 'Falha ao enviar a logo.';
            return;
        }
        logoUrl.value = data.url ?? null;
    } catch (e) {
        logoError.value = e?.message || 'Erro ao enviar a logo.';
    } finally {
        logoUploading.value = false;
        if (logoInputRef.value) logoInputRef.value.value = '';
    }
}

async function removeLogo() {
    if (!logoUrl.value) return;
    logoError.value = null;
    logoUploading.value = true;
    try {
        const res = await fetch(`/aplicacoes-api/${props.application.id}/logo`, {
            method: 'DELETE',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
            credentials: 'same-origin',
        });
        if (!res.ok) {
            const data = await res.json().catch(() => ({}));
            logoError.value = data.message || 'Falha ao remover a logo.';
            return;
        }
        logoUrl.value = null;
    } catch (e) {
        logoError.value = e?.message || 'Erro ao remover a logo.';
    } finally {
        logoUploading.value = false;
    }
}

watch(() => props.api_key_reveal, (val) => {
    if (val) {
        revealedKey.value = val;
        showKeyModal.value = true;
        copyKeyFeedback.value = false;
    }
}, { immediate: false });

function gatewayOptions(method) {
    const list = props.gateways_by_method?.[method] ?? [];
    return [
        { value: '', label: 'Nenhum' },
        ...list.map((g) => ({ value: g.slug, label: g.name })),
    ];
}

const METHOD_LABELS = { pix: 'PIX', card: 'Cartão', boleto: 'Boleto', pix_auto: 'PIX automático', apple_pay: 'Apple Pay', google_pay: 'Google Pay', crypto: 'Criptomoeda' };
const redundancySidebarOpen = ref(false);
const redundancySidebarMethod = ref(null);

function openRedundancySidebar(method) {
    redundancySidebarMethod.value = method;
    redundancySidebarOpen.value = true;
}

function canShowRedundancy(slug) {
    return slug !== '' && slug != null;
}

function submit() {
    const previousSecret = form.webhook_secret;
    if (props.webhook_secret_mask && previousSecret === props.webhook_secret_mask) {
        form.webhook_secret = '';
    }

    form.put(`/aplicacoes-api/${props.application.id}`, {
        preserveScroll: true,
        onError: () => {
            if (props.webhook_secret_mask && previousSecret === props.webhook_secret_mask) {
                form.webhook_secret = props.webhook_secret_mask;
            }
        },
        onSuccess: () => {
            if (props.webhook_secret_mask && previousSecret && previousSecret !== '') {
                form.webhook_secret = props.webhook_secret_mask;
            }
        },
    });
}

async function copyKey() {
    const text = revealedKey.value;
    if (!text) return;
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
        } else {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            ta.style.top = '0';
            document.body.appendChild(ta);
            ta.focus();
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
        }
        copyKeyFeedback.value = true;
        setTimeout(() => { copyKeyFeedback.value = false; }, 2000);
    } catch {
        copyKeyFeedback.value = false;
    }
}

function closeKeyModal() {
    showKeyModal.value = false;
}

function regenerateKey() {
    if (!window.confirm('Gerar uma nova API key? A key atual deixará de funcionar imediatamente.')) return;
    router.post(`/aplicacoes-api/${props.application.id}/regenerate-key`, {}, { preserveScroll: true });
}
</script>

<template>
    <div class="space-y-5">
        <!-- Cabeçalho -->
        <div class="flex flex-col gap-3">
            <a
                href="/aplicacoes-api"
                class="inline-flex w-fit items-center gap-1.5 text-[12.5px] font-medium text-[var(--ep-text-3)] transition-colors duration-150 hover:text-[var(--ep-text)]"
            >
                <ArrowLeft class="h-3.5 w-3.5" :stroke-width="1.75" />
                API de Pagamentos
            </a>
            <div class="min-w-0">
                <h1 class="text-[22px] font-semibold leading-tight tracking-[-0.025em] text-[var(--ep-text)]">Editar aplicação</h1>
                <div class="mt-1.5 flex flex-wrap items-center gap-2 text-[13px] text-[var(--ep-text-3)]">
                    <span class="min-w-0 truncate font-medium text-[var(--ep-text-2)]">{{ application.name }}</span>
                    <span class="inline-flex h-[22px] items-center rounded-md border border-[var(--ep-input-border)] bg-[var(--ep-input)] px-2 font-mono text-[11.5px] text-[var(--ep-text-3)]">{{ application.slug }}</span>
                    <span class="ep-chip" :class="form.is_active ? 'ep-chip--pos' : 'ep-chip--warn'">
                        <span class="h-1.5 w-1.5 rounded-full bg-current" />
                        {{ form.is_active ? 'Ativa' : 'Inativa' }}
                    </span>
                </div>
            </div>
        </div>

        <form class="grid grid-cols-1 items-start gap-4 xl:grid-cols-[minmax(0,1fr)_300px]" @submit.prevent="submit">
            <div class="min-w-0 space-y-4">
                <!-- Identificação -->
                <section class="panel-card p-5 sm:p-6">
                    <h2 class="ep-section-title flex items-center gap-2">
                        <Tag class="h-4 w-4 text-[var(--ep-text-3)]" :stroke-width="1.75" />
                        Identificação
                    </h2>
                    <div class="mt-4">
                        <label for="api-app-name" class="ep-label">Nome</label>
                        <input id="api-app-name" v-model="form.name" type="text" required class="ep-input" />
                        <p v-if="form.errors.name" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">{{ form.errors.name }}</p>
                    </div>
                </section>

                <!-- Aparência do checkout -->
                <section class="panel-card p-5 sm:p-6">
                    <h2 class="ep-section-title flex items-center gap-2">
                        <ImagePlus class="h-4 w-4 text-[var(--ep-text-3)]" :stroke-width="1.75" />
                        Logo do checkout
                    </h2>
                    <p class="ep-help !mt-1">Exibida no Checkout Pro (página de pagamento hospedada).</p>
                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <div
                            class="h-16 w-28 shrink-0 items-center justify-center rounded-[12px] border border-dashed border-[var(--ep-line-strong)] bg-[var(--ep-card-2)] text-[var(--ep-text-4)]"
                            :class="logoUrl ? 'hidden' : 'flex'"
                            aria-hidden="true"
                        >
                            <ImagePlus class="h-5 w-5" :stroke-width="1.75" />
                        </div>
                        <div v-if="logoUrl" class="flex flex-wrap items-center gap-3">
                            <div class="flex h-16 items-center rounded-[12px] border border-[var(--ep-line)] bg-[var(--ep-card-2)] px-3">
                                <img :src="logoUrl" alt="Logo" class="h-11 w-auto max-w-[180px] object-contain" />
                            </div>
                            <button type="button" class="ep-btn-ghost !h-8 !px-3 !text-[12.5px] text-[var(--ep-neg)] hover:bg-[var(--ep-neg-bg)] hover:text-[var(--ep-neg)]" :disabled="logoUploading" @click="removeLogo">
                                <Trash2 class="h-3.5 w-3.5" :stroke-width="1.75" />
                                Remover logo
                            </button>
                        </div>
                        <div class="flex items-center gap-2">
                            <input
                                ref="logoInputRef"
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="onLogoFileChange"
                            />
                            <button type="button" class="ep-btn-secondary !h-8 !px-3 !text-[12.5px]" :disabled="logoUploading" @click="logoInputRef?.click()">
                                <ImagePlus class="h-3.5 w-3.5" :stroke-width="1.75" />
                                {{ logoUrl ? 'Trocar logo' : 'Enviar logo' }}
                            </button>
                        </div>
                    </div>
                    <p v-if="logoError" class="mt-2 text-[12px] text-[var(--ep-neg)]">{{ logoError }}</p>

                    <div class="ep-divider my-5" />

                    <h3 class="ep-section-title flex items-center gap-2">
                        <Palette class="h-4 w-4 text-[var(--ep-text-3)]" :stroke-width="1.75" />
                        Cor de fundo do checkout
                    </h3>
                    <p class="ep-help !mt-1">Cor da coluna esquerda (resumo) no Checkout Pro.</p>
                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <input
                            :value="form.checkout_sidebar_bg || '#18181b'"
                            type="color"
                            class="h-[38px] w-12 cursor-pointer rounded-[10px] border border-[var(--ep-input-border)] bg-[var(--ep-input)] p-1"
                            :title="form.checkout_sidebar_bg || '#18181b'"
                            @input="form.checkout_sidebar_bg = $event.target.value"
                        />
                        <input
                            v-model="form.checkout_sidebar_bg"
                            type="text"
                            class="ep-input !w-32 font-mono !text-[12.5px] tabular-nums"
                            placeholder="#18181b"
                            maxlength="7"
                        />
                        <button type="button" class="ep-btn-ghost !h-[38px]" @click="form.checkout_sidebar_bg = ''">
                            <RotateCcw class="h-3.5 w-3.5" :stroke-width="1.75" />
                            Restaurar padrão
                        </button>
                    </div>
                    <p v-if="form.errors.checkout_sidebar_bg" class="mt-2 text-[12px] text-[var(--ep-neg)]">{{ form.errors.checkout_sidebar_bg }}</p>
                </section>

                <!-- Gateways -->
                <section class="panel-card p-5 sm:p-6">
                    <h2 class="ep-section-title flex items-center gap-2">
                        <Settings2 class="h-4 w-4 text-[var(--ep-text-3)]" :stroke-width="1.75" />
                        Gateways por método
                    </h2>
                    <div class="mt-4 overflow-hidden rounded-[14px] border border-[var(--ep-line)] bg-[var(--ep-card-2)]">
                        <template v-for="method in ['pix', 'card', 'boleto', 'apple_pay', 'google_pay', 'pix_auto', 'crypto']" :key="method">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-2 border-b border-[var(--ep-line)] px-4 py-3 last:border-b-0">
                                <span class="flex w-32 shrink-0 items-center gap-2 text-[13px] font-medium text-[var(--ep-text-2)]">
                                    <span
                                        class="h-2 w-2 shrink-0 rounded-full"
                                        :class="method === 'pix' ? 'bg-[var(--ep-pix)]' : method === 'card' ? 'bg-[var(--ep-cartao)]' : method === 'boleto' ? 'bg-[var(--ep-boleto)]' : 'bg-[var(--ep-text-4)]'"
                                    />
                                    {{ METHOD_LABELS[method] || method }}
                                </span>
                                <select
                                    v-model="form.payment_gateways[method]"
                                    class="ep-input min-w-[180px] flex-1 sm:max-w-xs"
                                >
                                    <option v-for="opt in gatewayOptions(method)" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                </select>
                                <button
                                    v-if="canShowRedundancy(form.payment_gateways[method])"
                                    type="button"
                                    class="ep-btn-secondary !h-8 !px-3 !text-[12.5px]"
                                    @click="openRedundancySidebar(method)"
                                >
                                    <Layers class="h-3.5 w-3.5" :stroke-width="1.75" />
                                    Redundância
                                </button>
                            </div>
                        </template>
                    </div>
                </section>

                <!-- Pixels de conversão -->
                <section class="panel-card p-5 sm:p-6">
                    <h2 class="ep-section-title flex items-center gap-2">
                        <Target class="h-4 w-4 text-[var(--ep-text-3)]" :stroke-width="1.75" />
                        Pixels de conversão (Checkout Pro)
                    </h2>
                    <p class="ep-help !mt-1">
                        Esses pixels serão usados no checkout hospedado (<code class="rounded-md bg-[var(--ep-card-2)] px-1 py-px font-mono text-[11.5px] text-[var(--ep-text-3)]">/api-checkout</code>) desta aplicação.
                    </p>

                    <div class="mt-4 space-y-3">
                        <div class="rounded-[14px] border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4 space-y-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-[13.5px] font-semibold tracking-[-0.01em] text-[var(--ep-text)]">Meta Pixel</p>
                                    <p class="mt-0.5 text-[12px] text-[var(--ep-text-4)]">Dispara PageView e Purchase quando configurado.</p>
                                </div>
                                <label class="inline-flex h-7 shrink-0 cursor-pointer items-center gap-2 rounded-full border border-[var(--ep-line-strong)] bg-[var(--ep-glass)] px-2.5 text-[12px] font-medium text-[var(--ep-text-2)]">
                                    <input v-model="form.conversion_pixels.meta.enabled" type="checkbox" class="h-3.5 w-3.5 cursor-pointer rounded accent-[var(--ep-accent)]" />
                                    Ativo
                                </label>
                            </div>
                            <div v-if="form.conversion_pixels.meta.enabled" class="space-y-2.5">
                                <div v-if="form.conversion_pixels.meta.entries.length === 0" class="rounded-[12px] border border-dashed border-[var(--ep-line-strong)] px-3 py-3 text-center text-[12.5px] text-[var(--ep-text-4)]">Nenhum pixel adicionado.</div>
                                <div v-for="(item, idx) in form.conversion_pixels.meta.entries" :key="item.id" class="grid grid-cols-1 items-end gap-3 rounded-[12px] border border-[var(--ep-line)] bg-[var(--ep-glass)] p-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]">
                                    <div class="min-w-0">
                                        <label class="ep-label !text-[12px]">Pixel ID</label>
                                        <input v-model="item.pixel_id" type="text" class="ep-input font-mono !text-[12.5px]" placeholder="1234567890" />
                                    </div>
                                    <div class="min-w-0">
                                        <label class="ep-label !text-[12px]">Access token (opcional)</label>
                                        <input v-model="item.access_token" type="text" class="ep-input font-mono !text-[12.5px]" placeholder="EAAB..." />
                                    </div>
                                    <div class="flex justify-end">
                                        <button type="button" class="ep-btn-ghost !h-[38px] !px-3 !text-[12.5px] text-[var(--ep-neg)] hover:bg-[var(--ep-neg-bg)] hover:text-[var(--ep-neg)]" @click="form.conversion_pixels.meta.entries.splice(idx, 1)">
                                            <Trash2 class="h-3.5 w-3.5" :stroke-width="1.75" />
                                            Remover
                                        </button>
                                    </div>
                                </div>
                                <div class="flex justify-end">
                                    <button type="button" class="ep-btn-secondary !h-8 !px-3 !text-[12.5px]" @click="form.conversion_pixels.meta.entries.push(newMetaEntry())">
                                        <Plus class="h-3.5 w-3.5" :stroke-width="1.75" />
                                        Adicionar pixel
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-[14px] border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4 space-y-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-[13.5px] font-semibold tracking-[-0.01em] text-[var(--ep-text)]">TikTok Pixel</p>
                                    <p class="mt-0.5 text-[12px] text-[var(--ep-text-4)]">Dispara PageView e Purchase quando configurado.</p>
                                </div>
                                <label class="inline-flex h-7 shrink-0 cursor-pointer items-center gap-2 rounded-full border border-[var(--ep-line-strong)] bg-[var(--ep-glass)] px-2.5 text-[12px] font-medium text-[var(--ep-text-2)]">
                                    <input v-model="form.conversion_pixels.tiktok.enabled" type="checkbox" class="h-3.5 w-3.5 cursor-pointer rounded accent-[var(--ep-accent)]" />
                                    Ativo
                                </label>
                            </div>
                            <div v-if="form.conversion_pixels.tiktok.enabled" class="space-y-2.5">
                                <div v-if="form.conversion_pixels.tiktok.entries.length === 0" class="rounded-[12px] border border-dashed border-[var(--ep-line-strong)] px-3 py-3 text-center text-[12.5px] text-[var(--ep-text-4)]">Nenhum pixel adicionado.</div>
                                <div v-for="(item, idx) in form.conversion_pixels.tiktok.entries" :key="item.id" class="grid grid-cols-1 items-end gap-3 rounded-[12px] border border-[var(--ep-line)] bg-[var(--ep-glass)] p-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]">
                                    <div class="min-w-0">
                                        <label class="ep-label !text-[12px]">Pixel ID</label>
                                        <input v-model="item.pixel_id" type="text" class="ep-input font-mono !text-[12.5px]" placeholder="Cxxxxxxxx" />
                                    </div>
                                    <div class="min-w-0">
                                        <label class="ep-label !text-[12px]">Access token (opcional)</label>
                                        <input v-model="item.access_token" type="text" class="ep-input font-mono !text-[12.5px]" placeholder="xxxx" />
                                    </div>
                                    <div class="flex justify-end">
                                        <button type="button" class="ep-btn-ghost !h-[38px] !px-3 !text-[12.5px] text-[var(--ep-neg)] hover:bg-[var(--ep-neg-bg)] hover:text-[var(--ep-neg)]" @click="form.conversion_pixels.tiktok.entries.splice(idx, 1)">
                                            <Trash2 class="h-3.5 w-3.5" :stroke-width="1.75" />
                                            Remover
                                        </button>
                                    </div>
                                </div>
                                <div class="flex justify-end">
                                    <button type="button" class="ep-btn-secondary !h-8 !px-3 !text-[12.5px]" @click="form.conversion_pixels.tiktok.entries.push(newTiktokEntry())">
                                        <Plus class="h-3.5 w-3.5" :stroke-width="1.75" />
                                        Adicionar pixel
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-[14px] border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4 space-y-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-[13.5px] font-semibold tracking-[-0.01em] text-[var(--ep-text)]">Google Ads</p>
                                    <p class="mt-0.5 text-[12px] text-[var(--ep-text-4)]">Usa gtag (conversion_id / conversion_label).</p>
                                </div>
                                <label class="inline-flex h-7 shrink-0 cursor-pointer items-center gap-2 rounded-full border border-[var(--ep-line-strong)] bg-[var(--ep-glass)] px-2.5 text-[12px] font-medium text-[var(--ep-text-2)]">
                                    <input v-model="form.conversion_pixels.google_ads.enabled" type="checkbox" class="h-3.5 w-3.5 cursor-pointer rounded accent-[var(--ep-accent)]" />
                                    Ativo
                                </label>
                            </div>
                            <div v-if="form.conversion_pixels.google_ads.enabled" class="space-y-2.5">
                                <div v-if="form.conversion_pixels.google_ads.entries.length === 0" class="rounded-[12px] border border-dashed border-[var(--ep-line-strong)] px-3 py-3 text-center text-[12.5px] text-[var(--ep-text-4)]">Nenhuma conversão adicionada.</div>
                                <div v-for="(item, idx) in form.conversion_pixels.google_ads.entries" :key="item.id" class="grid grid-cols-1 items-end gap-3 rounded-[12px] border border-[var(--ep-line)] bg-[var(--ep-glass)] p-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]">
                                    <div class="min-w-0">
                                        <label class="ep-label !text-[12px]">Conversion ID</label>
                                        <input v-model="item.conversion_id" type="text" class="ep-input font-mono !text-[12.5px]" placeholder="AW-XXXX" />
                                    </div>
                                    <div class="min-w-0">
                                        <label class="ep-label !text-[12px]">Conversion label</label>
                                        <input v-model="item.conversion_label" type="text" class="ep-input font-mono !text-[12.5px]" placeholder="abcdEFGH" />
                                    </div>
                                    <div class="flex justify-end">
                                        <button type="button" class="ep-btn-ghost !h-[38px] !px-3 !text-[12.5px] text-[var(--ep-neg)] hover:bg-[var(--ep-neg-bg)] hover:text-[var(--ep-neg)]" @click="form.conversion_pixels.google_ads.entries.splice(idx, 1)">
                                            <Trash2 class="h-3.5 w-3.5" :stroke-width="1.75" />
                                            Remover
                                        </button>
                                    </div>
                                </div>
                                <div class="flex justify-end">
                                    <button type="button" class="ep-btn-secondary !h-8 !px-3 !text-[12.5px]" @click="form.conversion_pixels.google_ads.entries.push(newGoogleAdsEntry())">
                                        <Plus class="h-3.5 w-3.5" :stroke-width="1.75" />
                                        Adicionar conversão
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-[14px] border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4 space-y-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-[13.5px] font-semibold tracking-[-0.01em] text-[var(--ep-text)]">Google Analytics (GA4)</p>
                                    <p class="mt-0.5 text-[12px] text-[var(--ep-text-4)]">Measurement IDs (G-XXXX).</p>
                                </div>
                                <label class="inline-flex h-7 shrink-0 cursor-pointer items-center gap-2 rounded-full border border-[var(--ep-line-strong)] bg-[var(--ep-glass)] px-2.5 text-[12px] font-medium text-[var(--ep-text-2)]">
                                    <input v-model="form.conversion_pixels.google_analytics.enabled" type="checkbox" class="h-3.5 w-3.5 cursor-pointer rounded accent-[var(--ep-accent)]" />
                                    Ativo
                                </label>
                            </div>
                            <div v-if="form.conversion_pixels.google_analytics.enabled" class="space-y-2.5">
                                <div v-if="form.conversion_pixels.google_analytics.entries.length === 0" class="rounded-[12px] border border-dashed border-[var(--ep-line-strong)] px-3 py-3 text-center text-[12.5px] text-[var(--ep-text-4)]">Nenhuma propriedade adicionada.</div>
                                <div v-for="(item, idx) in form.conversion_pixels.google_analytics.entries" :key="item.id" class="grid grid-cols-1 items-end gap-3 rounded-[12px] border border-[var(--ep-line)] bg-[var(--ep-glass)] p-3 sm:grid-cols-[minmax(0,1fr)_auto]">
                                    <div class="min-w-0">
                                        <label class="ep-label !text-[12px]">Measurement ID</label>
                                        <input v-model="item.measurement_id" type="text" class="ep-input font-mono !text-[12.5px]" placeholder="G-XXXX" />
                                    </div>
                                    <div class="flex justify-end">
                                        <button type="button" class="ep-btn-ghost !h-[38px] !px-3 !text-[12.5px] text-[var(--ep-neg)] hover:bg-[var(--ep-neg-bg)] hover:text-[var(--ep-neg)]" @click="form.conversion_pixels.google_analytics.entries.splice(idx, 1)">
                                            <Trash2 class="h-3.5 w-3.5" :stroke-width="1.75" />
                                            Remover
                                        </button>
                                    </div>
                                </div>
                                <div class="flex justify-end">
                                    <button type="button" class="ep-btn-secondary !h-8 !px-3 !text-[12.5px]" @click="form.conversion_pixels.google_analytics.entries.push(newGaEntry())">
                                        <Plus class="h-3.5 w-3.5" :stroke-width="1.75" />
                                        Adicionar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-[14px] border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4 space-y-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-[13.5px] font-semibold tracking-[-0.01em] text-[var(--ep-text)]">Script personalizado</p>
                                    <p class="mt-0.5 text-[12px] text-[var(--ep-text-4)]">Inserido no <span class="font-mono">&lt;head&gt;</span> do checkout.</p>
                                </div>
                                <button type="button" class="ep-btn-secondary !h-8 !px-3 !text-[12.5px]" @click="form.conversion_pixels.custom_script.push({ id: randomClientId(), name: '', script: '' })">
                                    <Plus class="h-3.5 w-3.5" :stroke-width="1.75" />
                                    Adicionar
                                </button>
                            </div>
                            <div v-if="form.conversion_pixels.custom_script.length === 0" class="rounded-[12px] border border-dashed border-[var(--ep-line-strong)] px-3 py-3 text-center text-[12.5px] text-[var(--ep-text-4)]">Nenhum script adicionado.</div>
                            <div v-for="(item, idx) in form.conversion_pixels.custom_script" :key="item.id" class="rounded-[12px] border border-[var(--ep-line)] bg-[var(--ep-glass)] p-3">
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div class="min-w-0">
                                        <label class="ep-label !text-[12px]">Nome</label>
                                        <input v-model="item.name" type="text" class="ep-input !text-[12.5px]" placeholder="Meu pixel" />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="ep-label !text-[12px]">Script</label>
                                        <textarea v-model="item.script" rows="4" class="ep-input font-mono !text-[12px]" placeholder="&lt;script&gt;...&lt;/script&gt;" />
                                    </div>
                                    <div class="sm:col-span-2 flex justify-end">
                                        <button type="button" class="ep-btn-ghost !h-8 !px-3 !text-[12.5px] text-[var(--ep-neg)] hover:bg-[var(--ep-neg-bg)] hover:text-[var(--ep-neg)]" @click="form.conversion_pixels.custom_script.splice(idx, 1)">
                                            <Trash2 class="h-3.5 w-3.5" :stroke-width="1.75" />
                                            Remover
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p v-if="form.errors.conversion_pixels" class="mt-3 text-[12px] text-[var(--ep-neg)]">{{ form.errors.conversion_pixels }}</p>
                </section>

                <!-- Webhooks e segurança -->
                <section class="panel-card p-5 sm:p-6">
                    <h2 class="ep-section-title flex items-center gap-2">
                        <Webhook class="h-4 w-4 text-[var(--ep-text-3)]" :stroke-width="1.75" />
                        Webhooks e retorno
                    </h2>
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="min-w-0">
                            <label for="api-app-webhook-url" class="ep-label">URL do webhook (opcional)</label>
                            <input id="api-app-webhook-url" v-model="form.webhook_url" type="url" class="ep-input font-mono !text-[12.5px]" placeholder="https://..." />
                            <p v-if="form.errors.webhook_url" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">{{ form.errors.webhook_url }}</p>
                        </div>
                        <div class="min-w-0">
                            <label for="api-app-return-url" class="ep-label">URL de retorno padrão (opcional)</label>
                            <input id="api-app-return-url" v-model="form.default_return_url" type="url" class="ep-input font-mono !text-[12.5px]" placeholder="https://..." />
                            <p class="ep-help">
                                Usada no Checkout Pro quando a sessão não enviar <code class="rounded-md bg-[var(--ep-card-2)] px-1 py-px font-mono text-[11.5px] text-[var(--ep-text-3)]">return_url</code>.
                            </p>
                            <p v-if="form.errors.default_return_url" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">{{ form.errors.default_return_url }}</p>
                        </div>
                    </div>

                    <div class="ep-divider my-5" />

                    <h3 class="ep-section-title flex items-center gap-2">
                        <ShieldCheck class="h-4 w-4 text-[var(--ep-text-3)]" :stroke-width="1.75" />
                        Segurança
                    </h3>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label for="api-app-webhook-secret" class="ep-label">Webhook secret (opcional)</label>
                            <input id="api-app-webhook-secret" v-model="form.webhook_secret" type="password" autocomplete="off" class="ep-input font-mono" placeholder="Secret para validar assinatura HMAC" />
                            <p class="ep-help">Usado para assinar o body do webhook (<span class="font-mono">X-Getfy-Signature</span>). Deixe em branco para não alterar.</p>
                            <p v-if="form.errors.webhook_secret" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">{{ form.errors.webhook_secret }}</p>
                        </div>

                        <div>
                            <label for="api-app-allowed-ips" class="ep-label">IPs permitidos (opcional)</label>
                            <textarea id="api-app-allowed-ips" v-model="form.allowed_ips" rows="3" class="ep-input font-mono !text-[12.5px]" placeholder="Um IP por linha ou separados por vírgula. Vazio = todos permitidos."></textarea>
                            <p v-if="form.errors.allowed_ips" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">{{ form.errors.allowed_ips }}</p>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Lateral: publicação + credenciais -->
            <aside class="min-w-0 space-y-4 xl:sticky xl:top-6">
                <section class="panel-card ep-glow-card p-5">
                    <h2 class="ep-section-title">Publicação</h2>
                    <div class="mt-3 flex items-start gap-3 rounded-[12px] border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-3">
                        <input v-model="form.is_active" type="checkbox" id="is_active" class="mt-0.5 h-4 w-4 shrink-0 cursor-pointer rounded accent-[var(--ep-accent)]" />
                        <label for="is_active" class="cursor-pointer text-[13px] font-medium text-[var(--ep-text)]">Aplicação ativa</label>
                    </div>
                    <div class="ep-divider my-4" />
                    <div class="flex flex-col gap-2">
                        <button type="submit" class="ep-btn w-full" :disabled="form.processing">Salvar</button>
                        <a href="/aplicacoes-api" class="ep-btn-ghost w-full">Voltar</a>
                    </div>
                </section>

                <section class="panel-card p-5">
                    <h2 class="ep-section-title flex items-center gap-2">
                        <KeyRound class="h-4 w-4 text-[var(--ep-text-3)]" :stroke-width="1.75" />
                        Credenciais
                    </h2>
                    <dl class="mt-3 space-y-3">
                        <div>
                            <dt class="mb-1.5 text-[12px] font-medium text-[var(--ep-text-3)]">Slug</dt>
                            <dd class="flex h-9 min-w-0 items-center rounded-[10px] border border-[var(--ep-input-border)] bg-[var(--ep-input)] px-3 font-mono text-[12.5px] text-[var(--ep-text-2)]">
                                <span class="truncate">{{ application.slug }}</span>
                            </dd>
                        </div>
                        <div>
                            <dt class="mb-1.5 text-[12px] font-medium text-[var(--ep-text-3)]">API key</dt>
                            <dd class="flex h-9 min-w-0 items-center gap-2 rounded-[10px] border border-[var(--ep-input-border)] bg-[var(--ep-input)] px-3 font-mono text-[12.5px] text-[var(--ep-text-4)]">
                                <Lock class="h-3.5 w-3.5 shrink-0" :stroke-width="1.75" aria-hidden="true" />
                                <span class="truncate tracking-[0.2em]" aria-label="API key oculta">••••••••••••••••••••</span>
                            </dd>
                            <p class="ep-help">Exibida uma única vez, quando é gerada.</p>
                        </div>
                    </dl>
                    <div class="ep-divider my-4" />
                    <button type="button" class="ep-btn-secondary w-full" @click="regenerateKey">
                        <RefreshCw class="h-4 w-4" :stroke-width="1.75" />
                        Gerar nova API key
                    </button>
                    <p class="mt-2 flex items-start gap-1.5 text-[12px] leading-snug text-[var(--ep-text-4)]">
                        <AlertTriangle class="mt-px h-3.5 w-3.5 shrink-0 text-[var(--ep-warn)]" :stroke-width="1.75" />
                        A key atual deixa de funcionar imediatamente.
                    </p>
                </section>
            </aside>
        </form>

        <GatewayRedundancySidebar
            :open="redundancySidebarOpen"
            :method="redundancySidebarMethod"
            :method-label="METHOD_LABELS[redundancySidebarMethod] || redundancySidebarMethod"
            :primary-slug="redundancySidebarMethod ? (form.payment_gateways[redundancySidebarMethod] || '') : ''"
            :gateways="gateways_by_method[redundancySidebarMethod] || []"
            :model-value="redundancySidebarMethod ? (form.payment_gateways[redundancySidebarMethod + '_redundancy'] || []) : []"
            @update:model-value="(val) => redundancySidebarMethod && (form.payment_gateways[redundancySidebarMethod + '_redundancy'] = val)"
            @save="(val) => { if (redundancySidebarMethod) { form.payment_gateways[redundancySidebarMethod + '_redundancy'] = val; } redundancySidebarOpen = false; }"
            @close="redundancySidebarOpen = false"
        />
    </div>

    <!-- Modal: API key (mostrar uma vez) -->
    <Teleport to="body">
        <div v-show="showKeyModal && revealedKey" class="fixed inset-0 z-[100000] flex items-center justify-center p-4" aria-modal="true" role="dialog">
            <div class="ep-scrim fixed inset-0" aria-hidden="true" @click="closeKeyModal" />
            <div class="ep-modal relative w-full max-w-lg p-6">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="ep-kpi__icon shrink-0">
                            <KeyRound class="h-4 w-4" :stroke-width="1.75" />
                        </span>
                        <div>
                            <h2 class="text-[16px] font-semibold tracking-[-0.015em] text-[var(--ep-text)]">Sua API key</h2>
                            <span class="ep-chip ep-chip--warn mt-1">
                                <span class="h-1.5 w-1.5 rounded-full bg-current" />
                                Exibida uma única vez
                            </span>
                        </div>
                    </div>
                    <button type="button" class="ep-btn-ghost ep-btn-icon !h-8 !w-8" aria-label="Fechar" @click="closeKeyModal">
                        <X class="h-4 w-4" :stroke-width="1.75" />
                    </button>
                </div>
                <p class="mt-4 text-[13px] leading-relaxed text-[var(--ep-text-3)]">
                    Copie agora. Esta key não será exibida novamente.
                </p>
                <div class="mt-3 flex min-h-12 items-center gap-2 rounded-[10px] border border-[var(--ep-input-border)] bg-[var(--ep-input)] py-1.5 pl-3.5 pr-1.5">
                    <code class="min-w-0 flex-1 select-all break-all font-mono text-[13px] leading-relaxed text-[var(--ep-text)]">{{ revealedKey }}</code>
                    <button
                        type="button"
                        class="ep-btn-secondary ep-btn-icon shrink-0"
                        title="Copiar"
                        @click="copyKey"
                    >
                        <Check v-if="copyKeyFeedback" class="h-4 w-4 text-[var(--ep-pos)]" :stroke-width="1.75" />
                        <Copy v-else class="h-4 w-4" :stroke-width="1.75" />
                        <span class="sr-only" aria-live="polite">{{ copyKeyFeedback ? 'Copiado!' : 'Copiar' }}</span>
                    </button>
                </div>
                <p class="mt-2 text-[12px]" :class="copyKeyFeedback ? 'text-[var(--ep-pos)]' : 'text-[var(--ep-text-4)]'">
                    {{ copyKeyFeedback ? 'Copiado!' : 'Use o botão ao lado para copiar.' }}
                </p>
                <button type="button" class="ep-btn mt-5 w-full" @click="closeKeyModal">Entendi</button>
            </div>
        </div>
    </Teleport>
</template>
