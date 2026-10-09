<script setup>
import { ref, computed, watch } from 'vue';
import Button from '@/components/ui/Button.vue';
import Toggle from '@/components/ui/Toggle.vue';
import Checkbox from '@/components/ui/Checkbox.vue';
import { Plus, Trash2, ChevronDown } from 'lucide-vue-next';
import {
    PIXEL_TABS,
    newMetaEntry,
    newTiktokEntry,
    newGoogleAdsEntry,
    newGaEntry,
    randomClientId,
} from '@/lib/conversionPixels';
import PixelIntegrationPicker from '@/components/produtos/PixelIntegrationPicker.vue';
import { Link } from '@inertiajs/vue3';

const model = defineModel({ type: Object, required: true });

const props = defineProps({
    disabled: { type: Boolean, default: false },
    availableIntegrations: { type: Object, default: () => ({}) },
    /** Quando false, oculta GTM e scripts personalizados (ex.: painel do afiliado). */
    allowCustomScript: { type: Boolean, default: true },
    allowGtm: { type: Boolean, default: true },
});

const visiblePixelTabs = computed(() =>
    PIXEL_TABS.filter((tab) => {
        if (tab.id === 'custom_script' && !props.allowCustomScript) return false;
        if (tab.id === 'gtm' && !props.allowGtm) return false;
        return true;
    })
);

function scriptIntegrations() {
    return props.availableIntegrations?.custom_script || [];
}

function usesScriptIntegrations() {
    return Array.isArray(model.value.custom_script_integration_ids) && model.value.custom_script_integration_ids.length > 0;
}

function toggleScriptIntegration(id, checked) {
    if (!Array.isArray(model.value.custom_script_integration_ids)) {
        model.value.custom_script_integration_ids = [];
    }
    const numId = Number(id);
    if (checked) {
        if (!model.value.custom_script_integration_ids.includes(numId)) {
            model.value.custom_script_integration_ids.push(numId);
        }
        model.value.custom_script = [];
    } else {
        model.value.custom_script_integration_ids = model.value.custom_script_integration_ids.filter((x) => x !== numId);
    }
}

function isScriptIntegrationSelected(id) {
    return (model.value.custom_script_integration_ids || []).map(Number).includes(Number(id));
}

const selectedPixelTab = ref('meta');

watch(visiblePixelTabs, (tabs) => {
    if (!tabs.some((tab) => tab.id === selectedPixelTab.value)) {
        selectedPixelTab.value = tabs[0]?.id || 'meta';
    }
}, { immediate: true });

const inputClass =
    'w-full rounded-xl border border-zinc-200 bg-white px-4 py-2.5 text-sm text-zinc-900 outline-none transition focus:border-[var(--color-primary)] focus:ring-2 focus:ring-[color-mix(in_srgb,var(--color-primary)_25%,transparent)] dark:border-zinc-700 dark:bg-zinc-800 dark:text-white disabled:opacity-60';
</script>

<template>
    <div class="space-y-5" :class="{ 'pointer-events-none opacity-60': disabled }">
        <div class="-mx-1 flex gap-2.5 overflow-x-auto scroll-smooth px-1 pb-2" style="scrollbar-width: thin;">
            <button
                v-for="tab in visiblePixelTabs"
                :key="tab.id"
                type="button"
                :disabled="disabled"
                :class="[
                    'relative flex h-[92px] w-[112px] shrink-0 flex-col items-center justify-center gap-2 rounded-2xl border p-3 transition-[background-color,border-color,box-shadow,transform] duration-150 active:scale-[0.98]',
                    selectedPixelTab === tab.id
                        ? 'border-[color-mix(in_oklab,var(--ep-accent)_55%,transparent)] bg-[color-mix(in_oklab,var(--ep-accent)_12%,transparent)] shadow-[inset_0_1px_0_rgba(255,255,255,0.12),0_0_22px_-8px_var(--ep-glow)]'
                        : 'border-[var(--ep-line)] bg-[var(--ep-card-2)] hover:border-[var(--ep-line-strong)] hover:bg-[var(--ep-hover)]',
                ]"
                @click="selectedPixelTab = tab.id"
            >
                <span
                    class="absolute right-2.5 top-2.5 h-1.5 w-1.5 rounded-full bg-[var(--ep-pos)] transition-opacity duration-150"
                    :class="model[tab.id]?.enabled ? 'opacity-100' : 'opacity-0'"
                    aria-hidden="true"
                />
                <img
                    :src="tab.image"
                    :alt="tab.label"
                    class="h-8 w-8 object-contain"
                    @error="($e) => $e.target && ($e.target.style.display = 'none')"
                />
                <span
                    class="max-w-full truncate text-[12px] font-medium"
                    :class="selectedPixelTab === tab.id ? 'text-[var(--ep-text)]' : 'text-[var(--ep-text-3)]'"
                >{{ tab.label }}</span>
            </button>
        </div>

        <div v-if="selectedPixelTab === 'meta'" class="panel-card space-y-5 p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-[14px] font-semibold tracking-[-0.01em] text-[var(--ep-text)]">Meta Ads (Facebook)</h3>
                <div class="flex items-center gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        :disabled="disabled || !model.meta.enabled"
                        @click="model.meta.entries.push(newMetaEntry())"
                    >
                        <Plus class="h-3.5 w-3.5" stroke-width="2" /> Adicionar pixel
                    </Button>
                    <Toggle v-model="model.meta.enabled" :disabled="disabled" />
                </div>
            </div>
            <template v-if="model.meta.enabled">
                <PixelIntegrationPicker
                    platform="meta"
                    :block="model.meta"
                    :integrations="availableIntegrations?.meta || []"
                    :disabled="disabled"
                />
                <details v-if="!(model.meta.integration_ids?.length)" class="group overflow-hidden rounded-2xl border border-[var(--ep-line)]">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 text-[13px] font-medium text-[var(--ep-text-2)] transition-colors duration-150 hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)] [&::-webkit-details-marker]:hidden">
                        Configuração manual (avançado)
                        <ChevronDown class="h-4 w-4 text-[var(--ep-text-4)] transition-transform duration-200 group-open:rotate-180" stroke-width="1.75" aria-hidden="true" />
                    </summary>
                    <div class="space-y-3 border-t border-[var(--ep-line)] p-4">
                <div v-for="(item, idx) in model.meta.entries" :key="item.id" class="space-y-4 rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4">
                    <div class="flex items-center justify-between gap-2">
                        <span class="ep-chip tabular-nums">Pixel {{ idx + 1 }}</span>
                        <button
                            type="button"
                            class="ep-btn-ghost ep-btn-icon !h-8 !w-8 !rounded-[10px] hover:!bg-[var(--ep-neg-bg)] hover:!text-[var(--ep-neg)]"
                            :disabled="disabled"
                            @click="model.meta.entries.splice(idx, 1)"
                        >
                            <Trash2 class="h-4 w-4" stroke-width="1.75" />
                        </button>
                    </div>
                    <div>
                        <label class="ep-label">Pixel ID</label>
                        <input v-model="item.pixel_id" type="text" placeholder="Ex: 123456789" class="ep-input font-mono !text-[13px] disabled:opacity-60" :disabled="disabled" />
                    </div>
                    <div>
                        <label class="ep-label">Access Token (CAPI)</label>
                        <input
                            v-model="item.access_token"
                            type="password"
                            placeholder="Token para Conversions API"
                            class="ep-input disabled:opacity-60"
                            autocomplete="off"
                            :disabled="disabled"
                        />
                    </div>
                    <div class="space-y-3 border-t border-[var(--ep-line)] pt-4">
                        <Checkbox v-model="item.fire_purchase_on_pix" label="Disparar Purchase ao gerar PIX (não na aprovação)?" :disabled="disabled" />
                        <Checkbox v-model="item.fire_purchase_on_boleto" label="Disparar Purchase ao gerar boleto (não na aprovação)?" :disabled="disabled" />
                        <Checkbox v-model="item.disable_order_bump_events" label="Desativar eventos de order bumps?" :disabled="disabled" />
                    </div>
                </div>
                <p v-if="model.meta.entries.length === 0" class="rounded-xl border border-dashed border-[var(--ep-line-strong)] px-4 py-6 text-center text-[12.5px] text-[var(--ep-text-4)]">
                    Nenhum pixel. Clique em «Adicionar pixel» ou desative a integração.
                </p>
                    </div>
                </details>
            </template>
        </div>

        <div v-if="selectedPixelTab === 'tiktok'" class="panel-card space-y-5 p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-[14px] font-semibold tracking-[-0.01em] text-[var(--ep-text)]">TikTok Ads</h3>
                <div class="flex items-center gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        :disabled="disabled || !model.tiktok.enabled"
                        @click="model.tiktok.entries.push(newTiktokEntry())"
                    >
                        <Plus class="h-3.5 w-3.5" stroke-width="2" /> Adicionar pixel
                    </Button>
                    <Toggle v-model="model.tiktok.enabled" :disabled="disabled" />
                </div>
            </div>
            <template v-if="model.tiktok.enabled">
                <PixelIntegrationPicker
                    platform="tiktok"
                    :block="model.tiktok"
                    :integrations="availableIntegrations?.tiktok || []"
                    :disabled="disabled"
                />
                <details v-if="!(model.tiktok.integration_ids?.length)" class="group overflow-hidden rounded-2xl border border-[var(--ep-line)]">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 text-[13px] font-medium text-[var(--ep-text-2)] transition-colors duration-150 hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)] [&::-webkit-details-marker]:hidden">
                        Configuração manual (avançado)
                        <ChevronDown class="h-4 w-4 text-[var(--ep-text-4)] transition-transform duration-200 group-open:rotate-180" stroke-width="1.75" aria-hidden="true" />
                    </summary>
                    <div class="space-y-3 border-t border-[var(--ep-line)] p-4">
                <div v-for="(item, idx) in model.tiktok.entries" :key="item.id" class="space-y-4 rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4">
                    <div class="flex items-center justify-between gap-2">
                        <span class="ep-chip tabular-nums">Pixel {{ idx + 1 }}</span>
                        <button
                            type="button"
                            class="ep-btn-ghost ep-btn-icon !h-8 !w-8 !rounded-[10px] hover:!bg-[var(--ep-neg-bg)] hover:!text-[var(--ep-neg)]"
                            :disabled="disabled"
                            @click="model.tiktok.entries.splice(idx, 1)"
                        >
                            <Trash2 class="h-4 w-4" stroke-width="1.75" />
                        </button>
                    </div>
                    <div>
                        <label class="ep-label">Pixel ID</label>
                        <input v-model="item.pixel_id" type="text" placeholder="Ex: C1X2Y3Z4..." class="ep-input font-mono !text-[13px] disabled:opacity-60" :disabled="disabled" />
                    </div>
                    <div>
                        <label class="ep-label">Access Token</label>
                        <input
                            v-model="item.access_token"
                            type="password"
                            placeholder="Token do TikTok Events API"
                            class="ep-input disabled:opacity-60"
                            autocomplete="off"
                            :disabled="disabled"
                        />
                    </div>
                    <div class="space-y-3 border-t border-[var(--ep-line)] pt-4">
                        <Checkbox v-model="item.fire_purchase_on_pix" label="Disparar Purchase ao gerar PIX (não na aprovação)?" :disabled="disabled" />
                        <Checkbox v-model="item.fire_purchase_on_boleto" label="Disparar Purchase ao gerar boleto (não na aprovação)?" :disabled="disabled" />
                        <Checkbox v-model="item.disable_order_bump_events" label="Desativar eventos de order bumps?" :disabled="disabled" />
                    </div>
                </div>
                <p v-if="model.tiktok.entries.length === 0" class="rounded-xl border border-dashed border-[var(--ep-line-strong)] px-4 py-6 text-center text-[12.5px] text-[var(--ep-text-4)]">
                    Nenhum pixel. Clique em «Adicionar pixel» ou desative a integração.
                </p>
                    </div>
                </details>
            </template>
        </div>

        <div v-if="selectedPixelTab === 'google_ads'" class="panel-card space-y-5 p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-[14px] font-semibold tracking-[-0.01em] text-[var(--ep-text)]">Google Ads</h3>
                <div class="flex items-center gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        :disabled="disabled || !model.google_ads.enabled"
                        @click="model.google_ads.entries.push(newGoogleAdsEntry())"
                    >
                        <Plus class="h-3.5 w-3.5" stroke-width="2" /> Adicionar conversão
                    </Button>
                    <Toggle v-model="model.google_ads.enabled" :disabled="disabled" />
                </div>
            </div>
            <template v-if="model.google_ads.enabled">
                <PixelIntegrationPicker
                    platform="google_ads"
                    :block="model.google_ads"
                    :integrations="availableIntegrations?.google_ads || []"
                    :disabled="disabled"
                />
                <details v-if="!(model.google_ads.integration_ids?.length)" class="group overflow-hidden rounded-2xl border border-[var(--ep-line)]">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 text-[13px] font-medium text-[var(--ep-text-2)] transition-colors duration-150 hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)] [&::-webkit-details-marker]:hidden">
                        Configuração manual (avançado)
                        <ChevronDown class="h-4 w-4 text-[var(--ep-text-4)] transition-transform duration-200 group-open:rotate-180" stroke-width="1.75" aria-hidden="true" />
                    </summary>
                    <div class="space-y-3 border-t border-[var(--ep-line)] p-4">
                <div v-for="(item, idx) in model.google_ads.entries" :key="item.id" class="space-y-4 rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4">
                    <div class="flex items-center justify-between gap-2">
                        <span class="ep-chip tabular-nums">Conversão {{ idx + 1 }}</span>
                        <button
                            type="button"
                            class="ep-btn-ghost ep-btn-icon !h-8 !w-8 !rounded-[10px] hover:!bg-[var(--ep-neg-bg)] hover:!text-[var(--ep-neg)]"
                            :disabled="disabled"
                            @click="model.google_ads.entries.splice(idx, 1)"
                        >
                            <Trash2 class="h-4 w-4" stroke-width="1.75" />
                        </button>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="ep-label">Conversion ID</label>
                            <input v-model="item.conversion_id" type="text" placeholder="Ex: AW-123456789" class="ep-input font-mono !text-[13px] disabled:opacity-60" :disabled="disabled" />
                        </div>
                        <div>
                            <label class="ep-label">Conversion Label</label>
                            <input v-model="item.conversion_label" type="text" placeholder="Ex: AbCdEfGhIjKlMn" class="ep-input font-mono !text-[13px] disabled:opacity-60" :disabled="disabled" />
                        </div>
                    </div>
                    <div class="space-y-3 border-t border-[var(--ep-line)] pt-4">
                        <Checkbox v-model="item.fire_purchase_on_pix" label="Disparar Purchase ao gerar PIX (não na aprovação)?" :disabled="disabled" />
                        <Checkbox v-model="item.fire_purchase_on_boleto" label="Disparar Purchase ao gerar boleto (não na aprovação)?" :disabled="disabled" />
                        <Checkbox v-model="item.disable_order_bump_events" label="Desativar eventos de order bumps?" :disabled="disabled" />
                    </div>
                </div>
                <p v-if="model.google_ads.entries.length === 0" class="rounded-xl border border-dashed border-[var(--ep-line-strong)] px-4 py-6 text-center text-[12.5px] text-[var(--ep-text-4)]">
                    Nenhuma conversão. Clique em «Adicionar conversão» ou desative a integração.
                </p>
                    </div>
                </details>
            </template>
        </div>

        <div v-if="selectedPixelTab === 'google_analytics'" class="panel-card space-y-5 p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-[14px] font-semibold tracking-[-0.01em] text-[var(--ep-text)]">Google Analytics (GA4)</h3>
                <div class="flex items-center gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        :disabled="disabled || !model.google_analytics.enabled"
                        @click="model.google_analytics.entries.push(newGaEntry())"
                    >
                        <Plus class="h-3.5 w-3.5" stroke-width="2" /> Adicionar propriedade
                    </Button>
                    <Toggle v-model="model.google_analytics.enabled" :disabled="disabled" />
                </div>
            </div>
            <template v-if="model.google_analytics.enabled">
                <PixelIntegrationPicker
                    platform="google_analytics"
                    :block="model.google_analytics"
                    :integrations="availableIntegrations?.google_analytics || []"
                    :disabled="disabled"
                />
                <details v-if="!(model.google_analytics.integration_ids?.length)" class="group overflow-hidden rounded-2xl border border-[var(--ep-line)]">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 text-[13px] font-medium text-[var(--ep-text-2)] transition-colors duration-150 hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)] [&::-webkit-details-marker]:hidden">
                        Configuração manual (avançado)
                        <ChevronDown class="h-4 w-4 text-[var(--ep-text-4)] transition-transform duration-200 group-open:rotate-180" stroke-width="1.75" aria-hidden="true" />
                    </summary>
                    <div class="space-y-3 border-t border-[var(--ep-line)] p-4">
                <div
                    v-for="(item, idx) in model.google_analytics.entries"
                    :key="item.id"
                    class="space-y-4 rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4"
                >
                    <div class="flex items-center justify-between gap-2">
                        <span class="ep-chip tabular-nums">GA4 {{ idx + 1 }}</span>
                        <button
                            type="button"
                            class="ep-btn-ghost ep-btn-icon !h-8 !w-8 !rounded-[10px] hover:!bg-[var(--ep-neg-bg)] hover:!text-[var(--ep-neg)]"
                            :disabled="disabled"
                            @click="model.google_analytics.entries.splice(idx, 1)"
                        >
                            <Trash2 class="h-4 w-4" stroke-width="1.75" />
                        </button>
                    </div>
                    <div>
                        <label class="ep-label">Measurement ID</label>
                        <input v-model="item.measurement_id" type="text" placeholder="Ex: G-XXXXXXXXXX" class="ep-input font-mono !text-[13px] disabled:opacity-60" :disabled="disabled" />
                    </div>
                    <div class="space-y-3 border-t border-[var(--ep-line)] pt-4">
                        <Checkbox v-model="item.fire_purchase_on_pix" label="Disparar Purchase ao gerar PIX (não na aprovação)?" :disabled="disabled" />
                        <Checkbox v-model="item.fire_purchase_on_boleto" label="Disparar Purchase ao gerar boleto (não na aprovação)?" :disabled="disabled" />
                        <Checkbox v-model="item.disable_order_bump_events" label="Desativar eventos de order bumps?" :disabled="disabled" />
                    </div>
                </div>
                <p v-if="model.google_analytics.entries.length === 0" class="rounded-xl border border-dashed border-[var(--ep-line-strong)] px-4 py-6 text-center text-[12.5px] text-[var(--ep-text-4)]">
                    Nenhuma propriedade. Clique em «Adicionar propriedade» ou desative a integração.
                </p>
                    </div>
                </details>
            </template>
        </div>

        <div v-if="selectedPixelTab === 'gtm'" class="panel-card space-y-5 p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-[14px] font-semibold tracking-[-0.01em] text-[var(--ep-text)]">Google Tag Manager</h3>
                <Toggle v-model="model.gtm.enabled" :disabled="disabled" />
            </div>
            <template v-if="model.gtm?.enabled">
                <p class="rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] px-4 py-3 text-[12.5px] leading-[1.8] text-[var(--ep-text-3)] [&_code]:rounded-md [&_code]:bg-[var(--ep-active)] [&_code]:px-1.5 [&_code]:py-0.5 [&_code]:font-mono [&_code]:text-[11.5px] [&_code]:text-[var(--ep-text)]">
                    O container GTM carrega no checkout e recebe eventos no <code>dataLayer</code>:
                    <code>page_view</code>,
                    <code>begin_checkout</code>,
                    <code>pix_generated</code>,
                    <code>purchase</code>.
                    Configure tags no GTM para ouvir esses eventos.
                </p>
                <div>
                    <label class="ep-label">Container ID</label>
                    <input
                        v-model="model.gtm.container_id"
                        type="text"
                        placeholder="GTM-XXXXXXX"
                        class="ep-input font-mono !text-[13px] uppercase disabled:opacity-60"
                        :disabled="disabled"
                        @blur="model.gtm.container_id = (model.gtm.container_id || '').trim().toUpperCase()"
                    />
                </div>
            </template>
        </div>

        <div v-if="selectedPixelTab === 'custom_script'" class="panel-card space-y-5 p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-[14px] font-semibold tracking-[-0.01em] text-[var(--ep-text)]">Scripts personalizados</h3>
                <Button
                    v-if="!usesScriptIntegrations()"
                    type="button"
                    variant="outline"
                    size="sm"
                    :disabled="disabled"
                    @click="model.custom_script.push({ id: randomClientId(), name: '', script: '' })"
                >
                    <Plus class="h-3.5 w-3.5" stroke-width="2" /> Adicionar script manual
                </Button>
            </div>
            <div class="space-y-3 rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4">
                <p class="ep-section-title">Integrações cadastradas</p>
                <p v-if="scriptIntegrations().length === 0" class="text-[12.5px] text-[var(--ep-text-4)]">
                    <Link href="/integracoes" class="font-medium text-[var(--ep-accent)] underline-offset-4 hover:underline">Cadastrar em Integrações</Link>
                </p>
                <div v-else class="space-y-2">
                    <label
                        v-for="item in scriptIntegrations()"
                        :key="item.id"
                        class="flex cursor-pointer items-center gap-3 rounded-xl border px-3.5 py-3 transition-colors duration-150"
                        :class="isScriptIntegrationSelected(item.id)
                            ? 'border-[color-mix(in_oklab,var(--ep-accent)_45%,transparent)] bg-[color-mix(in_oklab,var(--ep-accent)_10%,transparent)]'
                            : 'border-[var(--ep-line)] bg-[var(--ep-input)] hover:border-[var(--ep-line-strong)]'"
                    >
                        <input
                            type="checkbox"
                            class="h-4 w-4 shrink-0 cursor-pointer accent-[var(--ep-accent)]"
                            :checked="isScriptIntegrationSelected(item.id)"
                            :disabled="disabled"
                            @change="toggleScriptIntegration(item.id, $event.target.checked)"
                        />
                        <span class="min-w-0 truncate text-[13px] font-medium text-[var(--ep-text)]">{{ item.name }}</span>
                    </label>
                </div>
            </div>
            <details v-if="!usesScriptIntegrations()" class="group overflow-hidden rounded-2xl border border-[var(--ep-line)]">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 text-[13px] font-medium text-[var(--ep-text-2)] transition-colors duration-150 hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)] [&::-webkit-details-marker]:hidden">
                    Scripts manuais (avançado)
                    <ChevronDown class="h-4 w-4 text-[var(--ep-text-4)] transition-transform duration-200 group-open:rotate-180" stroke-width="1.75" aria-hidden="true" />
                </summary>
                <div class="space-y-3 border-t border-[var(--ep-line)] p-4">
            <div v-for="(item, idx) in model.custom_script" :key="item.id" class="space-y-3 rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4">
                <div class="flex items-center gap-2">
                    <input v-model="item.name" type="text" placeholder="Nome (opcional)" class="ep-input flex-1 disabled:opacity-60" :disabled="disabled" />
                    <button
                        type="button"
                        class="ep-btn-ghost ep-btn-icon shrink-0 !rounded-[10px] hover:!bg-[var(--ep-neg-bg)] hover:!text-[var(--ep-neg)]"
                        :disabled="disabled"
                        @click="model.custom_script.splice(idx, 1)"
                    >
                        <Trash2 class="h-4 w-4" stroke-width="1.75" />
                    </button>
                </div>
                <textarea
                    v-model="item.script"
                    rows="4"
                    class="ep-input font-mono !text-[12.5px] disabled:opacity-60"
                    placeholder="Cole o código do pixel aqui (ex: &lt;script&gt;...&lt;/script&gt;)"
                    :disabled="disabled"
                />
            </div>
            <p v-if="model.custom_script.length === 0" class="rounded-xl border border-dashed border-[var(--ep-line-strong)] px-4 py-6 text-center text-[12.5px] text-[var(--ep-text-4)]">
                Nenhum script adicionado.
            </p>
                </div>
            </details>
        </div>
    </div>
</template>
