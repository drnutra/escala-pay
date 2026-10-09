<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import Button from '@/components/ui/Button.vue';
import HorizontalScrollTabs from '@/components/ui/HorizontalScrollTabs.vue';
import AppCard from '@/components/integrations/AppCard.vue';
import ConversionPixelsAppCard from '@/components/integrations/ConversionPixelsAppCard.vue';
import SpedySidebar from '@/components/integrations/SpedySidebar.vue';
import UtmifySidebar from '@/components/integrations/UtmifySidebar.vue';
import WebhookSidebar from '@/components/integrations/WebhookSidebar.vue';
import ExternalCheckoutSidebar from '@/components/integrations/ExternalCheckoutSidebar.vue';
import CademiSidebar from '@/components/integrations/CademiSidebar.vue';
import IntegraXSidebar from '@/components/integrations/IntegraXSidebar.vue';
import PixelXSidebar from '@/components/integrations/PixelXSidebar.vue';
import ConversionPixelsSidebar from '@/components/integrations/ConversionPixelsSidebar.vue';
import GatewayCard from '@/components/settings/GatewayCard.vue';
import GatewayConfigSidebar from '@/components/settings/GatewayConfigSidebar.vue';
import { CreditCard, Zap, X } from 'lucide-vue-next';

defineOptions({ layout: LayoutInfoprodutor });

const TABS = [
    { id: 'apps', label: 'Apps', icon: Zap },
    { id: 'gateways', label: 'Gateways', icon: CreditCard },
];

const APPS_BASE = [
    {
        id: 'webhook',
        name: 'Webhook',
        description: 'Envie eventos da plataforma para a URL configurada. Painel com métricas, logs e documentação de payloads por evento.',
        image: 'images/integrations/webhook.png',
    },
    {
        id: 'external_checkout',
        name: 'Checkout externo',
        description: 'Receba vendas aprovadas de checkouts externos (Hotmart, Kiwify, etc.) via POST. Cria pedido, aluno e libera área de membros.',
        image: 'images/integrations/external.png',
    },
    {
        id: 'utmify',
        name: 'UTMfy',
        description: 'Rastreie vendas e envie eventos para a UTMfy. Requer apenas a chave de API.',
        image: 'images/integrations/utmify.jpg',
    },
    {
        id: 'spedy',
        name: 'Spedy',
        description: 'Emissão automática de notas fiscais. Envie vendas para a Spedy e emita NF-e/NFS-e.',
        image: 'images/integrations/spedy.png',
    },
    {
        id: 'cademi',
        name: 'Cademí',
        description: 'Área de membros externa. Após a compra, sincronize o aluno e conceda acesso na Cademí.',
        image: 'images/integrations/cademi.png',
    },
    {
        id: 'integrax',
        name: 'IntegraX',
        description: 'Envio de SMS automático: acesso pós-compra, PIX gerado e recuperação de carrinho. Configure o token e os textos por produto.',
        image: 'images/integrations/integrax.png',
    },
    {
        id: 'pixel_x',
        name: 'Pixel X',
        description: 'Rastreamento de conversão. Envie os 10 eventos mapeados para a Pixel X com token e payload proprietário.',
        image: 'images/integrations/pixel-x.jpg',
    },
    {
        id: 'conversion_pixels',
        name: 'Pixels e rastreamento',
        description: 'Meta Ads, TikTok, Google Ads, Google Analytics e scripts. Reutilize nos produtos sem cadastrar de novo.',
    },
];

const props = defineProps({
    gateways: { type: Array, default: () => [] },
    gateway_order: {
        type: Object,
        default: () => ({ pix: [], card: [], boleto: [] }),
    },
    webhooks: { type: Array, default: () => [] },
    webhook_events: { type: Object, default: () => ({}) },
    webhook_event_catalog: {
        type: Object,
        default: () => ({ groups: [], events: [] }),
    },
    utmify_integrations: { type: Array, default: () => [] },
    spedy_integrations: { type: Array, default: () => [] },
    cademi_integrations: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    api_applications: { type: Array, default: () => [] },
    plugin_apps: { type: Array, default: () => [] },
    conversion_pixel_integrations: { type: Array, default: () => [] },
    external_checkout_endpoints: { type: Array, default: () => [] },
    integrax_connection: {
        type: Object,
        default: () => ({
            configured: false,
            is_active: false,
            has_token: false,
            api_token: '',
            last_tested_at: null,
            last_error: null,
        }),
    },
    pixel_x_integrations: { type: Array, default: () => [] },
});

import { usePluginComponentResolver } from '@/composables/usePluginComponentResolver';

const pluginPagesGlob = import.meta.glob('../../PluginPages/**/*.vue');
const pageIntegrations = usePage();
const { resolve: resolvePluginComponent } = usePluginComponentResolver(
    computed(() => pageIntegrations.props.plugin_ui),
    pluginPagesGlob,
);

const APPS = computed(() =>
    [
        ...APPS_BASE.map((app) => {
        if (app.id === 'utmify') {
            const hasActive = (props.utmify_integrations || []).some(
                (i) => i.configured && i.is_active
            );
            return {
                ...app,
                status: hasActive ? 'active' : undefined,
            };
        }
        if (app.id === 'spedy') {
            const hasActive = (props.spedy_integrations || []).some(
                (i) => i.configured && i.is_active
            );
            return {
                ...app,
                status: hasActive ? 'active' : undefined,
            };
        }
        if (app.id === 'cademi') {
            const hasActive = (props.cademi_integrations || []).some(
                (i) => i.configured && i.is_active
            );
            return {
                ...app,
                status: hasActive ? 'active' : undefined,
            };
        }
        if (app.id === 'conversion_pixels') {
            const hasActive = (props.conversion_pixel_integrations || []).some(
                (i) => i.configured && i.is_active
            );
            return {
                ...app,
                status: hasActive ? 'active' : undefined,
            };
        }
        if (app.id === 'external_checkout') {
            const hasActive = (props.external_checkout_endpoints || []).some((e) => e.is_active);
            return {
                ...app,
                status: hasActive ? 'active' : undefined,
            };
        }
        if (app.id === 'integrax') {
            const conn = props.integrax_connection || {};
            const hasActive = conn.configured && conn.is_active;
            return {
                ...app,
                status: hasActive ? 'active' : undefined,
            };
        }
        if (app.id === 'pixel_x') {
            const hasActive = (props.pixel_x_integrations || []).some(
                (i) => i.configured && i.is_active
            );
            return {
                ...app,
                status: hasActive ? 'active' : undefined,
            };
        }
        return app;
    }),
        ...((props.plugin_apps || []).map((p) => ({
            id: `plugin:${p.id}`,
            plugin: true,
            plugin_slot: p,
            plugin_component: p.component,
            name: p.name,
            description: p.description,
            image: p.image,
            status: p.status,
        }))),
    ]
);

const gatewaySidebarOpen = ref(false);
const selectedGatewaySlug = ref(null);
const webhookSidebarOpen = ref(false);
const utmifySidebarOpen = ref(false);
const spedySidebarOpen = ref(false);
const cademiSidebarOpen = ref(false);
const conversionPixelsSidebarOpen = ref(false);
const externalCheckoutSidebarOpen = ref(false);
const integraxSidebarOpen = ref(false);
const pluginSidebarOpen = ref(false);
const selectedPluginSlot = ref(null);
const selectedPluginAppName = ref(null);

function openGatewaySidebar(slug) {
    selectedGatewaySlug.value = slug;
    gatewaySidebarOpen.value = true;
}

function closeGatewaySidebar() {
    gatewaySidebarOpen.value = false;
    selectedGatewaySlug.value = null;
}

function openWebhookSidebar() {
    webhookSidebarOpen.value = true;
}

function closeWebhookSidebar() {
    webhookSidebarOpen.value = false;
}

function openUtmifySidebar() {
    utmifySidebarOpen.value = true;
}

function closeUtmifySidebar() {
    utmifySidebarOpen.value = false;
}

function openSpedySidebar() {
    spedySidebarOpen.value = true;
}

function closeSpedySidebar() {
    spedySidebarOpen.value = false;
}

function openCademiSidebar() {
    cademiSidebarOpen.value = true;
}

function closeCademiSidebar() {
    cademiSidebarOpen.value = false;
}

function openExternalCheckoutSidebar() {
    externalCheckoutSidebarOpen.value = true;
}

function closeExternalCheckoutSidebar() {
    externalCheckoutSidebarOpen.value = false;
}

function openIntegraxSidebar() {
    integraxSidebarOpen.value = true;
}

function closeIntegraxSidebar() {
    integraxSidebarOpen.value = false;
}

function onIntegraxSaved() {
    router.reload({ only: ['integrax_connection'] });
}

function onExternalCheckoutSaved() {
    router.reload({ only: ['external_checkout_endpoints', 'products'] });
}

function openConversionPixelsSidebar() {
    conversionPixelsSidebarOpen.value = true;
}

function closeConversionPixelsSidebar() {
    conversionPixelsSidebarOpen.value = false;
}

const pixelXSidebarOpen = ref(false);
function openPixelXSidebar() {
    pixelXSidebarOpen.value = true;
}
function closePixelXSidebar() {
    pixelXSidebarOpen.value = false;
}
function onPixelXSaved() {
    router.reload({ only: ['pixel_x_integrations', 'products'] });
}

function openPluginSidebar(app) {
    selectedPluginSlot.value = app?.plugin_slot || (app?.plugin_component ? { component: app.plugin_component, ui_mode: 'legacy' } : null);
    selectedPluginAppName.value = app?.name || 'Integração';
    pluginSidebarOpen.value = true;
}

function closePluginSidebar() {
    pluginSidebarOpen.value = false;
    selectedPluginSlot.value = null;
    selectedPluginAppName.value = null;
}

function onGatewaySaved() {
    router.reload({ only: ['gateways', 'gateway_order'] });
}

function onWebhookSaved() {
    router.reload();
}

function onUtmifySaved() {
    // Recarrega só a lista de integrações para não perder o valor do input da chave no sidebar
    router.reload({ only: ['utmify_integrations', 'products', 'api_applications'] });
}

function onSpedySaved() {
    router.reload({ only: ['spedy_integrations', 'products'] });
}

function onCademiSaved() {
    router.reload({ only: ['cademi_integrations', 'products'] });
}

function onConversionPixelsSaved() {
    router.reload({ only: ['conversion_pixel_integrations', 'products'] });
}

function onAppClick(app) {
    if (app.id === 'webhook') {
        openWebhookSidebar();
    } else if (app.id === 'external_checkout') {
        openExternalCheckoutSidebar();
    } else if (app.id === 'utmify') {
        openUtmifySidebar();
    } else if (app.id === 'spedy') {
        openSpedySidebar();
    } else if (app.id === 'cademi') {
        openCademiSidebar();
    } else if (app.id === 'conversion_pixels') {
        openConversionPixelsSidebar();
    } else if (app.id === 'integrax') {
        openIntegraxSidebar();
    } else if (app.id === 'pixel_x') {
        openPixelXSidebar();
    } else if (app.plugin) {
        openPluginSidebar(app);
    }
}

const page = usePage();
const currentTab = computed(() => {
    const url = page.url;
    const idx = url.indexOf('?');
    const search = idx !== -1 ? url.slice(idx) : '';
    const q = new URLSearchParams(search);
    const t = q.get('tab');
    return TABS.some((tab) => tab.id === t) ? t : 'apps';
});

function setTab(tabId) {
    router.get('/integracoes', { tab: tabId }, { preserveState: true });
}

function parseIntegrationsSearch() {
    const url = page.url;
    const idx = url.indexOf('?');
    return idx !== -1 ? new URLSearchParams(url.slice(idx)) : new URLSearchParams();
}

function syncGatewayFromQuery() {
    const q = parseIntegrationsSearch();
    const gateway = q.get('gateway');
    if (!gateway) {
        return;
    }

    const tab = q.get('tab');
    if (tab !== 'gateways') {
        router.get(
            '/integracoes',
            { tab: 'gateways', gateway },
            { preserveState: true, replace: true },
        );
        return;
    }

    if (selectedGatewaySlug.value !== gateway || !gatewaySidebarOpen.value) {
        openGatewaySidebar(gateway);
    }
}

onMounted(() => syncGatewayFromQuery());

watch(() => page.url, () => syncGatewayFromQuery());
</script>

<template>
    <div class="space-y-6">
        <!-- Cabeçalho da tela -->
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="ep-chip tabular-nums">
                        <span class="h-1.5 w-1.5 rounded-full bg-[var(--ep-pos)]" aria-hidden="true" />
                        {{ APPS.filter((a) => a.status === 'active').length }} de {{ APPS.length }} conectados
                    </span>
                </div>
                <p class="mt-2 text-[13px] text-[var(--ep-text-3)]">
                    Configure webhooks, gateways e apps para conectar sua operação.
                </p>
            </div>
            <HorizontalScrollTabs aria-label="Abas de integrações" nav-class="ep-tabs" wrapper-class="sm:!w-auto">
                <button
                    v-for="tab in TABS"
                    :key="tab.id"
                    type="button"
                    :class="['ep-tab', currentTab === tab.id ? 'ep-tab--active' : 'border border-transparent']"
                    @click="setTab(tab.id)"
                >
                    <component :is="tab.icon" class="h-4 w-4 shrink-0" :stroke-width="1.75" aria-hidden="true" />
                    {{ tab.label }}
                </button>
            </HorizontalScrollTabs>
        </header>

        <!-- Aba Apps -->
        <template v-if="currentTab === 'apps'">
            <section aria-labelledby="int-apps-title">
                <div class="mb-3 flex items-baseline justify-between gap-3 px-1">
                    <h2 id="int-apps-title" class="ep-section-title">
                        Galeria de apps
                        <span class="ml-1 text-[12px] font-normal tabular-nums text-[var(--ep-text-4)]">{{ APPS.length }} disponíveis</span>
                    </h2>
                    <span class="hidden text-[12px] text-[var(--ep-text-4)] sm:inline">Clique em um app para configurar</span>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <template v-for="app in APPS" :key="app.id">
                        <ConversionPixelsAppCard
                            v-if="app.id === 'conversion_pixels'"
                            :app="app"
                            @click="onAppClick(app)"
                        />
                        <AppCard
                            v-else
                            :app="app"
                            @click="onAppClick(app)"
                        />
                    </template>
                </div>
            </section>
        </template>

        <!-- Aba Gateways -->
        <template v-if="currentTab === 'gateways'">
            <section class="space-y-6">
                <div class="panel-card p-6" aria-labelledby="int-gateways-title">
                    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 id="int-gateways-title" class="ep-section-title">
                                Gateways de pagamento
                                <span class="ml-1 text-[12px] font-normal tabular-nums text-[var(--ep-text-4)]">{{ gateways.length }} disponíveis</span>
                            </h2>
                            <p class="mt-1 max-w-2xl text-[12.5px] text-[var(--ep-text-3)]">
                                Configure os gateways que deseja usar no checkout. Clique em um card para configurar credenciais e testar a conexão.
                            </p>
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <GatewayCard
                            v-for="g in gateways"
                            :key="g.slug"
                            :gateway="g"
                            @click="openGatewaySidebar(g.slug)"
                        />
                    </div>
                    <div v-if="gateways.length === 0" class="ep-empty rounded-[14px] border border-dashed border-[var(--ep-line-strong)] bg-[var(--ep-card-2)]">
                        <CreditCard class="mb-1 h-5 w-5 text-[var(--ep-text-4)]" :stroke-width="1.75" aria-hidden="true" />
                        <p class="ep-empty__title">Nenhum gateway disponível.</p>
                        <p class="ep-empty__text">Instale ou ative um plugin de gateway em Plugins para ele aparecer aqui.</p>
                    </div>
                </div>
            </section>
        </template>

        <GatewayConfigSidebar
            :open="gatewaySidebarOpen"
            :gateway-slug="selectedGatewaySlug"
            @close="closeGatewaySidebar"
            @saved="onGatewaySaved"
        />
        <WebhookSidebar
            :open="webhookSidebarOpen"
            :webhooks="webhooks"
            :webhook-events="webhook_events"
            :webhook-event-catalog="webhook_event_catalog"
            :products="products"
            @close="closeWebhookSidebar"
            @saved="onWebhookSaved"
        />
        <UtmifySidebar
            :open="utmifySidebarOpen"
            :utmify_integrations="utmify_integrations"
            :products="products"
            :api_applications="api_applications"
            @close="closeUtmifySidebar"
            @saved="onUtmifySaved"
        />
        <SpedySidebar
            :open="spedySidebarOpen"
            :spedy_integrations="spedy_integrations"
            :products="products"
            @close="closeSpedySidebar"
            @saved="onSpedySaved"
        />
        <CademiSidebar
            :open="cademiSidebarOpen"
            :cademi_integrations="cademi_integrations"
            :products="products"
            @close="closeCademiSidebar"
            @saved="onCademiSaved"
        />
        <ConversionPixelsSidebar
            :open="conversionPixelsSidebarOpen"
            :conversion_pixel_integrations="conversion_pixel_integrations"
            :products="products"
            @close="closeConversionPixelsSidebar"
            @saved="onConversionPixelsSaved"
        />
        <ExternalCheckoutSidebar
            :open="externalCheckoutSidebarOpen"
            :endpoints="external_checkout_endpoints"
            @close="closeExternalCheckoutSidebar"
            @saved="onExternalCheckoutSaved"
        />
        <IntegraXSidebar
            :open="integraxSidebarOpen"
            :integrax_connection="integrax_connection"
            @close="closeIntegraxSidebar"
            @saved="onIntegraxSaved"
        />
        <PixelXSidebar
            :open="pixelXSidebarOpen"
            :pixel_x_integrations="pixel_x_integrations"
            :products="products"
            @close="closePixelXSidebar"
            @saved="onPixelXSaved"
        />
        <!-- Plugin sidebars (ex.: AutoZap) -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-opacity duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="pluginSidebarOpen"
                    class="ep-scrim fixed inset-0 z-[100000]"
                    aria-hidden="true"
                    @click="closePluginSidebar"
                />
            </Transition>
            <Transition
                enter-active-class="transition-transform duration-300 ease-[cubic-bezier(0.23,1,0.32,1)]"
                enter-from-class="translate-x-full"
                enter-to-class="translate-x-0"
                leave-active-class="transition-transform duration-200 ease-in"
                leave-from-class="translate-x-0"
                leave-to-class="translate-x-full"
            >
                <aside
                    v-if="pluginSidebarOpen"
                    class="ep-drawer fixed right-0 top-0 z-[100001] flex h-full w-full max-w-md flex-col"
                    role="dialog"
                    aria-label="Configuração da integração"
                    @click.stop
                >
                    <div class="flex shrink-0 items-center justify-between gap-3 border-b border-[var(--ep-line)] px-6 py-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[13px] border border-[var(--ep-glass-border)] bg-[var(--ep-glass-strong)] text-[var(--ep-accent)] shadow-[var(--ep-glass-highlight),0_8px_22px_-12px_var(--ep-glow)]">
                                <Zap class="h-[18px] w-[18px]" :stroke-width="1.75" aria-hidden="true" />
                            </span>
                            <div class="min-w-0">
                                <div class="truncate text-[16px] font-semibold tracking-[-0.02em] text-[var(--ep-text)]">
                                    {{ selectedPluginAppName || 'Integração' }}
                                </div>
                                <p class="text-[12px] text-[var(--ep-text-3)]">Configuração do plugin</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="ep-btn-ghost ep-btn-icon shrink-0"
                            aria-label="Fechar"
                            @click="closePluginSidebar"
                        >
                            <X class="h-[18px] w-[18px]" :stroke-width="1.75" aria-hidden="true" />
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto px-6 py-5">
                        <component
                            v-if="selectedPluginSlot && resolvePluginComponent(selectedPluginSlot)"
                            :is="resolvePluginComponent(selectedPluginSlot)"
                            @saved="router.reload()"
                            @close="closePluginSidebar"
                        />
                        <div v-else class="ep-empty rounded-[14px] border border-dashed border-[var(--ep-line-strong)] bg-[var(--ep-card-2)]">
                            <p class="ep-empty__title">Não foi possível carregar o painel desta integração do plugin.</p>
                            <p class="ep-empty__text">Verifique se o plugin está ativo em Plugins e recarregue a página.</p>
                        </div>
                    </div>
                </aside>
            </Transition>
        </Teleport>
    </div>
</template>
