<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import Button from '@/components/ui/Button.vue';
import HorizontalScrollTabs from '@/components/ui/HorizontalScrollTabs.vue';
import {
    Mail,
    Languages,
    Banknote,
    HardDrive,
    Clock,
    AlertCircle,
    Trash2,
    RefreshCw,
    Upload,
    Download,
    Palette,
    Bell,
    CheckCircle2,
} from 'lucide-vue-next';
import { Copy, Info, Link2, Terminal, GitBranch, ShieldCheck, Database } from 'lucide-vue-next';
import IntegrationCard from '@/components/IntegrationCard.vue';
import EmailProviderSidebar from '@/components/EmailProviderSidebar.vue';

defineOptions({ layout: LayoutInfoprodutor });

const props = defineProps({
    settings: {
        type: Object,
        required: true,
    },
    currency_catalog_presets: {
        type: Object,
        default: () => ({}),
    },
    current_version: {
        type: String,
        default: '1.0.0',
    },
    updates_enabled: {
        type: Boolean,
        default: true,
    },
    git_available: {
        type: Boolean,
        default: false,
    },
    update_mode: {
        type: String,
        default: 'archive',
    },
    archive_ready: {
        type: Boolean,
        default: true,
    },
    preflight_warnings: {
        type: Array,
        default: () => [],
    },
    cloud_mode: {
        type: Boolean,
        default: false,
    },
    docker_mode: {
        type: Boolean,
        default: false,
    },
    app_url: {
        type: String,
        default: '',
    },
    base_path: {
        type: String,
        default: '',
    },
    cron_url: {
        type: String,
        default: null,
    },
    push_vapid: {
        type: Object,
        default: () => ({
            configured: false,
            public_key: null,
            env_writable: false,
            env_exists: false,
            shared_file_exists: false,
        }),
    },
    settings_plugin_tabs: {
        type: Array,
        default: () => [],
    },
});

function allAllowedTabIds() {
    const core = ['email', 'storage', 'traducoes', 'moedas', 'push', 'cron', 'update'];
    const extra = (props.settings_plugin_tabs || []).map((t) => t.id).filter(Boolean);
    return [...core, ...extra];
}

const activeTab = ref('email');
if (typeof window !== 'undefined') {
    const t = new URLSearchParams(window.location.search).get('tab');
    if (t && allAllowedTabIds().includes(t)) activeTab.value = t;
    const isMobile = window.matchMedia && window.matchMedia('(max-width: 639px)').matches;
    if (isMobile && activeTab.value === 'traducoes') activeTab.value = 'email';
}

const pluginTabIds = computed(() => (props.settings_plugin_tabs || []).map((t) => t.id).filter(Boolean));

function isPluginTab(tabId) {
    return pluginTabIds.value.includes(tabId);
}

import { usePluginComponentResolver } from '@/composables/usePluginComponentResolver';

const pluginPagesGlob = import.meta.glob('../../PluginPages/**/*.vue');
const page = usePage();
const { resolve: resolvePluginTabComponent } = usePluginComponentResolver(
    computed(() => page.props.plugin_ui),
    pluginPagesGlob,
);

function getPluginTabComponent(tab) {
    if (!tab) {
        return null;
    }
    if (typeof tab === 'object' && (tab.ui_mode || tab.component)) {
        return resolvePluginTabComponent(tab);
    }
    return resolvePluginTabComponent({ component: tab, ui_mode: 'legacy' });
}

const defaultTranslations = () => ({
    pt_BR: {},
    en: {},
    es: {},
    ...(props.settings.checkout_translations ?? {}),
});
const defaultCurrencies = () => [...(props.settings.currencies ?? [])];

const form = useForm({
    smtp_host: props.settings.smtp_host ?? '',
    smtp_port: props.settings.smtp_port ?? '587',
    smtp_username: props.settings.smtp_username ?? '',
    smtp_encryption: props.settings.smtp_encryption ?? 'tls',
    smtp_password: '', // never pre-fill password
    mail_from_address: props.settings.mail_from_address ?? '',
    mail_from_name: props.settings.mail_from_name ?? '',
    reply_to: props.settings.reply_to ?? '',
    email_provider: props.settings.email_provider ?? 'smtp',
    hostinger_smtp_username: props.settings.hostinger_smtp_username ?? '',
    hostinger_smtp_password: '', // never pre-fill password
    hostinger_mail_from_address: props.settings.hostinger_mail_from_address ?? '',
    hostinger_mail_from_name: props.settings.hostinger_mail_from_name ?? '',
    hostinger_reply_to: props.settings.hostinger_reply_to ?? '',
    sendgrid_api_key: '', // never pre-fill API key
    sendgrid_mail_from_address: props.settings.sendgrid_mail_from_address ?? '',
    sendgrid_mail_from_name: props.settings.sendgrid_mail_from_name ?? '',
    checkout_translations: defaultTranslations(),
    currencies: defaultCurrencies(),
    storage_provider: props.settings.storage_provider ?? 'local',
    storage_s3_key: props.settings.storage_s3_key ?? '',
    storage_s3_secret: '', // never pre-fill
    storage_s3_bucket: props.settings.storage_s3_bucket ?? '',
    storage_s3_region: props.settings.storage_provider === 'r2' ? 'auto' : (props.settings.storage_s3_region ?? 'us-east-1'),
    storage_s3_endpoint: props.settings.storage_s3_endpoint ?? '',
    storage_s3_url: props.settings.storage_s3_url ?? '',
});

const showCloudR2Override = ref(false);

const testForm = useForm({
    test_to: '',
});

import { ref as vueRef } from 'vue';
const connectionResult = vueRef({ status: null, message: '' });
const sendResult = vueRef({ status: null, message: '' });
const connectionTesting = vueRef(false);
const sendTestSending = vueRef(false);

const coreTabsStatic = [
    { id: 'email', label: 'E‑MAIL', icon: Mail },
    { id: 'storage', label: 'Storage', icon: HardDrive },
    { id: 'traducoes', label: 'Traduções', icon: Languages },
    { id: 'moedas', label: 'Moedas', icon: Banknote },
    { id: 'push', label: 'Notificações push', icon: Bell },
    { id: 'cron', label: 'Cron', icon: Clock },
    { id: 'update', label: 'Update', icon: Download },
];

const tabs = computed(() => {
    const plug = (props.settings_plugin_tabs || []).map((t) => ({
        id: t.id,
        label: t.label,
        icon: Palette,
    }));
    return [...coreTabsStatic, ...plug];
});

const updateCheckLoading = ref(false);
const updateCheckResult = ref(null);
const updateRunLoading = ref(false);
const lastUpdateSteps = ref(null);
const integrityLoading = ref(false);
const integrityResult = ref(null);
const migrateLoading = ref(false);
const migrateResult = ref(null);

const pushVapidConfigured = computed(() => !!props.push_vapid?.configured);
const pushVapidGenerating = ref(false);
const pushVapidResult = ref(null);

async function generatePushVapid(force = false) {
    if (force) {
        const ok = window.confirm(
            'Regenerar as chaves VAPID invalida as inscrições push atuais. Cada usuário precisará reativar notificações no painel. Continuar?',
        );
        if (!ok) return;
    }

    pushVapidGenerating.value = true;
    pushVapidResult.value = null;
    try {
        const res = await window.axios.post('/configuracoes/push/vapid/generate', { force });
        pushVapidResult.value = {
            status: 'success',
            message: res.data?.message || 'Chaves VAPID configuradas.',
        };
        router.reload();
    } catch (e) {
        pushVapidResult.value = {
            status: 'error',
            message: e?.response?.data?.message || e?.message || 'Falha ao gerar chaves VAPID.',
        };
    } finally {
        pushVapidGenerating.value = false;
    }
}

async function checkForUpdate() {
    updateCheckLoading.value = true;
    updateCheckResult.value = null;
    try {
        const res = await window.axios.get('/configuracoes/update/check');
        updateCheckResult.value = res.data;
    } catch (e) {
        updateCheckResult.value = {
            current: props.current_version,
            latest: null,
            available: false,
            error: e?.response?.data?.message || 'Erro ao verificar atualizações.',
            changelog_remote: null,
        };
    } finally {
        updateCheckLoading.value = false;
    }
}

async function runUpdate() {
    updateRunLoading.value = true;
    lastUpdateSteps.value = null;
    try {
        const res = await window.axios.post('/configuracoes/update/run', {}, {
            headers: { Accept: 'application/json' },
            maxRedirects: 0,
            validateStatus: (status) => status >= 200 && status < 500,
        });
        if (res.data?.steps) {
            lastUpdateSteps.value = res.data.steps;
        }
        if (res.data?.success) {
            window.location.href = res.data.redirect || '/configuracoes?tab=update';
            return;
        }
        const msg = res.data?.message || 'Falha na atualização.';
        alert(msg);
    } catch (e) {
        const status = e?.response?.status;
        if (e?.response?.data?.steps) {
            lastUpdateSteps.value = e.response.data.steps;
        }
        const msg =
            status === 429
                ? 'Muitas tentativas em pouco tempo. Aguarde e tente novamente.'
                : (e?.response?.data?.message || e?.response?.data?.error || e?.message || 'Falha na atualização.');
        alert(msg);
    } finally {
        updateRunLoading.value = false;
    }
}

async function checkIntegrity() {
    integrityLoading.value = true;
    integrityResult.value = null;
    try {
        const res = await window.axios.post('/configuracoes/update/run', { action: 'integrity' }, {
            headers: { Accept: 'application/json' },
        });
        integrityResult.value = res.data;
    } catch (e) {
        integrityResult.value = {
            repository_exists: null,
            total_migrations: 0,
            ran_count: 0,
            pending_count: 0,
            pending: [],
            pending_truncated: false,
            error: e?.response?.data?.message || e?.response?.data?.error || 'Erro ao verificar integridade.',
        };
    } finally {
        integrityLoading.value = false;
    }
}

async function runMigrations() {
    migrateLoading.value = true;
    migrateResult.value = null;
    try {
        const res = await window.axios.post('/configuracoes/update/run', { action: 'migrate' }, {
            headers: { Accept: 'application/json' },
        });
        migrateResult.value = res.data;
    } catch (e) {
        const status = e?.response?.status;
        const msg =
            status === 429
                ? 'Muitas tentativas em pouco tempo. Aguarde e tente novamente.'
                : (e?.response?.data?.message || e?.response?.data?.error || e?.message || 'Falha ao rodar migrations.');
        migrateResult.value = { success: false, message: msg, output: '' };
    } finally {
        migrateLoading.value = false;
    }
}

const translationKeys = computed(() => {
    const t = form.checkout_translations ?? {};
    const keys = new Set([
        ...Object.keys(t.pt_BR ?? {}),
        ...Object.keys(t.en ?? {}),
        ...Object.keys(t.es ?? {}),
    ]);
    return [...keys].sort();
});

const localeLabels = { pt_BR: 'Português (BR)', en: 'English', es: 'Español' };

function ensureTranslationKey(key) {
    if (!form.checkout_translations.pt_BR) form.checkout_translations.pt_BR = {};
    if (!form.checkout_translations.en) form.checkout_translations.en = {};
    if (!form.checkout_translations.es) form.checkout_translations.es = {};
    if (form.checkout_translations.pt_BR[key] === undefined) form.checkout_translations.pt_BR[key] = '';
    if (form.checkout_translations.en[key] === undefined) form.checkout_translations.en[key] = '';
    if (form.checkout_translations.es[key] === undefined) form.checkout_translations.es[key] = '';
}

const CURRENCY_PRESETS = {
    ...(props.currency_catalog_presets || {}),
};

const rateModeByIndex = ref({});
const refreshLoadingByIndex = ref({});
const rateFetchError = ref(null);
const importCatalogLoading = ref(false);
const syncAllRatesLoading = ref(false);
const catalogActionMessage = ref(null);
const catalogActionError = ref(null);

function getRateMode(index) {
    return rateModeByIndex.value[index] ?? 'brl_to';
}

function setRateMode(index, mode) {
    rateModeByIndex.value = { ...rateModeByIndex.value, [index]: mode };
}

function inverseRate(rate) {
    const r = Number(rate);
    return r > 0 ? (1 / r) : '';
}

function setRateFromInverse(curr, value) {
    const v = parseFloat(String(value).replace(',', '.'));
    curr.rate_to_brl = v > 0 ? 1 / v : 0;
}

function applyPreset(curr) {
    const code = String(curr.code || '').trim().toUpperCase();
    const preset = CURRENCY_PRESETS[code];
    if (preset) {
        if (!curr.symbol) curr.symbol = preset.symbol;
        if (!curr.label) curr.label = preset.label;
    }
}

function onCurrencyCodeChange(curr, index) {
    curr.code = String(curr.code || '').toUpperCase();
    applyPreset(curr);
}

async function fetchRate(curr, index) {
    const code = String(curr.code || '').trim().toUpperCase();
    if (!code || code === 'BRL') return;
    refreshLoadingByIndex.value = { ...refreshLoadingByIndex.value, [index]: true };
    rateFetchError.value = null;
    try {
        const res = await fetch(`https://api.frankfurter.app/latest?from=BRL&to=${code}`);
        const data = await res.json();
        if (data.rates && typeof data.rates[code] === 'number') {
            curr.rate_to_brl = data.rates[code];
        } else {
            rateFetchError.value = 'Moeda não suportada pela API.';
        }
    } catch (e) {
        rateFetchError.value = 'Erro ao buscar taxa. Verifique a conexão.';
    } finally {
        refreshLoadingByIndex.value = { ...refreshLoadingByIndex.value, [index]: false };
    }
}

function canFetchRate(curr) {
    const code = String(curr.code || '').trim().toUpperCase();
    return code && code !== 'BRL';
}

function addCurrency() {
    form.currencies.push({ code: '', symbol: '', label: '', rate_to_brl: 1 });
}

function removeCurrency(index) {
    form.currencies.splice(index, 1);
    const next = { ...rateModeByIndex.value };
    delete next[index];
    rateModeByIndex.value = next;
}

async function importInternationalCurrencies() {
    importCatalogLoading.value = true;
    catalogActionMessage.value = null;
    catalogActionError.value = null;
    try {
        const { data } = await window.axios.post('/configuracoes/currencies/import-catalog');
        if (data?.currencies?.length) {
            form.currencies = data.currencies;
            catalogActionMessage.value = `${data.count} moedas importadas com taxas atualizadas.`;
        }
    } catch (e) {
        catalogActionError.value = e?.response?.data?.message || 'Não foi possível importar o catálogo.';
    } finally {
        importCatalogLoading.value = false;
    }
}

async function syncAllCurrencyRates() {
    syncAllRatesLoading.value = true;
    catalogActionMessage.value = null;
    catalogActionError.value = null;
    try {
        const { data } = await window.axios.post('/configuracoes/currencies/sync-rates');
        if (data?.currencies?.length) {
            form.currencies = data.currencies;
            catalogActionMessage.value = `Taxas atualizadas para ${data.count} moedas.`;
        }
    } catch (e) {
        catalogActionError.value = e?.response?.data?.message || 'Não foi possível atualizar as taxas.';
    } finally {
        syncAllRatesLoading.value = false;
    }
}

async function testConnection() {
    testForm.clearErrors();
    connectionResult.value.status = null;
    connectionResult.value.message = '';
    connectionTesting.value = true;
    const provider = form.email_provider || 'smtp';
    const payload = { email_provider: provider };
    if (provider === 'hostinger') {
        payload.hostinger_smtp_username = form.hostinger_smtp_username;
        payload.hostinger_smtp_password = form.hostinger_smtp_password;
    } else if (provider === 'sendgrid') {
        payload.sendgrid_api_key = form.sendgrid_api_key;
        payload.sendgrid_mail_from_address = form.sendgrid_mail_from_address;
        payload.sendgrid_mail_from_name = form.sendgrid_mail_from_name;
    } else {
        payload.smtp_host = form.smtp_host;
        payload.smtp_port = form.smtp_port;
        payload.smtp_username = form.smtp_username;
        payload.smtp_password = form.smtp_password;
        payload.smtp_encryption = form.smtp_encryption;
        payload.mail_from_address = form.mail_from_address;
        payload.mail_from_name = form.mail_from_name;
    }
    try {
        await window.axios.post('/configuracoes/email/connection-test', payload);
        connectionResult.value.status = 'success';
        connectionResult.value.message = 'Conexão estabelecida com sucesso.';
    } catch (e) {
        connectionResult.value.status = 'error';
        let msg = 'Erro ao testar conexão.';
        if (testForm.errors && Object.keys(testForm.errors).length) {
            msg = Object.values(testForm.errors).flat().join(' ');
        } else if (e && e.response && e.response.data && e.response.data.error) {
            msg = e.response.data.error;
        }
        connectionResult.value.message = msg;
    } finally {
        connectionTesting.value = false;
    }
}

async function sendTestEmail() {
    testForm.clearErrors();
    sendTestSending.value = true;
    const provider = form.email_provider || 'smtp';
    const payload = { test_to: testForm.test_to, email_provider: provider };
    if (provider === 'hostinger') {
        payload.hostinger_smtp_username = form.hostinger_smtp_username;
        payload.hostinger_smtp_password = form.hostinger_smtp_password;
    } else if (provider === 'sendgrid') {
        payload.sendgrid_api_key = form.sendgrid_api_key;
        payload.sendgrid_mail_from_address = form.sendgrid_mail_from_address;
        payload.sendgrid_mail_from_name = form.sendgrid_mail_from_name;
    } else {
        payload.smtp_host = form.smtp_host;
        payload.smtp_port = form.smtp_port;
        payload.smtp_username = form.smtp_username;
        payload.smtp_password = form.smtp_password;
        payload.smtp_encryption = form.smtp_encryption;
        payload.mail_from_address = form.mail_from_address;
        payload.mail_from_name = form.mail_from_name;
    }
    try {
        await window.axios.post('/configuracoes/email/send-test', payload);
        sendResult.value.status = 'success';
        sendResult.value.message = 'E‑mail de teste enviado com sucesso.';
        setTimeout(() => {
            sendResult.value.status = null;
            sendResult.value.message = '';
        }, 4000);
    } catch (e) {
        sendResult.value.status = 'error';
        let msg = 'Erro ao enviar e‑mail de teste.';
        if (e && e.response && e.response.data && e.response.data.error) {
            msg = e.response.data.error;
        }
        sendResult.value.message = msg;
        setTimeout(() => {
            sendResult.value.status = null;
            sendResult.value.message = '';
        }, 6000);
    } finally {
        sendTestSending.value = false;
    }
}

const storageProviders = [
    { id: 'local', label: 'Local', description: 'Arquivos em storage/app/public (padrão)' },
    { id: 's3', label: 'AWS S3', description: 'Amazon Simple Storage Service', endpoint: '' },
    { id: 'wasabi', label: 'Wasabi', description: 'S3-compatível', endpoint: 'https://s3.wasabisys.com' },
    { id: 'r2', label: 'Cloudflare R2', description: 'S3-compatível sem egress', endpoint: 'https://ACCOUNT_ID.r2.cloudflarestorage.com' },
];

const storageTestResult = vueRef({ status: null, message: '' });
const storageTestLoading = vueRef(false);
const storageMigrateLoading = vueRef(false);

async function testStorageConnection() {
    storageTestResult.value = { status: null, message: '' };
    const provider = form.storage_provider;
    if (provider !== 'local' && !isCloudManagedR2.value) {
        const key = (form.storage_s3_key ?? '').trim();
        const bucket = (form.storage_s3_bucket ?? '').trim();
        if (!key || !bucket) {
            storageTestResult.value = {
                status: 'error',
                message: 'Preencha Access Key e Bucket para testar a conexão. O Secret Key pode ficar em branco se já tiver sido salvo antes.',
            };
            return;
        }
    }
    storageTestLoading.value = true;
    const region =
        provider === 'r2' ? 'auto' : (form.storage_s3_region && form.storage_s3_region.trim()) || 'us-east-1';
    const payload = isCloudManagedR2.value
        ? { storage_provider: 'r2' }
        : {
            storage_provider: provider,
            storage_s3_key: form.storage_s3_key ?? '',
            storage_s3_secret: form.storage_s3_secret ?? '',
            storage_s3_bucket: form.storage_s3_bucket ?? '',
            storage_s3_region: region,
            storage_s3_endpoint: form.storage_s3_endpoint ?? '',
        };
    try {
        const res = await window.axios.post('/configuracoes/storage/test', payload);
        storageTestResult.value = { status: 'success', message: res.data.message || 'Conexão estabelecida com sucesso.' };
    } catch (e) {
        const data = e?.response?.data;
        let message = data?.message || data?.error || 'Erro ao testar conexão.';
        if (data?.errors && typeof data.errors === 'object') {
            const firstError = Object.values(data.errors).flat().find(Boolean);
            if (firstError) message = firstError;
        }
        storageTestResult.value = { status: 'error', message };
    } finally {
        storageTestLoading.value = false;
    }
}

function restoreRemoteStorageFieldsIfEmpty() {
    syncStorageFormFromSettings(props.settings);
}

function syncStorageFormFromSettings(settings) {
    if (!settings) return;
    if (!(form.storage_s3_key ?? '').trim() && (settings.storage_s3_key ?? '').trim()) {
        form.storage_s3_key = settings.storage_s3_key;
    }
    if (!(form.storage_s3_bucket ?? '').trim() && (settings.storage_s3_bucket ?? '').trim()) {
        form.storage_s3_bucket = settings.storage_s3_bucket;
    }
    if (!(form.storage_s3_endpoint ?? '').trim() && (settings.storage_s3_endpoint ?? '').trim()) {
        form.storage_s3_endpoint = settings.storage_s3_endpoint;
    }
    if (!(form.storage_s3_url ?? '').trim() && (settings.storage_s3_url ?? '').trim()) {
        form.storage_s3_url = settings.storage_s3_url;
    }
    if (settings.storage_provider === 'r2') {
        form.storage_s3_region = 'auto';
    } else if (!(form.storage_s3_region ?? '').trim() && (settings.storage_s3_region ?? '').trim()) {
        form.storage_s3_region = settings.storage_s3_region;
    }
}

function onStorageProviderChange(providerId) {
    form.storage_provider = providerId;
    showCloudR2Override.value = false;
    if (providerId !== 'local') {
        restoreRemoteStorageFieldsIfEmpty();
    }
    const prov = storageProviders.find((p) => p.id === providerId);
    if (prov?.endpoint && !form.storage_s3_endpoint) {
        form.storage_s3_endpoint = prov.endpoint;
    }
    if (providerId === 'r2') {
        form.storage_s3_region = 'auto';
    }
}

const isStorageRemote = computed(
    () =>
        form.storage_provider === 's3' ||
        form.storage_provider === 'wasabi' ||
        form.storage_provider === 'r2',
);

const isCloudManagedR2 = computed(
    () =>
        !!props.cloud_mode &&
        !!props.settings.storage_cloud_r2_managed &&
        form.storage_provider === 'r2' &&
        showCloudR2Override.value === false,
);
const canMigrateStorage = computed(
    () =>
        isStorageRemote.value &&
        (isCloudManagedR2.value ||
            ((form.storage_s3_key ?? '').trim() !== '' &&
                (form.storage_s3_bucket ?? '').trim() !== '')),
);

async function migrateStorageToRemote() {
    storageTestResult.value = { status: null, message: '' };
    storageMigrateLoading.value = true;
    try {
        const res = await window.axios.post('/configuracoes/storage/migrate');
        const d = res.data;
        storageTestResult.value = {
            status: 'success',
            message: d.message || `${d.transferred ?? 0} arquivo(s) transferido(s) com sucesso.`,
        };
    } catch (e) {
        const data = e?.response?.data;
        let message = data?.message || data?.error || 'Erro ao transferir arquivos.';
        if (data?.errors && Array.isArray(data.errors) && data.errors[0]?.message) {
            message += ' ' + data.errors[0].message;
        }
        storageTestResult.value = { status: 'error', message };
    } finally {
        storageMigrateLoading.value = false;
    }
}

const providers = [
    {
        id: 'smtp',
        title: 'SMTP',
        logo: '/images/integrations/smtp.svg',
        description: 'Configuração SMTP',
    },
    {
        id: 'hostinger',
        title: 'Hostinger Mail',
        logo: '/images/integrations/hostinger.webp',
        description: 'Configuração Hostinger',
        defaults: { smtp_host: 'smtp.hostinger.com', smtp_port: '465', smtp_encryption: 'ssl' },
    },
    {
        id: 'sendgrid',
        title: 'SendGrid',
        logo: '/images/integrations/twillio-sendgrid.jpg',
        description: 'Envio via API Key SendGrid',
    },
];

const selectedProviderId = ref(form.email_provider || 'smtp');
const sidebarOpen = ref(false);
const selectedProvider = ref(null);

function selectProvider(provider) {
    selectedProviderId.value = provider.id;
    form.email_provider = provider.id;
}

function openProviderConfig(provider) {
    selectedProvider.value = provider;
    sidebarOpen.value = true;
}

function closeSidebar() {
    sidebarOpen.value = false;
}

function saveFromSidebar() {
    form.put('/configuracoes', {
        preserveScroll: true,
        onSuccess: () => closeSidebar(),
    });
}

function isProviderConfigured(providerId) {
    if (providerId === 'smtp') {
        return !!(form.smtp_host && form.smtp_username);
    }
    if (providerId === 'hostinger') {
        return !!form.hostinger_smtp_username;
    }
    if (providerId === 'sendgrid') {
        return !!form.sendgrid_mail_from_address;
    }
    return false;
}

function copyToClipboard(text) {
    try {
        navigator.clipboard?.writeText(text);
    } catch (_) {}
}

const cronLinuxLine = computed(() => {
    const path = props.base_path && typeof props.base_path === 'string' ? props.base_path : '/caminho/do/projeto';
    return `* * * * * cd ${path} && php artisan schedule:run >> /dev/null 2>&1`;
});

const cronCurlLine = computed(() => {
    if (!props.cron_url) return '';
    return `* * * * * curl -fsS "${props.cron_url}" > /dev/null 2>&1`;
});

const inputClass =
    'block w-full rounded-xl border-2 border-zinc-200 bg-white px-4 py-2.5 text-zinc-900 placeholder-zinc-400 transition focus:border-[var(--color-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-zinc-500';
const selectClass =
    'block w-full rounded-xl border-2 border-zinc-200 bg-white px-4 py-2.5 text-zinc-900 transition focus:border-[var(--color-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white';
</script>

<template>
    <div class="space-y-5">
        <!-- Cabeçalho -->
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0">
                <p class="text-[13px] text-[var(--ep-text-3)]">
                    Gerencie e-mail, traduções do checkout e moedas disponíveis.
                </p>
            </div>
            <span class="ep-chip tabular-nums">
                <span class="h-1.5 w-1.5 rounded-full bg-[var(--ep-pos)]" aria-hidden="true" />
                Versão {{ current_version }}
            </span>
        </header>

        <!-- Seções (abas segmentadas em vidro) -->
        <HorizontalScrollTabs aria-label="Abas de configurações" nav-class="ep-tabs">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    type="button"
                    :aria-current="activeTab === tab.id ? 'page' : undefined"
                    :class="[
                        'ep-tab',
                        tab.id === 'traducoes' ? 'hidden sm:inline-flex' : 'inline-flex',
                        activeTab === tab.id ? 'ep-tab--active' : 'border border-transparent',
                    ]"
                    @click="activeTab = tab.id"
                >
                    <component :is="tab.icon" class="h-4 w-4 shrink-0" :stroke-width="1.75" aria-hidden="true" />
                    {{ tab.label }}
                </button>
        </HorizontalScrollTabs>

        <form
            v-show="activeTab !== 'update' && activeTab !== 'cron' && activeTab !== 'push' && !isPluginTab(activeTab)"
            class="w-full max-w-full space-y-6"
            @submit.prevent="form.put('/configuracoes', { preserveScroll: true, onSuccess: (page) => syncStorageFormFromSettings(page.props.settings) })"
        >
            <!-- Aba E-MAIL -->
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-show="activeTab === 'email'" class="space-y-4">
                    <section class="panel-card overflow-hidden p-6" aria-labelledby="cfg-email">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2 id="cfg-email" class="ep-section-title">Provedores de e-mail</h2>
                                <p class="mt-1 max-w-xl text-[12.5px] leading-relaxed text-[var(--ep-text-3)]">
                                    Escolha o provedor de e-mail para envio de acessos, notificações e recuperação de senha.
                                </p>
                            </div>
                            <span
                                class="ep-chip"
                                :class="isProviderConfigured(selectedProviderId) ? 'ep-chip--pos' : 'ep-chip--warn'"
                            >
                                <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                                {{ isProviderConfigured(selectedProviderId) ? 'Provedor configurado' : 'Configuração pendente' }}
                            </span>
                        </div>
                        <div class="mt-5 grid gap-4 sm:grid-cols-2 md:grid-cols-3">
                            <IntegrationCard
                                v-for="prov in providers"
                                :key="prov.id"
                                :title="prov.title"
                                :logo="prov.logo"
                                :description="prov.description"
                                :selected="prov.id === selectedProviderId"
                                :configured="isProviderConfigured(prov.id)"
                                @select="selectProvider(prov)"
                                @configure="openProviderConfig(prov)"
                            />
                        </div>
                        <div
                            v-if="selectedProviderId && !isProviderConfigured(selectedProviderId)"
                            class="mt-5 flex items-start gap-3 rounded-xl border border-[color-mix(in_oklab,var(--ep-warn)_30%,transparent)] bg-[var(--ep-warn-bg)] px-4 py-3"
                        >
                            <AlertCircle class="mt-px h-4 w-4 shrink-0 text-[var(--ep-warn)]" :stroke-width="1.75" aria-hidden="true" />
                            <p class="text-[12.5px] leading-relaxed text-[var(--ep-text-2)]">
                                Clique no ícone de engrenagem para configurar o provedor selecionado.
                            </p>
                        </div>
                    </section>
                </div>
            </Transition>

            <!-- Aba Storage -->
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-show="activeTab === 'storage'" class="space-y-4">
                    <section class="panel-card p-6" aria-labelledby="cfg-storage">
                        <div class="min-w-0">
                            <h2 id="cfg-storage" class="ep-section-title">Storage de arquivos</h2>
                            <p class="mt-1 max-w-2xl text-[12.5px] leading-relaxed text-[var(--ep-text-3)]">
                                Configure onde as imagens da plataforma serão armazenadas (produtos, checkout, área de membros, avatares).
                            </p>
                        </div>
                        <div class="mt-5 space-y-5">
                            <div>
                                <label class="ep-label">Provedor</label>
                                <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-4">
                                    <button
                                        v-for="prov in storageProviders"
                                        :key="prov.id"
                                        type="button"
                                        :class="[
                                            'relative rounded-2xl border p-4 pr-10 text-left transition-[background-color,border-color,box-shadow,transform] duration-150 active:scale-[0.99]',
                                            form.storage_provider === prov.id
                                                ? 'border-[color-mix(in_oklab,var(--ep-accent)_55%,transparent)] bg-[color-mix(in_oklab,var(--ep-accent)_10%,transparent)] shadow-[0_0_0_3px_color-mix(in_oklab,var(--ep-accent)_14%,transparent),var(--ep-glass-highlight)]'
                                                : 'border-[var(--ep-line)] bg-[var(--ep-card-2)] hover:border-[var(--ep-line-strong)] hover:bg-[var(--ep-hover)]',
                                        ]"
                                        @click="onStorageProviderChange(prov.id)"
                                    >
                                        <span
                                            class="absolute right-4 top-4 flex h-4 w-4 items-center justify-center rounded-full border"
                                            :class="form.storage_provider === prov.id
                                                ? 'border-[var(--ep-accent)]'
                                                : 'border-[var(--ep-line-strong)]'"
                                            aria-hidden="true"
                                        >
                                            <span
                                                class="h-2 w-2 rounded-full bg-[var(--ep-accent)] transition-opacity duration-150"
                                                :class="form.storage_provider === prov.id ? 'opacity-100' : 'opacity-0'"
                                            />
                                        </span>
                                        <p class="text-[13.5px] font-medium tracking-[-0.01em] text-[var(--ep-text)]">{{ prov.label }}</p>
                                        <p class="mt-1 text-[12px] leading-snug text-[var(--ep-text-3)]">{{ prov.description }}</p>
                                    </button>
                                </div>
                            </div>

                            <div v-if="form.storage_provider !== 'local'" class="space-y-4 rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-5">
                                <div
                                    v-if="isCloudManagedR2"
                                    class="flex flex-col items-start justify-between gap-4 rounded-xl border border-[color-mix(in_oklab,var(--ep-pos)_30%,transparent)] bg-[var(--ep-pos-bg)] p-4 sm:flex-row"
                                >
                                    <div class="flex min-w-0 items-start gap-3">
                                        <CheckCircle2 class="mt-px h-4 w-4 shrink-0 text-[var(--ep-pos)]" :stroke-width="1.75" aria-hidden="true" />
                                        <div class="min-w-0">
                                            <p class="text-[13px] font-medium text-[var(--ep-text)]">
                                                Parabéns, você está usando o Getfy Cloud com Cloudflare R2.
                                            </p>
                                            <p class="mt-1 text-[12.5px] text-[var(--ep-text-2)]">
                                                As credenciais foram provisionadas automaticamente.
                                            </p>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        class="ep-btn-secondary shrink-0"
                                        @click="showCloudR2Override = true"
                                    >
                                        Usar minhas credenciais
                                    </button>
                                </div>

                                <template v-else>
                                    <h3 class="ep-section-title">Credenciais S3</h3>
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label class="ep-label">Access Key</label>
                                            <input
                                                v-model="form.storage_s3_key"
                                                type="text"
                                                class="ep-input font-mono text-[12.5px]"
                                                placeholder="AKIA..."
                                                autocomplete="off"
                                            />
                                        </div>
                                        <div>
                                            <label class="ep-label">Secret Key</label>
                                            <input
                                                v-model="form.storage_s3_secret"
                                                type="password"
                                                class="ep-input"
                                                placeholder="••••••••"
                                                autocomplete="new-password"
                                            />
                                        </div>
                                        <div>
                                            <label class="ep-label">Bucket</label>
                                            <input
                                                v-model="form.storage_s3_bucket"
                                                type="text"
                                                class="ep-input"
                                                placeholder="meu-bucket"
                                            />
                                        </div>
                                        <div v-if="form.storage_provider !== 'r2'">
                                            <label class="ep-label">Region</label>
                                            <input
                                                v-model="form.storage_s3_region"
                                                type="text"
                                                class="ep-input"
                                                placeholder="us-east-1"
                                            />
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label class="ep-label">Endpoint</label>
                                            <input
                                                v-model="form.storage_s3_endpoint"
                                                type="text"
                                                class="ep-input"
                                                placeholder="https://s3.wasabisys.com ou vazio para AWS"
                                            />
                                            <p class="ep-help">R2: <span class="font-mono">https://ACCOUNT_ID.r2.cloudflarestorage.com</span></p>
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label class="ep-label">URL pública <span class="font-normal text-[var(--ep-text-4)]">(opcional)</span></label>
                                            <input
                                                v-model="form.storage_s3_url"
                                                type="text"
                                                class="ep-input"
                                                placeholder="https://cdn.exemplo.com"
                                            />
                                            <p class="ep-help">CDN ou domínio customizado.</p>
                                        </div>
                                    </div>
                                </template>

                                <div class="flex flex-col items-stretch gap-3 border-t border-[var(--ep-line)] pt-4 sm:flex-row sm:flex-wrap sm:items-center">
                                    <button
                                        type="button"
                                        :disabled="storageTestLoading"
                                        class="ep-btn-secondary w-full sm:w-auto"
                                        @click="testStorageConnection"
                                    >
                                        <RefreshCw class="h-4 w-4" :stroke-width="1.75" :class="{ 'animate-spin': storageTestLoading }" />
                                        {{ storageTestLoading ? 'Testando...' : 'Testar conexão' }}
                                    </button>
                                    <button
                                        v-if="isStorageRemote"
                                        type="button"
                                        :disabled="storageMigrateLoading || !canMigrateStorage"
                                        class="ep-btn-secondary w-full sm:w-auto"
                                        title="Salve as configurações antes de transferir."
                                        @click="migrateStorageToRemote"
                                    >
                                        <Upload class="h-4 w-4" :stroke-width="1.75" :class="{ 'animate-pulse': storageMigrateLoading }" />
                                        {{ storageMigrateLoading ? 'Transferindo...' : 'Transferir arquivos do storage local para o S3/R2' }}
                                    </button>
                                    <p
                                        v-if="storageTestResult.status"
                                        :class="[
                                            'flex items-center gap-2 text-[12.5px] sm:ml-1',
                                            storageTestResult.status === 'success'
                                                ? 'text-[var(--ep-pos)]'
                                                : 'text-[var(--ep-neg)]',
                                        ]"
                                    >
                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-current" aria-hidden="true" />
                                        {{ storageTestResult.message }}
                                    </p>
                                </div>
                            </div>
                            <div v-else class="flex items-start gap-3 rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4">
                                <HardDrive class="mt-px h-4 w-4 shrink-0 text-[var(--ep-text-3)]" :stroke-width="1.75" aria-hidden="true" />
                                <p class="text-[12.5px] leading-relaxed text-[var(--ep-text-2)]">
                                    Os arquivos serão salvos em <code class="rounded-md border border-[var(--ep-line)] bg-[var(--ep-input)] px-1.5 py-0.5 font-mono text-[11.5px] text-[var(--ep-text)]">storage/app/public</code> e servidos via <code class="rounded-md border border-[var(--ep-line)] bg-[var(--ep-input)] px-1.5 py-0.5 font-mono text-[11.5px] text-[var(--ep-text)]">/storage</code>.
                                </p>
                            </div>
                        </div>
                    </section>
                </div>
            </Transition>

            <!-- Aba Traduções -->
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-show="activeTab === 'traducoes'" class="hidden space-y-4 sm:block">
                    <section class="panel-card ep-data overflow-hidden" aria-labelledby="cfg-traducoes">
                        <div class="flex flex-wrap items-start justify-between gap-3 px-6 pb-4 pt-6">
                            <div class="min-w-0">
                                <h2 id="cfg-traducoes" class="ep-section-title">Checkout – textos por idioma</h2>
                                <p class="mt-1 text-[12.5px] leading-relaxed text-[var(--ep-text-3)]">
                                    Edite os textos exibidos no checkout. Português (BR), English, Español.
                                </p>
                            </div>
                            <span class="ep-chip tabular-nums">{{ translationKeys.length }} chaves</span>
                        </div>
                        <div class="overflow-x-auto border-t border-[var(--ep-line)]">
                            <div
                                v-if="translationKeys.length === 0"
                                class="ep-empty"
                            >
                                <Languages class="h-6 w-6 text-[var(--ep-text-4)]" :stroke-width="1.75" aria-hidden="true" />
                                <p class="ep-empty__title">Nenhuma chave de tradução</p>
                                <p class="ep-empty__text">
                                    As chaves padrão são carregadas automaticamente ao acessar o checkout.
                                </p>
                            </div>
                            <div v-else>
                                <table class="ep-table min-w-[860px]">
                                    <thead>
                                        <tr>
                                            <th class="w-[22%]">Chave</th>
                                            <th>Português (BR)</th>
                                            <th>English</th>
                                            <th>Español</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="key in translationKeys"
                                            :key="key"
                                        >
                                            <td class="whitespace-nowrap !py-3.5 align-top font-mono text-[12px] !text-[var(--ep-text-3)]">{{ key }}</td>
                                            <td class="!py-2 align-top">
                                                <input
                                                    v-model="form.checkout_translations.pt_BR[key]"
                                                    type="text"
                                                    class="ep-input !h-9 text-[13px]"
                                                    @focus="ensureTranslationKey(key)"
                                                />
                                            </td>
                                            <td class="!py-2 align-top">
                                                <input
                                                    v-model="form.checkout_translations.en[key]"
                                                    type="text"
                                                    class="ep-input !h-9 text-[13px]"
                                                    @focus="ensureTranslationKey(key)"
                                                />
                                            </td>
                                            <td class="!py-2 align-top">
                                                <input
                                                    v-model="form.checkout_translations.es[key]"
                                                    type="text"
                                                    class="ep-input !h-9 text-[13px]"
                                                    @focus="ensureTranslationKey(key)"
                                                />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                </div>
            </Transition>

            <!-- Aba Moedas -->
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-show="activeTab === 'moedas'" class="space-y-4">
                    <section class="panel-card p-6" aria-labelledby="cfg-moedas">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 id="cfg-moedas" class="ep-section-title">Moedas disponíveis no checkout</h2>
                                    <span class="ep-chip tabular-nums">{{ form.currencies.length }}</span>
                                </div>
                                <p class="mt-1 max-w-2xl text-[12.5px] leading-relaxed text-[var(--ep-text-3)]">
                                    Configure as moedas e suas taxas de conversão. Importe o catálogo internacional ou atualize todas as taxas via Frankfurter.
                                </p>
                            </div>
                            <div class="flex shrink-0 flex-wrap gap-2">
                                <button
                                    type="button"
                                    :disabled="importCatalogLoading || syncAllRatesLoading"
                                    class="ep-btn-secondary"
                                    @click="importInternationalCurrencies"
                                >
                                    <Download class="h-4 w-4" :stroke-width="1.75" :class="{ 'animate-pulse': importCatalogLoading }" />
                                    Importar moedas internacionais
                                </button>
                                <button
                                    type="button"
                                    :disabled="importCatalogLoading || syncAllRatesLoading || !form.currencies.length"
                                    class="ep-btn-secondary"
                                    @click="syncAllCurrencyRates"
                                >
                                    <RefreshCw class="h-4 w-4" :stroke-width="1.75" :class="{ 'animate-spin': syncAllRatesLoading }" />
                                    Atualizar todas as taxas
                                </button>
                            </div>
                        </div>
                        <p v-if="catalogActionMessage" class="mt-4 flex items-center gap-2 text-[12.5px] text-[var(--ep-pos)]"><span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />{{ catalogActionMessage }}</p>
                        <p v-if="catalogActionError" class="mt-4 flex items-center gap-2 text-[12.5px] text-[var(--ep-neg)]"><span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />{{ catalogActionError }}</p>
                        <div v-if="rateFetchError" class="mt-4 flex items-start gap-3 rounded-xl border border-[color-mix(in_oklab,var(--ep-warn)_30%,transparent)] bg-[var(--ep-warn-bg)] px-4 py-3 text-[12.5px] text-[var(--ep-text-2)]">
                            <AlertCircle class="mt-px h-4 w-4 shrink-0 text-[var(--ep-warn)]" :stroke-width="1.75" aria-hidden="true" />
                            {{ rateFetchError }}
                        </div>
                        <div class="mt-5 space-y-4">
                            <div class="grid gap-4 sm:grid-cols-1 md:grid-cols-2">
                                <div
                                    v-for="(curr, index) in form.currencies"
                                    :key="index"
                                    class="flex flex-col gap-4 rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4 transition-colors duration-150 hover:border-[var(--ep-line-strong)]"
                                >
                                    <div class="flex flex-wrap items-end gap-3">
                                        <div class="w-24 shrink-0">
                                            <label class="ep-label">Código</label>
                                            <input
                                                v-model="curr.code"
                                                type="text"
                                                class="ep-input font-mono uppercase tracking-wide"
                                                placeholder="BRL"
                                                maxlength="10"
                                                @blur="onCurrencyCodeChange(curr, index)"
                                            />
                                        </div>
                                        <div class="w-20 shrink-0">
                                            <label class="ep-label">Símbolo</label>
                                            <input
                                                v-model="curr.symbol"
                                                type="text"
                                                class="ep-input"
                                                placeholder="R$"
                                            />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <label class="ep-label">Nome</label>
                                            <input
                                                v-model="curr.label"
                                                type="text"
                                                class="ep-input"
                                                placeholder="Real brasileiro"
                                            />
                                        </div>
                                        <button
                                            type="button"
                                            class="ep-btn-ghost ep-btn-icon ml-auto shrink-0 hover:!bg-[var(--ep-neg-bg)] hover:!text-[var(--ep-neg)]"
                                            :aria-label="'Remover moeda ' + (curr.code || 'sem código')"
                                            @click="removeCurrency(index)"
                                        >
                                            <Trash2 class="h-4 w-4" :stroke-width="1.75" />
                                        </button>
                                    </div>
                                    <div class="space-y-3 border-t border-[var(--ep-line)] pt-4">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-[12px] font-medium text-[var(--ep-text-3)]">Formato</span>
                                            <div class="ep-tabs">
                                                <button
                                                    type="button"
                                                    :class="[
                                                        'ep-tab !h-7 !px-2.5 text-[12px] tabular-nums',
                                                        getRateMode(index) === 'brl_to'
                                                            ? 'ep-tab--active'
                                                            : 'border border-transparent',
                                                    ]"
                                                    @click="setRateMode(index, 'brl_to')"
                                                >
                                                    1 BRL = X {{ curr.code || 'moeda' }}
                                                </button>
                                                <button
                                                    type="button"
                                                    :class="[
                                                        'ep-tab !h-7 !px-2.5 text-[12px] tabular-nums',
                                                        getRateMode(index) === 'foreign_to_brl'
                                                            ? 'ep-tab--active'
                                                            : 'border border-transparent',
                                                    ]"
                                                    @click="setRateMode(index, 'foreign_to_brl')"
                                                >
                                                    1 {{ curr.code || 'moeda' }} = X BRL
                                                </button>
                                            </div>
                                        </div>
                                        <div class="flex items-end gap-2">
                                            <div class="min-w-0 flex-1">
                                                <label class="ep-label tabular-nums">
                                                    {{ getRateMode(index) === 'brl_to' ? `1 BRL = X ${curr.code || 'moeda'}` : `1 ${curr.code || 'moeda'} = X BRL` }}
                                                </label>
                                                <input
                                                    v-if="getRateMode(index) === 'brl_to'"
                                                    v-model.number="curr.rate_to_brl"
                                                    type="number"
                                                    step="any"
                                                    min="0"
                                                    class="ep-input tabular-nums"
                                                    :placeholder="curr.code === 'BRL' ? '1' : '0,18'"
                                                />
                                                <input
                                                    v-else
                                                    type="number"
                                                    step="any"
                                                    min="0"
                                                    class="ep-input tabular-nums"
                                                    :placeholder="curr.code === 'BRL' ? '1' : '5,55'"
                                                    :value="inverseRate(curr.rate_to_brl)"
                                                    @input="(e) => setRateFromInverse(curr, e.target.value)"
                                                />
                                            </div>
                                            <button
                                                v-if="canFetchRate(curr)"
                                                type="button"
                                                :disabled="refreshLoadingByIndex[index]"
                                                class="ep-btn-secondary ep-btn-icon !h-[38px] !w-[38px] shrink-0"
                                                title="Buscar taxa atual da API Frankfurter"
                                                @click="fetchRate(curr, index)"
                                            >
                                                <RefreshCw class="h-4 w-4" :stroke-width="1.75" :class="{ 'animate-spin': refreshLoadingByIndex[index] }" />
                                            </button>
                                        </div>
                                        <p v-if="curr.code !== 'BRL' && curr.rate_to_brl > 0" class="text-[12px] tabular-nums text-[var(--ep-text-4)]">
                                            Ex.: 1 {{ curr.code }} ≈ {{ (1 / curr.rate_to_brl).toFixed(2) }} BRL
                                        </p>
                                        <p v-else class="text-[12px] tabular-nums text-[var(--ep-text-4)]">
                                            Ex: 0,18 = 1 BRL equivale a 0,18 USD (ou 1 USD ≈ 5,55 BRL)
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="flex h-12 w-full items-center justify-center gap-2 rounded-2xl border border-dashed border-[var(--ep-line-strong)] text-[13px] font-medium text-[var(--ep-text-2)] transition-colors duration-150 hover:border-[color-mix(in_oklab,var(--ep-accent)_55%,transparent)] hover:bg-[var(--ep-hover)] hover:text-[var(--ep-accent)]"
                                @click="addCurrency"
                            >
                                <Banknote class="h-4 w-4" :stroke-width="1.75" />
                                Adicionar moeda
                            </button>
                        </div>
                    </section>
                </div>
            </Transition>

            <div
                class="sticky bottom-4 z-10 -mx-2 flex items-center justify-end gap-3 rounded-2xl border border-[var(--ep-glass-border)] bg-[var(--ep-drawer)] px-4 py-3 shadow-[var(--ep-shadow-pop)] sm:static sm:mx-0 sm:justify-start sm:rounded-none sm:border-0 sm:bg-transparent sm:px-0 sm:py-0 sm:shadow-none sm:backdrop-blur-none"
            >
                <Button type="submit" class="w-full sm:w-auto" :disabled="form.processing">Salvar alterações</Button>
            </div>
        </form>

        <template v-for="pt in settings_plugin_tabs" :key="pt.id">
            <div v-show="activeTab === pt.id" class="w-full max-w-full space-y-4">
                <component :is="getPluginTabComponent(pt)" v-if="getPluginTabComponent(pt)" />
                <div v-else class="panel-card ep-empty">
                    <AlertCircle class="h-6 w-6 text-[var(--ep-neg)]" :stroke-width="1.75" aria-hidden="true" />
                    <p class="ep-empty__title">Componente do plugin não encontrado: {{ pt.component }}</p>
                </div>
            </div>
        </template>

        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-show="activeTab === 'cron'" class="w-full max-w-full space-y-4">
                <section class="panel-card p-6" aria-labelledby="cfg-cron">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 id="cfg-cron" class="ep-section-title">Cron (agendador)</h2>
                            <p class="mt-1 max-w-2xl text-[12.5px] leading-relaxed text-[var(--ep-text-3)]">
                                Importante para o funcionamento geral da plataforma (envios em lote, tarefas automáticas, reconciliação de pagamentos, carrinho abandonado e outros).
                            </p>
                        </div>
                        <span class="ep-chip" :class="cloud_mode || docker_mode ? 'ep-chip--pos' : 'ep-chip--warn'">
                            <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                            {{ cloud_mode || docker_mode ? 'Automático' : 'Configuração manual' }}
                        </span>
                    </div>
                    <div class="mt-5 space-y-4">
                        <div class="flex items-start gap-3 rounded-xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] px-4 py-3">
                            <Info class="mt-px h-4 w-4 shrink-0 text-[var(--ep-accent)]" :stroke-width="1.75" aria-hidden="true" />
                            <div class="min-w-0">
                                <p class="text-[13px] font-medium text-[var(--ep-text)]">
                                    Aviso importante
                                </p>
                                <p class="mt-1 text-[12.5px] leading-relaxed text-[var(--ep-text-2)]">
                                    Se você estiver usando o modo Cloud ou instalou via Docker, você não precisa configurar o cron manualmente. Só é necessário configurar em hospedagem compartilhada.
                                </p>
                            </div>
                        </div>
                        <div
                            v-if="cloud_mode || docker_mode"
                            class="flex items-start gap-3 rounded-xl border border-[color-mix(in_oklab,var(--ep-pos)_30%,transparent)] bg-[var(--ep-pos-bg)] px-4 py-3"
                        >
                            <CheckCircle2 class="mt-px h-4 w-4 shrink-0 text-[var(--ep-pos)]" :stroke-width="1.75" aria-hidden="true" />
                            <div class="min-w-0">
                                <p class="text-[13px] font-medium text-[var(--ep-text)]">
                                    Modo Cloud / Docker
                                </p>
                                <p class="mt-1 text-[12.5px] leading-relaxed text-[var(--ep-text-2)]">
                                    Se você estiver usando o modo Cloud ou instalou via Docker, o agendador normalmente já fica configurado automaticamente. Só configure manualmente se estiver em hospedagem compartilhada.
                                </p>
                            </div>
                        </div>
                        <div
                            v-else
                            class="flex items-start gap-3 rounded-xl border border-[color-mix(in_oklab,var(--ep-warn)_30%,transparent)] bg-[var(--ep-warn-bg)] px-4 py-3"
                        >
                            <AlertCircle class="mt-px h-4 w-4 shrink-0 text-[var(--ep-warn)]" :stroke-width="1.75" aria-hidden="true" />
                            <div class="min-w-0">
                                <p class="text-[13px] font-medium text-[var(--ep-text)]">
                                    Hospedagem compartilhada
                                </p>
                                <p class="mt-1 text-[12.5px] leading-relaxed text-[var(--ep-text-2)]">
                                    Se você instalou em hospedagem compartilhada, configure um cron job chamando o agendador a cada minuto para manter as rotinas automáticas funcionando.
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-4 lg:grid-cols-2">
                            <div class="flex min-w-0 flex-col rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-5">
                                <div class="flex items-center gap-2.5">
                                    <span class="ep-kpi__icon !h-7 !w-7 !rounded-lg" aria-hidden="true"><Link2 class="h-3.5 w-3.5" :stroke-width="1.75" /></span>
                                    <h3 class="ep-section-title">Cron por URL</h3>
                                </div>
                                <p class="mt-3 text-[12.5px] leading-relaxed text-[var(--ep-text-3)]">
                                    Use em serviços externos (cron-job.org, EasyCron etc.) quando você não tem acesso a SSH/Terminal.
                                </p>

                                <template v-if="cron_url">
                                    <div class="mt-4 flex items-stretch gap-2">
                                        <code class="min-w-0 flex-1 break-all rounded-xl border border-[var(--ep-input-border)] bg-[var(--ep-input)] px-3 py-2 font-mono text-[12px] leading-relaxed text-[var(--ep-text)]">
                                            {{ cron_url }}
                                        </code>
                                        <button
                                            type="button"
                                            class="ep-btn-secondary shrink-0 self-start"
                                            @click="copyToClipboard(cron_url)"
                                        >
                                            <Copy class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                                            Copiar
                                        </button>
                                    </div>
                                    <p class="ep-help">
                                        Configure a URL para ser chamada a cada minuto.
                                    </p>
                                </template>
                                <p v-else class="mt-4 text-[12.5px] leading-relaxed text-[var(--ep-text-2)]">
                                    Para gerar a URL, defina <code class="rounded-md border border-[var(--ep-line)] bg-[var(--ep-input)] px-1.5 py-0.5 font-mono text-[11.5px] text-[var(--ep-text)]">CRON_SECRET</code> no arquivo .env.
                                </p>
                            </div>

                            <div class="flex min-w-0 flex-col rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-5">
                                <div class="flex items-center gap-2.5">
                                    <span class="ep-kpi__icon !h-7 !w-7 !rounded-lg" aria-hidden="true"><Terminal class="h-3.5 w-3.5" :stroke-width="1.75" /></span>
                                    <h3 class="ep-section-title">Cron no Linux (crontab)</h3>
                                </div>
                                <p class="mt-3 text-[12.5px] leading-relaxed text-[var(--ep-text-3)]">
                                    Se você tem acesso ao servidor, adicione uma linha no <code class="rounded-md border border-[var(--ep-line)] bg-[var(--ep-input)] px-1.5 py-0.5 font-mono text-[11.5px] text-[var(--ep-text)]">crontab -e</code>.
                                </p>
                                <pre class="mt-4 overflow-x-auto rounded-xl border border-[var(--ep-input-border)] bg-[var(--ep-input)] px-4 py-3 text-left font-mono text-[12px] leading-relaxed text-[var(--ep-text)]">{{ cronLinuxLine }}</pre>
                                <template v-if="cron_url">
                                    <p class="mt-4 text-[12px] font-medium text-[var(--ep-text-3)]">Alternativa (chamando a URL)</p>
                                    <pre class="mt-2 overflow-x-auto rounded-xl border border-[var(--ep-input-border)] bg-[var(--ep-input)] px-4 py-3 text-left font-mono text-[12px] leading-relaxed text-[var(--ep-text)]">{{ cronCurlLine }}</pre>
                                </template>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </Transition>

        <!-- Aba Notificações push (fora do form) -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-show="activeTab === 'push'" class="w-full max-w-full space-y-4">
                <section class="panel-card p-6" aria-labelledby="cfg-push">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 id="cfg-push" class="ep-section-title">Notificações push do painel</h2>
                            <p class="mt-1 max-w-2xl text-[12.5px] leading-relaxed text-[var(--ep-text-3)]">
                                Chaves VAPID necessárias para enviar notificações push (vendas aprovadas, PIX, boleto) aos usuários do painel.
                                Ao gerar aqui, as chaves são salvas automaticamente no <code class="rounded-md border border-[var(--ep-line)] bg-[var(--ep-input)] px-1.5 py-0.5 font-mono text-[11.5px] text-[var(--ep-text)]">.env</code>
                                (<code class="rounded-md border border-[var(--ep-line)] bg-[var(--ep-input)] px-1.5 py-0.5 font-mono text-[11.5px] text-[var(--ep-text)]">PWA_VAPID_PUBLIC</code> e
                                <code class="rounded-md border border-[var(--ep-line)] bg-[var(--ep-input)] px-1.5 py-0.5 font-mono text-[11.5px] text-[var(--ep-text)]">PWA_VAPID_PRIVATE</code>) — não é preciso copiar nem colar manualmente.
                            </p>
                        </div>
                        <span class="ep-chip" :class="pushVapidConfigured ? 'ep-chip--pos' : 'ep-chip--warn'">
                            <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                            {{ pushVapidConfigured ? 'Ativo' : 'Pendente' }}
                        </span>
                    </div>
                    <div class="mt-5 space-y-4">
                        <div
                            class="rounded-xl border px-4 py-3"
                            :class="pushVapidConfigured
                                ? 'border-[color-mix(in_oklab,var(--ep-pos)_30%,transparent)] bg-[var(--ep-pos-bg)]'
                                : 'border-[color-mix(in_oklab,var(--ep-warn)_30%,transparent)] bg-[var(--ep-warn-bg)]'"
                        >
                            <div class="flex items-start gap-3">
                                <CheckCircle2
                                    v-if="pushVapidConfigured"
                                    class="mt-px h-4 w-4 shrink-0 text-[var(--ep-pos)]"
                                    :stroke-width="1.75"
                                />
                                <AlertCircle
                                    v-else
                                    class="mt-px h-4 w-4 shrink-0 text-[var(--ep-warn)]"
                                    :stroke-width="1.75"
                                />
                                <div class="min-w-0">
                                    <p
                                        class="text-[13px] font-medium text-[var(--ep-text)]"
                                    >
                                        {{ pushVapidConfigured ? 'Chaves VAPID configuradas' : 'Chaves VAPID ausentes ou inválidas' }}
                                    </p>
                                    <p
                                        class="mt-1 text-[12.5px] leading-relaxed text-[var(--ep-text-2)]"
                                    >
                                        <template v-if="pushVapidConfigured">
                                            As chaves estão no <code class="rounded-md border border-[var(--ep-line)] bg-[var(--ep-input)] px-1.5 py-0.5 font-mono text-[11.5px] text-[var(--ep-text)]">.env</code> e o painel já pode registrar inscrições push. Peça aos usuários que ativem notificações no centro de notificações.
                                        </template>
                                        <template v-else>
                                            Sem chaves válidas, o botão de ativar notificações não aparece e nenhum push é enviado.
                                        </template>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="!push_vapid.env_writable && push_vapid.env_exists"
                            class="flex items-start gap-3 rounded-xl border border-[color-mix(in_oklab,var(--ep-neg)_30%,transparent)] bg-[var(--ep-neg-bg)] px-4 py-3"
                        >
                            <AlertCircle class="mt-px h-4 w-4 shrink-0 text-[var(--ep-neg)]" :stroke-width="1.75" />
                            <div class="min-w-0 text-[12.5px] leading-relaxed text-[var(--ep-text-2)]">
                                <p class="text-[13px] font-medium text-[var(--ep-text)]">Sem permissão para gravar o .env</p>
                                <p class="mt-1">
                                    Gere as chaves via SSH com
                                    <code class="rounded-md border border-[var(--ep-line)] bg-[var(--ep-input)] px-1.5 py-0.5 font-mono text-[11.5px] text-[var(--ep-text)]">php artisan pwa:vapid</code>
                                    ou ajuste as permissões do arquivo.
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="docker_mode"
                            class="flex items-start gap-3 rounded-xl border border-[color-mix(in_oklab,var(--ep-accent-2)_30%,transparent)] bg-[color-mix(in_oklab,var(--ep-accent-2)_10%,transparent)] px-4 py-3"
                        >
                            <Info class="mt-px h-4 w-4 shrink-0 text-[var(--ep-accent-2)]" :stroke-width="1.75" aria-hidden="true" />
                            <div class="min-w-0">
                                <p class="text-[13px] font-medium text-[var(--ep-text)]">Ambiente Docker</p>
                                <p class="mt-1 text-[12.5px] leading-relaxed text-[var(--ep-text-2)]">
                                    As chaves são sincronizadas com o worker de filas automaticamente. Ao regenerar, o queue worker será reiniciado.
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="pushVapidResult"
                            class="flex items-center gap-2 rounded-xl border px-4 py-3 text-[12.5px]"
                            :class="pushVapidResult.status === 'success'
                                ? 'border-[color-mix(in_oklab,var(--ep-pos)_30%,transparent)] bg-[var(--ep-pos-bg)] text-[var(--ep-pos)]'
                                : 'border-[color-mix(in_oklab,var(--ep-neg)_30%,transparent)] bg-[var(--ep-neg-bg)] text-[var(--ep-neg)]'"
                        >
                            <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-current" aria-hidden="true" />
                            {{ pushVapidResult.message }}
                        </div>

                        <div class="flex flex-wrap gap-3 border-t border-[var(--ep-line)] pt-5">
                            <Button
                                v-if="!pushVapidConfigured"
                                type="button"
                                :disabled="pushVapidGenerating || !push_vapid.env_writable"
                                @click="generatePushVapid(false)"
                            >
                                <RefreshCw class="h-4 w-4" :stroke-width="1.75" :class="{ 'animate-spin': pushVapidGenerating }" />
                                {{ pushVapidGenerating ? 'Gerando...' : 'Gerar chaves VAPID' }}
                            </Button>
                            <button
                                v-else
                                type="button"
                                :disabled="pushVapidGenerating || !push_vapid.env_writable"
                                class="ep-btn-secondary hover:!border-[color-mix(in_oklab,var(--ep-warn)_45%,transparent)] hover:!text-[var(--ep-warn)]"
                                @click="generatePushVapid(true)"
                            >
                                <RefreshCw class="h-4 w-4" :stroke-width="1.75" :class="{ 'animate-spin': pushVapidGenerating }" />
                                {{ pushVapidGenerating ? 'Regenerando...' : 'Regenerar chaves' }}
                            </button>
                        </div>
                    </div>
                </section>
            </div>
        </Transition>

        <!-- Aba Update (fora do form) -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-show="activeTab === 'update'" class="w-full max-w-full space-y-4">
                <!-- Herói: versão instalada + ações -->
                <section class="panel-card ep-glow-card p-6" aria-labelledby="cfg-update">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div class="min-w-0">
                            <h2 id="cfg-update" class="ep-section-title">Versão e atualizações</h2>
                            <p class="mt-1 max-w-xl text-[12.5px] leading-relaxed text-[var(--ep-text-3)]">
                                Versão atual instalada e verificação de atualizações a partir do repositório oficial.
                            </p>
                            <div class="mt-5">
                                <span class="ep-kpi__label">Versão atual</span>
                                <div class="mt-1 flex flex-wrap items-center gap-3">
                                    <p class="text-[28px] font-semibold leading-none tracking-[-0.03em] tabular-nums text-[var(--ep-text)]">{{ current_version }}</p>
                                    <span
                                        class="ep-chip"
                                        :class="updateCheckResult?.available ? 'ep-chip--accent' : updateCheckResult?.error ? 'ep-chip--warn' : updateCheckResult ? 'ep-chip--pos' : ''"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                                        {{ updateCheckResult?.available ? 'Atualização disponível' : updateCheckResult?.error ? 'Falha na verificação' : updateCheckResult ? 'Em dia' : 'Não verificado' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                :disabled="updateCheckLoading"
                                class="ep-btn-secondary"
                                @click="checkForUpdate"
                            >
                                <RefreshCw class="h-4 w-4" :stroke-width="1.75" :class="{ 'animate-spin': updateCheckLoading }" />
                                {{ updateCheckLoading ? 'Verificando...' : 'Verificar atualização' }}
                            </button>
                            <button
                                v-if="updateCheckResult?.available && updates_enabled && (update_mode === 'git' || archive_ready)"
                                type="button"
                                :disabled="updateRunLoading"
                                class="ep-btn"
                                @click="runUpdate"
                            >
                                <Download class="h-4 w-4" :stroke-width="1.75" :class="{ 'animate-pulse': updateRunLoading }" />
                                {{ updateRunLoading ? 'Atualizando... Aguarde.' : 'Atualizar' }}
                            </button>
                        </div>
                    </div>

                    <div class="mt-6 space-y-4 border-t border-[var(--ep-line)] pt-5">
                        <div
                            class="flex items-start gap-3 rounded-xl border px-4 py-3"
                            :class="update_mode === 'git'
                                ? 'border-[color-mix(in_oklab,var(--ep-pos)_30%,transparent)] bg-[var(--ep-pos-bg)]'
                                : docker_mode
                                    ? 'border-[color-mix(in_oklab,var(--ep-accent-2)_30%,transparent)] bg-[color-mix(in_oklab,var(--ep-accent-2)_10%,transparent)]'
                                    : 'border-[color-mix(in_oklab,var(--ep-accent)_30%,transparent)] bg-[color-mix(in_oklab,var(--ep-accent)_10%,transparent)]'"
                        >
                            <GitBranch
                                class="mt-px h-4 w-4 shrink-0"
                                :stroke-width="1.75"
                                :class="update_mode === 'git' ? 'text-[var(--ep-pos)]' : docker_mode ? 'text-[var(--ep-accent-2)]' : 'text-[var(--ep-accent)]'"
                                aria-hidden="true"
                            />
                            <div class="min-w-0">
                                <p class="text-[13px] font-medium text-[var(--ep-text)]">
                                    {{ update_mode === 'git' ? 'Modo Git (VPS / servidor com terminal)' : docker_mode ? 'Modo Docker (download ZIP no container)' : 'Modo download ZIP (hospedagem compartilhada)' }}
                                </p>
                                <p class="mt-1 text-[12.5px] leading-relaxed text-[var(--ep-text-2)]">
                                    <template v-if="update_mode === 'git'">
                                        Atualização via <code class="rounded-md border border-[var(--ep-line)] bg-[var(--ep-input)] px-1.5 py-0.5 font-mono text-[11.5px] text-[var(--ep-text)]">git pull</code> + Composer + build.
                                    </template>
                                    <template v-else-if="docker_mode">
                                        O código roda em container — o painel baixa a release do GitHub e aplica dentro do container, sem precisar de SSH.
                                        Preserva <code class="rounded-md border border-[var(--ep-line)] bg-[var(--ep-input)] px-1.5 py-0.5 font-mono text-[11.5px] text-[var(--ep-text)]">.docker/</code>,
                                        <code class="rounded-md border border-[var(--ep-line)] bg-[var(--ep-input)] px-1.5 py-0.5 font-mono text-[11.5px] text-[var(--ep-text)]">storage/</code> e uploads.
                                    </template>
                                    <template v-else>
                                        Sem Git no servidor — o painel baixa a release do GitHub e aplica os arquivos, preservando
                                        <code class="rounded-md border border-[var(--ep-line)] bg-[var(--ep-input)] px-1.5 py-0.5 font-mono text-[11.5px] text-[var(--ep-text)]">.env</code>,
                                        <code class="rounded-md border border-[var(--ep-line)] bg-[var(--ep-input)] px-1.5 py-0.5 font-mono text-[11.5px] text-[var(--ep-text)]">storage/</code>,
                                        <code class="rounded-md border border-[var(--ep-line)] bg-[var(--ep-input)] px-1.5 py-0.5 font-mono text-[11.5px] text-[var(--ep-text)]">plugins/</code> e uploads em
                                        <code class="rounded-md border border-[var(--ep-line)] bg-[var(--ep-input)] px-1.5 py-0.5 font-mono text-[11.5px] text-[var(--ep-text)]">public/storage/</code>.
                                    </template>
                                </p>
                                <ul v-if="preflight_warnings.length && update_mode === 'archive'" class="mt-2 list-disc space-y-1 pl-5 text-[12.5px] text-[var(--ep-warn)]">
                                    <li v-for="(warn, idx) in preflight_warnings" :key="idx">{{ warn }}</li>
                                </ul>
                                <p v-else-if="update_mode === 'archive' && !archive_ready" class="mt-2 text-[12.5px] text-[var(--ep-warn)]">
                                    Verifique permissões de escrita na pasta da aplicação e se a extensão PHP Zip está habilitada.
                                </p>
                            </div>
                        </div>
                        <div v-if="updateCheckResult" class="rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4">
                            <p v-if="updateCheckResult.error" class="flex items-center gap-2 text-[12.5px] text-[var(--ep-warn)]"><span class="h-1.5 w-1.5 shrink-0 rounded-full bg-current" aria-hidden="true" />{{ updateCheckResult.error }}</p>
                            <p v-else-if="updateCheckResult.available" class="flex items-center gap-2 text-[13px] font-medium text-[var(--ep-pos)]">
                                <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-current" aria-hidden="true" />
                                <span>Nova versão disponível: <span class="tabular-nums">{{ updateCheckResult.latest }}</span></span>
                            </p>
                            <p v-else-if="updateCheckResult.latest" class="text-[12.5px] text-[var(--ep-text-2)]">
                                Você está na versão mais recente (<span class="tabular-nums">{{ updateCheckResult.latest }}</span>).
                            </p>
                            <p v-else class="text-[12.5px] text-[var(--ep-text-3)]">
                                Nenhuma release encontrada no repositório.
                            </p>
                            <div
                                v-if="updateCheckResult.changelog_remote"
                                class="mt-4 border-t border-[var(--ep-line)] pt-4 text-[12.5px] text-[var(--ep-text-2)]"
                            >
                                <p class="ep-section-title mb-2">O que há de novo na versão {{ updateCheckResult.latest }}</p>
                                <pre class="max-h-72 overflow-y-auto whitespace-pre-wrap font-sans leading-relaxed">{{ updateCheckResult.changelog_remote }}</pre>
                            </div>
                        </div>
                        <div v-if="lastUpdateSteps?.length" class="rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4">
                            <p class="ep-section-title mb-3">Detalhes da última tentativa</p>
                            <div class="space-y-2">
                                <div
                                    v-for="(step, idx) in lastUpdateSteps"
                                    :key="idx"
                                    class="rounded-xl border px-3 py-2.5 text-[12px] text-[var(--ep-text-2)]"
                                    :class="step.ok
                                        ? 'border-[color-mix(in_oklab,var(--ep-pos)_30%,transparent)] bg-[var(--ep-pos-bg)]'
                                        : 'border-[color-mix(in_oklab,var(--ep-neg)_30%,transparent)] bg-[var(--ep-neg-bg)]'"
                                >
                                    <p class="flex items-center gap-2 font-medium text-[var(--ep-text)]">
                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full" :class="step.ok ? 'bg-[var(--ep-pos)]' : 'bg-[var(--ep-neg)]'" aria-hidden="true" />
                                        <span class="min-w-0 flex-1">{{ step.label }}</span>
                                        <span class="shrink-0 text-[11px] font-medium text-[var(--ep-text-3)]">{{ step.ok ? 'OK' : 'Falhou' }}</span>
                                    </p>
                                    <pre v-if="step.output" class="mt-1.5 whitespace-pre-wrap font-mono text-[11.5px]">{{ step.output }}</pre>
                                    <pre v-if="step.error" class="mt-1.5 whitespace-pre-wrap font-mono text-[11.5px] text-[var(--ep-neg)]">{{ step.error }}</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Manutenção: integridade + migrations -->
                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="panel-card flex flex-col p-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-2.5">
                                <span class="ep-kpi__icon !h-7 !w-7 !rounded-lg" aria-hidden="true"><ShieldCheck class="h-3.5 w-3.5" :stroke-width="1.75" /></span>
                                <p class="ep-section-title">Integridade</p>
                            </div>
                            <button
                                type="button"
                                :disabled="integrityLoading"
                                class="ep-btn-secondary"
                                @click="checkIntegrity"
                            >
                                <RefreshCw class="h-4 w-4" :stroke-width="1.75" :class="{ 'animate-spin': integrityLoading }" />
                                {{ integrityLoading ? 'Verificando...' : 'Verificar integridade' }}
                            </button>
                        </div>
                        <div v-if="integrityResult" class="mt-4 border-t border-[var(--ep-line)] pt-4 text-[12.5px]">
                            <p v-if="integrityResult.error" class="flex items-center gap-2 text-[var(--ep-warn)]"><span class="h-1.5 w-1.5 shrink-0 rounded-full bg-current" aria-hidden="true" />{{ integrityResult.error }}</p>
                            <template v-else>
                                <p v-if="integrityResult.pending_count > 0" class="flex items-center gap-2 text-[var(--ep-warn)]">
                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-current" aria-hidden="true" />
                                    <span>Existem <span class="font-semibold tabular-nums">{{ integrityResult.pending_count }}</span> migrations pendentes para rodar.</span>
                                </p>
                                <p v-else class="flex items-center gap-2 text-[var(--ep-pos)]">
                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-current" aria-hidden="true" />
                                    Nenhuma migration pendente.
                                </p>
                                <div v-if="(integrityResult.pending ?? []).length" class="mt-3 rounded-xl border border-[var(--ep-input-border)] bg-[var(--ep-input)] p-3">
                                    <pre class="max-h-56 overflow-y-auto whitespace-pre-wrap font-mono text-[11.5px] text-[var(--ep-text-2)]">{{ (integrityResult.pending ?? []).join('\n') }}</pre>
                                    <p v-if="integrityResult.pending_truncated" class="ep-help">Lista truncada.</p>
                                </div>
                                <p class="ep-help mt-3">
                                    Observação: ao atualizar pelo painel, o sistema já tenta rodar as migrations automaticamente.
                                </p>
                            </template>
                        </div>
                    </div>
                    <div class="panel-card flex flex-col p-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-2.5">
                                <span class="ep-kpi__icon !h-7 !w-7 !rounded-lg" aria-hidden="true"><Database class="h-3.5 w-3.5" :stroke-width="1.75" /></span>
                                <p class="ep-section-title">Migrations</p>
                            </div>
                            <button
                                type="button"
                                :disabled="migrateLoading"
                                class="ep-btn-secondary"
                                @click="runMigrations"
                            >
                                <RefreshCw class="h-4 w-4" :stroke-width="1.75" :class="{ 'animate-spin': migrateLoading }" />
                                {{ migrateLoading ? 'Rodando...' : 'Rodar migrations' }}
                            </button>
                        </div>
                        <div v-if="migrateResult" class="mt-4 border-t border-[var(--ep-line)] pt-4 text-[12.5px]">
                            <p v-if="migrateResult.success" class="flex items-center gap-2 text-[var(--ep-pos)]"><span class="h-1.5 w-1.5 shrink-0 rounded-full bg-current" aria-hidden="true" />{{ migrateResult.message }}</p>
                            <p v-else class="flex items-center gap-2 text-[var(--ep-warn)]"><span class="h-1.5 w-1.5 shrink-0 rounded-full bg-current" aria-hidden="true" />{{ migrateResult.message }}</p>
                            <p v-if="migrateResult.partial && migrateResult.pending > 0" class="mt-1.5 text-[var(--ep-warn)]">
                                Restam <span class="font-semibold tabular-nums">{{ migrateResult.pending }}</span> migrations — clique em "Rodar migrations" novamente.
                            </p>
                            <div v-if="(migrateResult.output ?? '').trim() !== ''" class="mt-3 rounded-xl border border-[var(--ep-input-border)] bg-[var(--ep-input)] p-3">
                                <pre class="max-h-56 overflow-y-auto whitespace-pre-wrap font-mono text-[11.5px] text-[var(--ep-text-2)]">{{ migrateResult.output }}</pre>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-start gap-3 rounded-2xl border border-[color-mix(in_oklab,var(--ep-accent)_28%,transparent)] bg-[color-mix(in_oklab,var(--ep-accent)_8%,transparent)] px-5 py-4">
                    <Info class="mt-px h-4 w-4 shrink-0 text-[var(--ep-accent)]" :stroke-width="1.75" aria-hidden="true" />
                    <div class="min-w-0">
                        <p class="text-[13px] font-medium text-[var(--ep-text)]">Checkout, cartão ou pixels bloqueados?</p>
                        <p class="mt-1 text-[12.5px] leading-relaxed text-[var(--ep-text-2)]">
                            Instalações manuais antigas podem bloquear scripts de pagamento (CajuPay, Google Analytics, Utmify) por CSP.
                            Atualize a plataforma aqui para aplicar a política corrigida. Após atualizar, limpe o cache do navegador no checkout.
                            Domínios extras podem ser definidos no <code class="rounded-md border border-[var(--ep-line)] bg-[var(--ep-input)] px-1.5 py-0.5 font-mono text-[11.5px] text-[var(--ep-text)]">.env</code> com
                            <code class="rounded-md border border-[var(--ep-line)] bg-[var(--ep-input)] px-1.5 py-0.5 font-mono text-[11.5px] text-[var(--ep-text)]">CSP_EXTRA_SCRIPT_SRC</code> e
                            <code class="rounded-md border border-[var(--ep-line)] bg-[var(--ep-input)] px-1.5 py-0.5 font-mono text-[11.5px] text-[var(--ep-text)]">CSP_EXTRA_CONNECT_SRC</code>.
                        </p>
                    </div>
                </div>
                <div v-if="!updates_enabled" class="flex items-start gap-3 rounded-2xl border border-[color-mix(in_oklab,var(--ep-warn)_30%,transparent)] bg-[var(--ep-warn-bg)] px-5 py-4">
                    <AlertCircle class="mt-px h-4 w-4 shrink-0 text-[var(--ep-warn)]" :stroke-width="1.75" aria-hidden="true" />
                    <p class="text-[12.5px] text-[var(--ep-text-2)]">Atualizações pela interface estão desativadas (GETFY_UPDATES_ENABLED).</p>
                </div>
            </div>
        </Transition>

        <!-- Email Provider Sidebar (Teleport para ficar por cima do layout) -->
        <Teleport to="body">
            <EmailProviderSidebar
                :open="sidebarOpen"
                :provider="selectedProvider"
                :form="form"
                :connection-result="connectionResult"
                :send-result="sendResult"
                :connection-testing="connectionTesting"
                :send-test-sending="sendTestSending"
                @close="closeSidebar"
                @test-connection="testConnection"
                @send-test="(email) => { testForm.test_to = email; sendTestEmail(); }"
                @save="saveFromSidebar"
            />
        </Teleport>
    </div>
</template>
