<script setup>
import { computed, ref, watch } from 'vue';
import axios from 'axios';
import Button from '@/components/ui/Button.vue';
import Toggle from '@/components/ui/Toggle.vue';
import { X, Plus, Pencil, Trash2, ArrowLeft, Loader2 } from 'lucide-vue-next';
import Checkbox from '@/components/ui/Checkbox.vue';
import { PIXEL_TABS, ENTRY_FLAGS } from '@/lib/conversionPixels';

const props = defineProps({
    open: { type: Boolean, default: false },
    conversion_pixel_integrations: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'saved']);

const selectedTab = ref('meta');
const editingIntegration = ref(null);
const isCreating = ref(false);
const saving = ref(false);
const deleting = ref(null);
const confirmingDeleteId = ref(null);
const errorMessage = ref(null);

const inputClass =
    'w-full rounded-xl border border-zinc-200 bg-white px-4 py-2.5 text-sm text-zinc-900 outline-none transition focus:border-[var(--color-primary)] focus:ring-2 focus:ring-[color-mix(in_srgb,var(--color-primary)_25%,transparent)] dark:border-zinc-700 dark:bg-zinc-800 dark:text-white';

const showingForm = computed(() => editingIntegration.value !== null || isCreating.value);

const integrationsForTab = computed(() =>
    (props.conversion_pixel_integrations || []).filter((i) => i.platform === selectedTab.value)
);

const integrationTabs = computed(() => PIXEL_TABS.filter((tab) => tab.id !== 'gtm'));

const form = ref({
    name: '',
    is_active: true,
    config: {},
    access_token: '',
    product_ids: [],
});

const supportsBehaviorFlags = computed(() =>
    ['meta', 'tiktok', 'google_ads', 'google_analytics'].includes(selectedTab.value)
);

function emptyConfig(platform) {
    const flags = { ...ENTRY_FLAGS };
    if (platform === 'meta' || platform === 'tiktok') return { pixel_id: '', ...flags };
    if (platform === 'google_ads') return { conversion_id: '', conversion_label: '', ...flags };
    if (platform === 'google_analytics') return { measurement_id: '', ...flags };
    if (platform === 'custom_script') return { script: '' };
    return {};
}

function resetForm() {
    editingIntegration.value = null;
    isCreating.value = false;
    confirmingDeleteId.value = null;
    form.value = {
        name: '',
        is_active: true,
        config: emptyConfig(selectedTab.value),
        access_token: '',
        product_ids: [],
    };
    errorMessage.value = null;
}

function isProductSelected(id) {
    return form.value.product_ids.map(String).includes(String(id));
}

function setProductSelected(id, checked) {
    const sid = String(id);
    if (checked) {
        if (!isProductSelected(sid)) {
            form.value.product_ids = [...form.value.product_ids, sid];
        }
    } else {
        form.value.product_ids = form.value.product_ids.filter((x) => String(x) !== sid);
    }
}

watch(
    () => props.open,
    (open) => {
        if (!open) resetForm();
    }
);

watch(selectedTab, () => {
    if (!showingForm.value) return;
    resetForm();
});

function startNew() {
    editingIntegration.value = null;
    isCreating.value = true;
    form.value = {
        name: '',
        is_active: true,
        config: emptyConfig(selectedTab.value),
        access_token: '',
        product_ids: [],
    };
    errorMessage.value = null;
}

function editIntegration(integration) {
    isCreating.value = false;
    editingIntegration.value = integration;
    const cfg = { ...emptyConfig(integration.platform), ...(integration.config || {}) };
    form.value = {
        name: integration.name,
        is_active: integration.is_active ?? true,
        config: cfg,
        access_token: '',
        product_ids: [...(integration.product_ids || [])],
    };
    errorMessage.value = null;
}

async function save() {
    saving.value = true;
    errorMessage.value = null;
    const platform = selectedTab.value;
    const payload = {
        platform,
        name: form.value.name,
        is_active: form.value.is_active,
        config: form.value.config,
        product_ids: form.value.product_ids,
    };
    if (platform === 'meta' || platform === 'tiktok') {
        if (isCreating.value || form.value.access_token) {
            payload.access_token = form.value.access_token;
        }
    }

    try {
        if (editingIntegration.value) {
            await axios.put(`/integracoes/conversion-pixels/${editingIntegration.value.id}`, payload);
        } else {
            await axios.post('/integracoes/conversion-pixels', payload);
        }
        resetForm();
        emit('saved');
    } catch (e) {
        errorMessage.value = e.response?.data?.message || 'Não foi possível salvar.';
    } finally {
        saving.value = false;
    }
}

async function destroyIntegration(integration) {
    deleting.value = integration.id;
    try {
        await axios.delete(`/integracoes/conversion-pixels/${integration.id}`);
        confirmingDeleteId.value = null;
        emit('saved');
    } catch {
        errorMessage.value = 'Não foi possível excluir.';
    } finally {
        deleting.value = null;
    }
}
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="open" class="fixed inset-0 z-[100000] flex justify-end">
                <div class="ep-scrim absolute inset-0" aria-hidden="true" @click="emit('close')" />
                <aside
                    class="ep-drawer relative flex h-full w-full max-w-xl flex-col"
                    role="dialog"
                    aria-labelledby="conversion-pixels-sidebar-title"
                >
                    <header class="flex items-center justify-between gap-3 border-b border-[var(--ep-line)] px-6 py-4">
                        <div class="flex min-w-0 items-center gap-2">
                            <button
                                v-if="showingForm"
                                type="button"
                                class="ep-btn-ghost ep-btn-icon -ml-2 shrink-0"
                                aria-label="Voltar"
                                @click="resetForm"
                            >
                                <ArrowLeft class="h-[18px] w-[18px]" :stroke-width="1.75" />
                            </button>
                            <div class="min-w-0">
                                <h2 id="conversion-pixels-sidebar-title" class="truncate text-[17px] font-semibold tracking-[-0.02em] text-[var(--ep-text)]">
                                    Pixels e rastreamento
                                </h2>
                                <p class="mt-0.5 text-[12.5px] text-[var(--ep-text-3)]">
                                    Cadastre pixels uma vez e reutilize nos produtos.
                                </p>
                            </div>
                        </div>
                        <button type="button" class="ep-btn-ghost ep-btn-icon shrink-0" aria-label="Fechar" @click="emit('close')">
                            <X class="h-[18px] w-[18px]" :stroke-width="1.75" />
                        </button>
                    </header>

                    <div v-if="!showingForm" class="border-b border-[var(--ep-line)] px-6 py-3">
                        <div class="ep-tabs max-w-full overflow-x-auto">
                            <button
                                v-for="tab in integrationTabs"
                                :key="tab.id"
                                type="button"
                                :class="[
                                    'ep-tab shrink-0 border',
                                    selectedTab === tab.id
                                        ? 'ep-tab--active'
                                        : 'border-transparent',
                                ]"
                                @click="selectedTab = tab.id"
                            >
                                <img :src="tab.image" :alt="tab.label" class="h-4 w-4 object-contain" />
                                {{ tab.label }}
                            </button>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto px-6 py-5">
                        <section
                            v-if="!showingForm"
                            class="panel-card mb-5 p-5 text-[12.5px] leading-relaxed text-[var(--ep-text-3)]"
                        >
                            <p class="ep-section-title mb-3">Tracking GTM e server-side</p>
                            <ul class="space-y-2">
                                <li class="flex gap-2.5">
                                    <span class="mt-[7px] h-1.5 w-1.5 shrink-0 rounded-full bg-[var(--ep-accent)]" aria-hidden="true" />
                                    <span>
                                        O checkout publica eventos no
                                        <code class="rounded-md border border-[var(--ep-line)] bg-[var(--ep-input)] px-1.5 py-0.5 font-mono text-[11.5px] text-[var(--ep-text-2)]">dataLayer</code>
                                        (<code class="rounded-md border border-[var(--ep-line)] bg-[var(--ep-input)] px-1.5 py-0.5 font-mono text-[11.5px] text-[var(--ep-text-2)]">page_view</code>,
                                        <code class="rounded-md border border-[var(--ep-line)] bg-[var(--ep-input)] px-1.5 py-0.5 font-mono text-[11.5px] text-[var(--ep-text-2)]">begin_checkout</code>,
                                        <code class="rounded-md border border-[var(--ep-line)] bg-[var(--ep-input)] px-1.5 py-0.5 font-mono text-[11.5px] text-[var(--ep-text-2)]">purchase</code>,
                                        <code class="rounded-md border border-[var(--ep-line)] bg-[var(--ep-input)] px-1.5 py-0.5 font-mono text-[11.5px] text-[var(--ep-text-2)]">pix_generated</code>).
                                    </span>
                                </li>
                                <li class="flex gap-2.5">
                                    <span class="mt-[7px] h-1.5 w-1.5 shrink-0 rounded-full bg-[var(--ep-text-4)]" aria-hidden="true" />
                                    <span>Configure tags no GTM ouvindo esses eventos. O container GTM é cadastrado em cada produto (Pixels → GTM).</span>
                                </li>
                                <li class="flex gap-2.5">
                                    <span class="mt-[7px] h-1.5 w-1.5 shrink-0 rounded-full bg-[var(--ep-text-4)]" aria-hidden="true" />
                                    <span>Meta CAPI exige <strong class="font-medium text-[var(--ep-text)]">access token</strong> na integração, não só Pixel ID.</span>
                                </li>
                                <li class="flex gap-2.5">
                                    <span class="mt-[7px] h-1.5 w-1.5 shrink-0 rounded-full bg-[var(--ep-text-4)]" aria-hidden="true" />
                                    <span>Utmify é integração separada dos pixels do checkout.</span>
                                </li>
                                <li class="flex gap-2.5">
                                    <span class="mt-[7px] h-1.5 w-1.5 shrink-0 rounded-full bg-[var(--ep-text-4)]" aria-hidden="true" />
                                    <span>Abandono é métrica interna do painel — não é enviado automaticamente ao GTM.</span>
                                </li>
                                <li class="flex gap-2.5">
                                    <span class="mt-[7px] h-1.5 w-1.5 shrink-0 rounded-full bg-[var(--ep-text-4)]" aria-hidden="true" />
                                    <span>Use <code class="rounded-md border border-[var(--ep-line)] bg-[var(--ep-input)] px-1.5 py-0.5 font-mono text-[11.5px] text-[var(--ep-text-2)]">?tracking_debug=1</code> no checkout para ver falhas de API no console.</span>
                                </li>
                            </ul>
                        </section>
                        <p
                            v-if="errorMessage"
                            class="mb-4 rounded-xl border border-[color-mix(in_oklab,var(--ep-neg)_30%,transparent)] bg-[var(--ep-neg-bg)] px-3.5 py-2.5 text-[13px] text-[var(--ep-neg)]"
                        >
                            {{ errorMessage }}
                        </p>

                        <template v-if="!showingForm">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <h3 class="ep-section-title flex items-center gap-2">
                                    Integrações
                                    <span class="ep-chip tabular-nums">{{ integrationsForTab.length }}</span>
                                </h3>
                                <Button type="button" size="sm" @click="startNew">
                                    <Plus class="h-4 w-4" :stroke-width="1.75" /> Novo
                                </Button>
                            </div>
                            <ul
                                v-if="integrationsForTab.length"
                                class="panel-card ep-data divide-y divide-[var(--ep-line)] overflow-hidden"
                            >
                                <li
                                    v-for="item in integrationsForTab"
                                    :key="item.id"
                                    class="flex items-center justify-between gap-3 px-5 py-4 transition-colors duration-150 hover:bg-[var(--ep-hover)]"
                                >
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="text-[14px] font-medium tracking-[-0.01em] text-[var(--ep-text)]">{{ item.name }}</p>
                                            <span class="ep-chip" :class="item.is_active ? 'ep-chip--pos' : 'ep-chip--warn'">
                                                <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                                                {{ item.is_active ? 'Ativo' : 'Inativo' }}
                                            </span>
                                        </div>
                                        <p class="mt-1 truncate font-mono text-[12px] text-[var(--ep-text-3)]">{{ item.summary }}</p>
                                    </div>
                                    <div class="flex shrink-0 gap-1">
                                        <button
                                            type="button"
                                            class="ep-btn-ghost ep-btn-icon !h-8 !w-8"
                                            aria-label="Editar"
                                            @click="editIntegration(item)"
                                        >
                                            <Pencil class="h-4 w-4" :stroke-width="1.75" />
                                        </button>
                                        <button
                                            type="button"
                                            class="ep-btn-ghost ep-btn-icon !h-8 !w-8 hover:!text-[var(--ep-neg)]"
                                            aria-label="Excluir"
                                            @click="confirmingDeleteId = item.id"
                                        >
                                            <Trash2 class="h-4 w-4" :stroke-width="1.75" />
                                        </button>
                                    </div>
                                </li>
                            </ul>
                            <div v-else class="panel-card ep-empty">
                                <p class="ep-empty__title">Nenhuma integração nesta plataforma</p>
                                <p class="ep-empty__text">Clique em «Novo» para cadastrar.</p>
                            </div>
                        </template>

                        <form v-else class="space-y-4" @submit.prevent="save">
                            <section class="panel-card space-y-4 p-5">
                                <h3 class="ep-section-title">Identificação</h3>
                                <div>
                                    <label class="ep-label">Nome</label>
                                    <input v-model="form.name" type="text" required class="ep-input" placeholder="Ex: Meta — Loja principal" />
                                </div>
                                <div class="flex items-center justify-between border-t border-[var(--ep-line)] pt-4">
                                    <span class="text-[13px] font-medium text-[var(--ep-text-2)]">Ativo</span>
                                    <Toggle v-model="form.is_active" />
                                </div>
                            </section>

                            <section class="panel-card space-y-4 p-5">
                                <h3 class="ep-section-title">Credenciais</h3>
                                <template v-if="selectedTab === 'meta' || selectedTab === 'tiktok'">
                                    <div>
                                        <label class="ep-label">Pixel ID</label>
                                        <input v-model="form.config.pixel_id" type="text" required class="ep-input font-mono !text-[13px]" />
                                    </div>
                                    <div>
                                        <label class="ep-label">
                                            Access Token (CAPI)
                                            <span v-if="editingIntegration?.has_access_token" class="font-normal text-[var(--ep-text-4)]">
                                                — deixe em branco para manter o atual
                                            </span>
                                        </label>
                                        <input
                                            v-model="form.access_token"
                                            type="password"
                                            :required="isCreating"
                                            class="ep-input"
                                            autocomplete="off"
                                        />
                                    </div>
                                </template>

                                <template v-else-if="selectedTab === 'google_ads'">
                                    <div>
                                        <label class="ep-label">Conversion ID</label>
                                        <input v-model="form.config.conversion_id" type="text" required class="ep-input font-mono !text-[13px]" />
                                    </div>
                                    <div>
                                        <label class="ep-label">Conversion Label</label>
                                        <input v-model="form.config.conversion_label" type="text" class="ep-input font-mono !text-[13px]" />
                                    </div>
                                </template>

                                <template v-else-if="selectedTab === 'google_analytics'">
                                    <div>
                                        <label class="ep-label">Measurement ID</label>
                                        <input v-model="form.config.measurement_id" type="text" required class="ep-input font-mono !text-[13px]" placeholder="G-XXXXXXXXXX" />
                                    </div>
                                </template>

                                <template v-else-if="selectedTab === 'custom_script'">
                                    <div>
                                        <label class="ep-label">Script</label>
                                        <textarea
                                            v-model="form.config.script"
                                            rows="8"
                                            required
                                            class="ep-input font-mono !text-[12.5px]"
                                            placeholder="&lt;script&gt;...&lt;/script&gt;"
                                        />
                                    </div>
                                </template>
                            </section>

                            <section v-if="supportsBehaviorFlags" class="panel-card space-y-3 p-5">
                                <p class="ep-section-title mb-1">Comportamento no checkout</p>
                                <Checkbox
                                    v-model="form.config.fire_purchase_on_pix"
                                    label="Disparar Purchase ao gerar PIX (não na aprovação)?"
                                />
                                <Checkbox
                                    v-model="form.config.fire_purchase_on_boleto"
                                    label="Disparar Purchase ao gerar boleto (não na aprovação)?"
                                />
                                <Checkbox
                                    v-model="form.config.disable_order_bump_events"
                                    label="Desativar eventos de order bumps?"
                                />
                            </section>

                            <section class="panel-card p-5">
                                <span class="ep-section-title block">
                                    Produtos atribuídos
                                </span>
                                <p class="mb-3 mt-1 text-[12px] leading-relaxed text-[var(--ep-text-4)]">
                                    Marque os produtos que devem usar este pixel. Sem seleção, o pixel não é aplicado em nenhum checkout.
                                </p>
                                <div
                                    v-if="products.length"
                                    class="max-h-56 space-y-0.5 overflow-y-auto rounded-xl border border-[var(--ep-input-border)] bg-[var(--ep-input)] p-1.5 text-left"
                                >
                                    <label
                                        v-for="p in products"
                                        :key="p.id"
                                        class="flex cursor-pointer items-start justify-start gap-2.5 rounded-[10px] px-2.5 py-2 transition-colors duration-150 hover:bg-[var(--ep-hover)]"
                                    >
                                        <span class="shrink-0 pt-0.5">
                                            <Checkbox
                                                :model-value="isProductSelected(p.id)"
                                                class="!w-auto shrink-0"
                                                @update:model-value="(v) => setProductSelected(p.id, v)"
                                            />
                                        </span>
                                        <span class="min-w-0 flex-1 text-left text-[13px] leading-snug text-[var(--ep-text)]">
                                            {{ p.name }}
                                        </span>
                                    </label>
                                </div>
                                <p v-else class="text-[12.5px] text-[var(--ep-text-4)]">Nenhum produto cadastrado.</p>
                            </section>

                            <Button type="submit" class="w-full" :disabled="saving">
                                <Loader2 v-if="saving" class="h-4 w-4 animate-spin" :stroke-width="1.75" />
                                {{ editingIntegration ? 'Salvar alterações' : 'Criar integração' }}
                            </Button>
                        </form>
                    </div>

                    <div
                        v-if="confirmingDeleteId"
                        class="ep-scrim absolute inset-0 z-10 flex items-center justify-center p-4"
                    >
                        <div class="ep-modal w-full max-w-sm p-6">
                            <h3 class="text-[15px] font-semibold tracking-[-0.015em] text-[var(--ep-text)]">Excluir integração</h3>
                            <p class="mt-2 text-[13px] leading-relaxed text-[var(--ep-text-3)]">Excluir esta integração? Produtos que a usam deixarão de disparar este pixel.</p>
                            <div class="mt-5 flex gap-2">
                                <Button variant="outline" class="flex-1" @click="confirmingDeleteId = null">Cancelar</Button>
                                <Button
                                    variant="destructive"
                                    class="flex-1"
                                    :disabled="deleting === confirmingDeleteId"
                                    @click="destroyIntegration(integrationsForTab.find((i) => i.id === confirmingDeleteId))"
                                >
                                    Excluir
                                </Button>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </Transition>
    </Teleport>
</template>
