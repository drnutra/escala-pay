<script setup>
import { ref, computed, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import Button from '@/components/ui/Button.vue';
import HorizontalScrollTabs from '@/components/ui/HorizontalScrollTabs.vue';
import { Puzzle, Power, PowerOff, ExternalLink, CreditCard, Package, Download, Trash2, FolderUp, ArrowUpRight } from 'lucide-vue-next';

defineOptions({ layout: LayoutInfoprodutor });

const TABS = [
    { id: 'installed', label: 'Instalados', icon: Puzzle },
    { id: 'store', label: 'Loja de plugins', icon: Package },
];

const CATEGORY_LABELS = {
    gateway: 'Gateway',
    integration: 'Integração',
    marketing: 'Marketing',
    outros: 'Outros',
    other: 'Outros',
};

const props = defineProps({
    plugins: { type: Array, default: () => [] },
    /** Lista de slugs instalados (registrados no servidor). */
    installedPluginSlugs: { type: Array, default: () => [] },
    /** Lista de nomes dos plugins instalados (para comparar com a loja por nome). */
    installedPluginNames: { type: Array, default: () => [] },
    storePlugins: { type: Array, default: () => [] },
    pluginStore: { type: Object, default: () => ({ store_url: '', submit_url: '' }) },
    pluginsPath: { type: String, default: '' },
    /** Pasta persistente para instalações (ZIP/loja). */
    plugins_install_path: { type: String, default: '' },
    /** Pasta versionada com o código (ex.: example-gateway). */
    plugins_bundled_path: { type: String, default: '' },
});

const page = usePage();
/** Normaliza slug para comparação (loja pode usar _ e pasta pode usar -). */
function normalizeSlug(s) {
    if (s == null || typeof s !== 'string') return '';
    return s.toLowerCase().replace(/_/g, '-').replace(/[^a-z0-9-]/g, '');
}
/** Normaliza nome para comparação (minúsculas, sem acentos, espaços colapsados). */
function normalizeName(s) {
    if (s == null || typeof s !== 'string') return '';
    const t = s.toLowerCase().trim().replace(/\s+/g, ' ');
    return t.normalize('NFD').replace(/\p{Diacritic}/gu, '');
}
/** Sets de slugs e nomes instalados (servidor), normalizados. */
const installedSlugsSet = computed(() => {
    const slugs = Array.isArray(props.installedPluginSlugs) ? props.installedPluginSlugs : [];
    return new Set(slugs.map((slug) => normalizeSlug(slug)));
});
const installedNamesSet = computed(() => {
    const names = Array.isArray(props.installedPluginNames) ? props.installedPluginNames : [];
    return new Set(names.map((name) => normalizeName(name)));
});
/** Verifica se o plugin da loja está instalado — por slug ou por nome. */
function isStorePluginInstalled(storePlugin) {
    const slug = storePlugin?.slug ?? storePlugin;
    const name = typeof storePlugin === 'object' ? storePlugin?.name : undefined;
    if (slug && installedSlugsSet.value.has(normalizeSlug(slug))) return true;
    if (name && installedNamesSet.value.has(normalizeName(name))) return true;
    return false;
}
const currentTab = computed(() => {
    const url = page.url;
    const idx = url.indexOf('?');
    const search = idx !== -1 ? url.slice(idx) : '';
    const q = new URLSearchParams(search);
    const t = q.get('tab');
    return TABS.some((tab) => tab.id === t) ? t : 'installed';
});

const storeDetail = ref(null);
const installingSlug = ref(null);
const storeBannerFailed = ref({});
const storePluginsList = ref([]);
const storePluginsLoading = ref(false);
const storePluginsError = ref(null);
const lastInstallDownloadUrl = ref(null);
const lastInstallSlug = ref(null);
const showZipUnavailableModal = ref(false);
const showManualInstallModal = ref(false);
const manualInstallFileInput = ref(null);
const manualInstallError = ref('');
const manualInstallProcessing = ref(false);
const downloadFallbackLoading = ref(false);
const downloadFallbackError = ref('');

/** Marketplace oficial (página pública). API fica em api_url. */
const PLUGIN_STORE_URL = computed(() =>
    (props.pluginStore?.store_url || 'https://getfy.org/plugins').replace(/\/$/, '')
);
const PLUGIN_STORE_API_URL = computed(() =>
    (props.pluginStore?.api_url || 'https://getfy.org').replace(/\/$/, '')
);

function goToPluginStore() {
    if (typeof window !== 'undefined') {
        window.open(PLUGIN_STORE_URL.value, '_blank', 'noopener,noreferrer');
    }
}

function setTab(tabId) {
    if (tabId === 'store') {
        goToPluginStore();
        return;
    }
    router.get('/gerenciar-plugins', { tab: tabId }, { preserveState: true });
}

watch(
    currentTab,
    (tab) => {
        if (tab !== 'store') return;
        goToPluginStore();
        router.replace('/gerenciar-plugins', { preserveState: true });
    },
    { immediate: true }
);

async function loadStorePlugins() {
    const baseUrl = PLUGIN_STORE_API_URL.value;
    storePluginsError.value = null;
    storePluginsLoading.value = true;
    try {
        // Busca direto na API da loja (navegador → getfy.org) para evitar requisição servidor→servidor que caía no vhost errado
        const apiUrl = baseUrl + '/api/v1/plugins';
        const r = await fetch(apiUrl);
        const json = await r.json();
        storePluginsList.value = Array.isArray(json?.data) ? json.data : [];
        if (json?.error) storePluginsError.value = json.error;
        if (!r.ok) storePluginsError.value = json?.error || `Loja retornou HTTP ${r.status}.`;
    } catch (e) {
        storePluginsList.value = [];
        storePluginsError.value = 'Não foi possível carregar a loja. Tente novamente mais tarde.';
    } finally {
        storePluginsLoading.value = false;
    }
}

function categoryLabel(category) {
    return CATEGORY_LABELS[category] ?? category ?? 'Outros';
}

async function openStoreDetail(plugin) {
    storeDetail.value = { ...plugin };
    const baseUrl = PLUGIN_STORE_API_URL.value;
    try {
        const apiUrl = baseUrl + '/api/v1/plugins/' + encodeURIComponent(plugin.slug);
        const r = await fetch(apiUrl);
        if (r.ok) {
            const json = await r.json();
            if (json?.data) {
                storeDetail.value = { ...storeDetail.value, ...json.data };
            }
        }
    } catch (_) {}
}

function closeStoreDetail() {
    storeDetail.value = null;
}

function setStoreBannerFailed(slug) {
    storeBannerFailed.value = { ...storeBannerFailed.value, [slug]: true };
}

const returnUrl = computed(() => {
    const base = typeof window !== 'undefined' ? window.location.origin : '';
    return base + '/gerenciar-plugins?tab=installed&install=';
});

function checkoutUrl(slug) {
    const base = PLUGIN_STORE_API_URL.value;
    const targetCheckout = '/c/' + slug + '?return_url=' + encodeURIComponent(returnUrl.value + slug);
    return `${base}/login?next=${encodeURIComponent(targetCheckout)}`;
}

async function installStorePlugin(slug, purchaseToken = null) {
    const baseUrl = PLUGIN_STORE_API_URL.value;
    installingSlug.value = slug;
    storePluginsError.value = null;
    try {
        // 1) Obter link de download no navegador (evita requisição servidor→loja)
        const apiUrl = baseUrl + '/api/v1/plugins/' + encodeURIComponent(slug) + '/request-download';
        const body = purchaseToken ? { purchase_token: purchaseToken } : {};
        const r = await fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify(body),
            credentials: 'omit',
        });
        const json = await r.json().catch(() => ({}));
        const downloadUrl = json?.download_url;
        if (!r.ok || !downloadUrl) {
            storePluginsError.value = json?.message || json?.error || `Loja retornou HTTP ${r.status}.`;
            installingSlug.value = null;
            return;
        }
        // 2) Baixar o ZIP no navegador (evita que o servidor precise acessar a loja)
        const zipRes = await fetch(downloadUrl, { credentials: 'omit' });
        if (!zipRes.ok) {
            storePluginsError.value = 'Não foi possível baixar o arquivo do plugin.';
            installingSlug.value = null;
            return;
        }
        const blob = await zipRes.blob();
        const file = new File([blob], slug + '.zip', { type: 'application/zip' });
        lastInstallDownloadUrl.value = downloadUrl;
        lastInstallSlug.value = slug;
        // 3) Enviar via Inertia (CSRF e redirect tratados automaticamente)
        router.post(`/gerenciar-plugins/install/${slug}`, { plugin_zip: file }, {
            preserveScroll: true,
            forceFormData: true,
            onFinish: () => { installingSlug.value = null; },
            onError: (errors) => {
                storePluginsError.value = typeof errors === 'object' && errors?.plugin_zip
                    ? (Array.isArray(errors.plugin_zip) ? errors.plugin_zip[0] : errors.plugin_zip)
                    : 'Falha ao instalar. Tente novamente.';
            },
        });
    } catch (e) {
        storePluginsError.value = 'Não foi possível obter ou instalar o plugin. Verifique a conexão.';
        installingSlug.value = null;
    }
}

function enablePlugin(slug) {
    router.post(`/integracoes/plugins/${slug}/enable`, {}, { preserveScroll: true });
}

function disablePlugin(slug) {
    router.post(`/integracoes/plugins/${slug}/disable`, {}, { preserveScroll: true });
}

const registeringSlug = ref(null);
function registerPlugin(slug) {
    registeringSlug.value = slug;
    router.post(`/gerenciar-plugins/register-plugin/${slug}`, {}, {
        preserveScroll: true,
        onFinish: () => { registeringSlug.value = null; },
    });
}

const uninstallingSlug = ref(null);
function uninstallPlugin(plugin) {
    if (!window.confirm(`Excluir o plugin "${plugin.name}"? A pasta do plugin será removida e não será possível desfazer.`)) return;
    uninstallingSlug.value = plugin.slug;
    router.delete(`/integracoes/plugins/${plugin.slug}`, {
        preserveScroll: true,
        onFinish: () => { uninstallingSlug.value = null; },
    });
}

function goToGateways() {
    router.visit('/integracoes?tab=gateways');
}

const urlPurchaseToken = ref(null);
const urlInstallSlug = ref(null);
watch(() => page.url, () => {
    if (typeof window === 'undefined') return;
    const q = new URLSearchParams(window.location.search);
    urlPurchaseToken.value = q.get('purchase_token') || null;
    urlInstallSlug.value = q.get('install') || null;
}, { immediate: true });

watch([urlPurchaseToken, urlInstallSlug, currentTab], ([token, installSlug, tab]) => {
    if (tab !== 'installed' || !installSlug || !token) return;
    installStorePlugin(installSlug, token);
}, { immediate: true });

watch(() => page.props?.flash?.zip_unavailable, (v) => {
    if (v) showZipUnavailableModal.value = true;
});

function openZipUnavailableModal() {
    showZipUnavailableModal.value = true;
    downloadFallbackError.value = '';
}

function closeZipUnavailableModal() {
    showZipUnavailableModal.value = false;
}

async function downloadPluginFallback() {
    const slug = lastInstallSlug.value;
    const baseUrl = PLUGIN_STORE_API_URL.value;
    if (!slug) {
        if (lastInstallDownloadUrl.value) window.open(lastInstallDownloadUrl.value, '_blank');
        closeZipUnavailableModal();
        return;
    }
    downloadFallbackError.value = '';
    downloadFallbackLoading.value = true;
    try {
        const apiUrl = baseUrl + '/api/v1/plugins/' + encodeURIComponent(slug) + '/request-download';
        const body = urlPurchaseToken.value ? { purchase_token: urlPurchaseToken.value } : {};
        const r = await fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify(body),
            credentials: 'omit',
        });
        const json = await r.json().catch(() => ({}));
        const downloadUrl = json?.download_url;
        if (!r.ok || !downloadUrl) {
            downloadFallbackError.value = json?.message || json?.error || 'Não foi possível obter o link de download.';
            return;
        }
        window.open(downloadUrl, '_blank');
        closeZipUnavailableModal();
    } catch (e) {
        downloadFallbackError.value = 'Não foi possível obter o link. Tente instalar manualmente com o ZIP.';
    } finally {
        downloadFallbackLoading.value = false;
    }
}

function openManualInstallModal() {
    showManualInstallModal.value = true;
    manualInstallError.value = '';
    if (manualInstallFileInput.value) manualInstallFileInput.value.value = '';
    closeZipUnavailableModal();
}

function closeManualInstallModal() {
    showManualInstallModal.value = false;
    manualInstallError.value = '';
}

function submitManualInstall() {
    const file = manualInstallFileInput.value?.files?.[0];
    if (!file || !file.name.toLowerCase().endsWith('.zip')) {
        manualInstallError.value = 'Selecione um arquivo .zip do plugin.';
        return;
    }
    manualInstallError.value = '';
    manualInstallProcessing.value = true;
    router.post('/gerenciar-plugins/install-from-zip', { plugin_zip: file }, {
        preserveScroll: true,
        forceFormData: true,
        onFinish: () => { manualInstallProcessing.value = false; },
        onSuccess: () => { closeManualInstallModal(); },
        onError: (errors) => {
            manualInstallError.value = typeof errors?.plugin_zip === 'string'
                ? errors.plugin_zip
                : (errors?.plugin_zip?.[0] ?? 'Falha ao instalar. Tente novamente.');
        },
    });
}
</script>

<template>
    <div class="space-y-6">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="ep-page-heading">
                        Plugins
                    </h1>
                    <span class="ep-chip tabular-nums">
                        <span class="h-1.5 w-1.5 rounded-full bg-[var(--ep-pos)]" aria-hidden="true" />
                        {{ plugins.filter((p) => p.is_registered && p.is_enabled).length }} de {{ plugins.length }} ativos
                    </span>
                </div>
                <p class="mt-1 text-[13px] text-[var(--ep-text-3)]">
                    Gerencie extensões instaladas na sua conta.
                </p>
            </div>
            <HorizontalScrollTabs aria-label="Abas de plugins" nav-class="ep-tabs" wrapper-class="sm:!w-auto">
                <template v-for="tab in TABS" :key="tab.id">
                    <a
                        v-if="tab.id === 'store'"
                        :href="PLUGIN_STORE_URL"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="ep-tab border border-transparent"
                    >
                        <component :is="tab.icon" class="h-4 w-4 shrink-0" :stroke-width="1.75" aria-hidden="true" />
                        {{ tab.label }}
                        <ArrowUpRight class="h-3.5 w-3.5 opacity-60" :stroke-width="1.75" aria-hidden="true" />
                    </a>
                    <button
                        v-else
                        type="button"
                        :class="['ep-tab', currentTab === tab.id ? 'ep-tab--active' : 'border border-transparent']"
                        @click="setTab(tab.id)"
                    >
                        <component :is="tab.icon" class="h-4 w-4 shrink-0" :stroke-width="1.75" aria-hidden="true" />
                        {{ tab.label }}
                    </button>
                </template>
            </HorizontalScrollTabs>
        </header>

        <!-- Aba Instalados -->
        <template v-if="currentTab === 'installed'">
            <section class="space-y-3" aria-labelledby="plugins-installed-title">
                <div class="flex flex-wrap items-end justify-between gap-3 px-1">
                    <div class="min-w-0">
                        <h2 id="plugins-installed-title" class="ep-section-title">
                            Instalados
                            <span class="ml-1 text-[12px] font-normal tabular-nums text-[var(--ep-text-4)]">{{ plugins.length }} na pasta</span>
                        </h2>
                        <p class="mt-0.5 max-w-2xl text-[12.5px] text-[var(--ep-text-3)]">
                            Ative ou desative plugins. Envie um ZIP com
                            <code class="rounded-[6px] border border-[var(--ep-line)] bg-[var(--ep-active)] px-1 py-px font-mono text-[11.5px] text-[var(--ep-text-2)]">plugin.json</code>
                            na pasta raiz para instalar manualmente.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="ep-btn-secondary shrink-0"
                        @click="openManualInstallModal"
                    >
                        <FolderUp class="h-4 w-4" :stroke-width="1.75" />
                        Instalar ZIP
                    </button>
                </div>

                <div
                    v-if="plugins.length === 0"
                    class="panel-card ep-empty"
                >
                    <Puzzle class="mb-1 h-5 w-5 text-[var(--ep-text-4)]" :stroke-width="1.75" aria-hidden="true" />
                    <p class="ep-empty__title">
                        Nenhum plugin na pasta
                        <code class="rounded-[6px] border border-[var(--ep-line)] bg-[var(--ep-active)] px-1.5 py-0.5 font-mono text-[11.5px]">plugins/</code>.
                    </p>
                    <p class="ep-empty__text">Use “Instalar ZIP” para enviar um plugin ou abra a Loja de plugins.</p>
                </div>

                <div
                    v-else
                    class="panel-card ep-data overflow-hidden"
                >
                    <ul class="divide-y divide-[var(--ep-line)]" role="list">
                        <li
                            v-for="plugin in plugins"
                            :key="plugin.slug"
                            class="flex flex-col gap-3 px-5 py-3.5 transition-colors duration-150 hover:bg-[var(--ep-hover)] sm:flex-row sm:items-center sm:gap-4"
                        >
                            <div class="flex min-w-0 flex-1 items-start gap-3.5">
                                <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-[13px] border border-[var(--ep-glass-border)] bg-[var(--ep-glass-strong)] text-[var(--ep-accent)] shadow-[var(--ep-glass-highlight),0_8px_22px_-12px_var(--ep-glow)]" aria-hidden="true">
                                    <Puzzle class="h-[18px] w-[18px]" :stroke-width="1.75" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                        <span class="truncate text-[13.5px] font-medium text-[var(--ep-text)]">
                                            {{ plugin.name }}
                                        </span>
                                        <span class="font-mono text-[11.5px] tabular-nums text-[var(--ep-text-4)]">
                                            v{{ plugin.version }}
                                        </span>
                                        <span
                                            class="ep-chip !h-5 !px-2 text-[11px]"
                                        >
                                            {{ categoryLabel(plugin.category) }}
                                        </span>
                                    </div>
                                    <p
                                        v-if="plugin.description"
                                        class="mt-0.5 line-clamp-1 text-[12.5px] text-[var(--ep-text-3)]"
                                    >
                                        {{ plugin.description }}
                                    </p>
                                    <div class="mt-1.5 flex flex-wrap items-center gap-3 empty:hidden">
                                        <a
                                            v-if="plugin.settings_url"
                                            :href="plugin.settings_url"
                                            class="inline-flex items-center gap-1 text-[12px] font-medium text-[var(--ep-accent)] transition-opacity hover:opacity-80"
                                        >
                                            <ExternalLink class="h-3 w-3" :stroke-width="1.75" />
                                            Configurar
                                        </a>
                                        <button
                                            v-if="plugin.type === 'gateway' && plugin.is_enabled"
                                            type="button"
                                            class="inline-flex items-center gap-1 text-[12px] font-medium text-[var(--ep-text-3)] transition-colors hover:text-[var(--ep-text)]"
                                            @click="goToGateways"
                                        >
                                            <CreditCard class="h-3 w-3" :stroke-width="1.75" />
                                            Gateways
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 pl-[54px] sm:shrink-0 sm:justify-end sm:pl-0">
                                <span
                                    v-if="!plugin.is_registered"
                                    class="ep-chip ep-chip--warn"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                                    Pendente
                                </span>
                                <span
                                    v-else-if="plugin.is_enabled"
                                    class="ep-chip ep-chip--pos"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                                    Ativo
                                </span>
                                <span
                                    v-else
                                    class="ep-chip"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-current opacity-60" aria-hidden="true" />
                                    Inativo
                                </span>

                                <template v-if="plugin.is_registered">
                                    <Button
                                        v-if="plugin.is_enabled"
                                        variant="outline"
                                        size="sm"
                                        @click="disablePlugin(plugin.slug)"
                                    >
                                        <PowerOff class="h-3.5 w-3.5" :stroke-width="1.75" />
                                        <span class="hidden sm:inline">Desativar</span>
                                    </Button>
                                    <Button
                                        v-else
                                        size="sm"
                                        @click="enablePlugin(plugin.slug)"
                                    >
                                        <Power class="h-3.5 w-3.5" :stroke-width="1.75" />
                                        <span class="hidden sm:inline">Ativar</span>
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="w-8 px-0 hover:!bg-[var(--ep-neg-bg)] hover:!text-[var(--ep-neg)]"
                                        :disabled="uninstallingSlug === plugin.slug"
                                        :title="uninstallingSlug === plugin.slug ? 'Excluindo...' : 'Excluir plugin'"
                                        @click="uninstallPlugin(plugin)"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" :stroke-width="1.75" />
                                    </Button>
                                </template>
                                <template v-else>
                                    <Button
                                        size="sm"
                                        :disabled="registeringSlug === plugin.slug"
                                        @click="registerPlugin(plugin.slug)"
                                    >
                                        <Download v-if="registeringSlug !== plugin.slug" class="h-3.5 w-3.5" :stroke-width="1.75" />
                                        <span v-else class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-current border-t-transparent" />
                                        {{ registeringSlug === plugin.slug ? 'Instalando...' : 'Instalar' }}
                                    </Button>
                                </template>
                            </div>
                        </li>
                    </ul>
                </div>
            </section>
        </template>

        <!-- Modal: extensão Zip não disponível (fallback) -->
        <Teleport to="body">
            <div
                v-if="showZipUnavailableModal"
                class="ep-scrim fixed inset-0 z-[60] flex items-center justify-center p-4"
                @click.self="closeZipUnavailableModal"
            >
                <div
                    class="ep-modal max-h-[90vh] w-full max-w-md overflow-y-auto p-6"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="zip-unavailable-title"
                >
                    <h3 id="zip-unavailable-title" class="text-[16px] font-semibold tracking-[-0.02em] text-[var(--ep-text)]">
                        Extensão PHP Zip não disponível
                    </h3>
                    <p class="mt-1.5 text-[13px] leading-[1.55] text-[var(--ep-text-3)]">
                        A instalação automática precisa da extensão Zip no PHP. Use uma das opções abaixo.
                    </p>

                    <div class="mt-4 rounded-[14px] border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4">
                        <p class="text-[13px] font-medium text-[var(--ep-text)]">
                            Extrair manualmente no servidor
                        </p>
                        <ol class="mt-2 list-decimal space-y-1.5 pl-4 text-[12.5px] leading-[1.55] text-[var(--ep-text-3)] marker:text-[var(--ep-text-4)] marker:tabular-nums">
                            <li>Obtenha o ZIP do plugin (use o botão abaixo para gerar o link).</li>
                            <li>No painel da sua hospedagem (gerenciador de arquivos, FTP etc.), acesse a pasta de plugins do projeto.</li>
                            <li>Envie o ZIP para essa pasta ou baixe o arquivo direto do link para o servidor (muitos painéis têm “Baixar de URL”).</li>
                            <li>Extraia o ZIP nessa mesma pasta. O resultado deve ser uma pasta que contém o arquivo <code class="rounded-[6px] bg-[var(--ep-active)] px-1 font-mono text-[11.5px] text-[var(--ep-text-2)]">plugin.json</code>.</li>
                            <li>
                                Instalações via painel ou ZIP — pasta persistente (não é apagada ao atualizar o código a partir do Git):
                                <code class="mt-1 block break-all rounded-[8px] border border-[var(--ep-line)] bg-[var(--ep-input)] px-2 py-1 font-mono text-[11.5px] text-[var(--ep-text-2)]">{{ plugins_install_path || pluginsPath || '.docker/plugins-installed/ (Docker) ou storage/app/plugins-installed/' }}</code>
                            </li>
                            <li>
                                Plugins incluídos no repositório (exemplo) ficam em:
                                <code class="mt-1 block break-all rounded-[8px] border border-[var(--ep-line)] bg-[var(--ep-input)] px-2 py-1 font-mono text-[11.5px] text-[var(--ep-text-2)]">{{ plugins_bundled_path || 'plugins/' }}</code>
                            </li>
                            <li>Atualize esta página para o plugin aparecer.</li>
                        </ol>
                    </div>

                    <p v-if="downloadFallbackError" class="mt-3 rounded-[12px] border border-[color-mix(in_oklab,var(--ep-neg)_35%,transparent)] bg-[var(--ep-neg-bg)] px-3 py-2.5 text-[12.5px] text-[var(--ep-neg)]">
                        {{ downloadFallbackError }}
                    </p>
                    <div class="mt-6 flex flex-wrap gap-2">
                        <Button
                            v-if="lastInstallSlug"
                            size="sm"
                            :disabled="downloadFallbackLoading"
                            @click="downloadPluginFallback"
                        >
                            <Download v-if="!downloadFallbackLoading" class="h-4 w-4" :stroke-width="1.75" />
                            <span v-else class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />
                            {{ downloadFallbackLoading ? 'Gerando link...' : 'Baixar plugin (ZIP)' }}
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="downloadFallbackLoading"
                            @click="openManualInstallModal"
                        >
                            <FolderUp class="h-4 w-4" :stroke-width="1.75" />
                            Instalar manualmente (enviar ZIP)
                        </Button>
                        <Button variant="ghost" size="sm" class="ml-auto" @click="closeZipUnavailableModal">
                            Fechar
                        </Button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal: instalar plugin manualmente (upload ZIP) -->
        <Teleport to="body">
            <div
                v-if="showManualInstallModal"
                class="ep-scrim fixed inset-0 z-[60] flex items-center justify-center p-4"
                @click.self="closeManualInstallModal"
            >
                <div
                    class="ep-modal w-full max-w-md p-6"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="manual-install-title"
                >
                    <h3 id="manual-install-title" class="text-[16px] font-semibold tracking-[-0.02em] text-[var(--ep-text)]">
                        Instalar plugin manualmente
                    </h3>
                    <p class="mt-1.5 text-[13px] leading-[1.55] text-[var(--ep-text-3)]">
                        Envie o arquivo .zip do plugin. O nome da pasta do plugin será detectado automaticamente (pasta raiz dentro do ZIP).
                    </p>
                    <form @submit.prevent="submitManualInstall" class="mt-5 space-y-4">
                        <div>
                            <label class="ep-label">Arquivo ZIP</label>
                            <input
                                ref="manualInstallFileInput"
                                type="file"
                                accept=".zip"
                                class="ep-input !h-auto cursor-pointer py-1.5 text-[13px] file:mr-3 file:h-7 file:cursor-pointer file:rounded-[8px] file:border file:border-solid file:border-[var(--ep-line-strong)] file:bg-[var(--ep-glass-strong)] file:px-3 file:text-[12.5px] file:font-medium file:text-[var(--ep-text)]"
                                @change="manualInstallError = ''"
                            />
                        </div>
                        <p v-if="manualInstallError" class="rounded-[12px] border border-[color-mix(in_oklab,var(--ep-neg)_35%,transparent)] bg-[var(--ep-neg-bg)] px-3 py-2.5 text-[12.5px] text-[var(--ep-neg)]">
                            {{ manualInstallError }}
                        </p>
                        <div class="flex flex-wrap justify-end gap-2 border-t border-[var(--ep-line)] pt-4">
                            <Button type="button" variant="outline" size="sm" @click="closeManualInstallModal">
                                Cancelar
                            </Button>
                            <Button type="submit" size="sm" :disabled="manualInstallProcessing">
                                {{ manualInstallProcessing ? 'Instalando...' : 'Instalar' }}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>
