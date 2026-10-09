<script setup>
import { ref, watch, computed } from 'vue';
import { X, Bell, Check, ExternalLink, Loader2 } from 'lucide-vue-next';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import { usePanelPushSubscribe } from '@/composables/usePanelPushSubscribe';
import { pushErrorMessage } from '@/lib/pushSubscription';
import { usePage } from '@inertiajs/vue3';
import Toggle from '@/components/ui/Toggle.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
});

const emit = defineEmits(['update:open', 'unread-count-update']);

const page = usePage();
const pushEnabled = computed(() => !!page.props.push_enabled);
const {
    pushRegistered,
    registerAndSubscribe,
    checkExistingSubscription,
    lastPushError,
    reactivatePush,
} = usePanelPushSubscribe();

// Não acessar Notification no template (é API global; Vue resolve como prop do componente). Tudo via computeds:
const hasNotificationAPI = computed(() => typeof window !== 'undefined' && typeof window.Notification !== 'undefined');
const notificationPermissionDenied = computed(
    () => hasNotificationAPI.value && window.Notification.permission === 'denied'
);
const notificationPermissionGranted = computed(
    () => hasNotificationAPI.value && window.Notification.permission === 'granted'
);
const pushActive = computed(() => notificationPermissionGranted.value && pushRegistered.value);

const pushErrorLabel = computed(() =>
    lastPushError.value ? pushErrorMessage(lastPushError.value) : null,
);

const canReactivatePush = computed(
    () =>
        pushEnabled.value &&
        notificationPermissionGranted.value &&
        !pushActive.value &&
        (lastPushError.value || !pushRegistered.value),
);

// Botão "Ativar": permissão ainda não concedida neste navegador
const canActivatePush = computed(
    () =>
        pushEnabled.value &&
        !pushActive.value &&
        hasNotificationAPI.value &&
        window.Notification.permission !== 'denied' &&
        !canReactivatePush.value,
);

const loading = ref(false);
const notifications = ref([]);
const unreadCount = ref(0);
const pushSubscribed = ref(false);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const activatingPush = ref(false);
const pushPreferences = ref({ pix: true, boleto: true, card: true });
const savingPreferences = ref(false);

async function fetchNotifications() {
    if (!props.open) return;
    loading.value = true;
    try {
        const { data } = await axios.get('/painel/notifications', { params: { per_page: 20 } });
        notifications.value = data.data ?? [];
        unreadCount.value = data.unread_count ?? 0;
        pushSubscribed.value = data.push_subscribed ?? false;
        if (data.push_preferences) {
            pushPreferences.value = {
                pix: data.push_preferences.pix !== false,
                boleto: data.push_preferences.boleto !== false,
                card: data.push_preferences.card !== false,
            };
        }
        meta.value = data.meta ?? { current_page: 1, last_page: 1, total: 0 };
        emit('unread-count-update', unreadCount.value);
    } catch (_) {
        notifications.value = [];
    } finally {
        loading.value = false;
    }
}

async function savePushPreferences() {
    if (!pushSubscribed.value && !pushActive.value) return;
    savingPreferences.value = true;
    try {
        const { data } = await axios.patch('/painel/push-preferences', {
            preferences: pushPreferences.value,
        });
        if (data?.preferences) {
            pushPreferences.value = {
                pix: data.preferences.pix !== false,
                boleto: data.preferences.boleto !== false,
                card: data.preferences.card !== false,
            };
        }
    } catch (_) {}
    savingPreferences.value = false;
}

watch(
    () => props.open,
    async (isOpen) => {
        if (!isOpen) return;
        fetchNotifications();
        checkExistingSubscription({ silent: true }).then((synced) => {
            if (synced && !pushSubscribed.value) {
                pushSubscribed.value = true;
            }
        });
    },
);

function close() {
    emit('update:open', false);
}

async function markRead(notification) {
    if (notification.read_at) return;
    try {
        await axios.patch(`/painel/notifications/${notification.id}/read`);
        notification.read_at = new Date().toISOString();
        unreadCount.value = Math.max(0, unreadCount.value - 1);
        emit('unread-count-update', unreadCount.value);
    } catch (_) {}
}

async function markAllRead() {
    try {
        await axios.post('/painel/notifications/mark-all-read');
        notifications.value.forEach((n) => {
            n.read_at = n.read_at || new Date().toISOString();
        });
        unreadCount.value = 0;
        emit('unread-count-update', 0);
    } catch (_) {}
}

async function clearAllNotifications() {
    const ok = confirm('Tem certeza que deseja limpar todas as notificações?');
    if (!ok) return;
    try {
        await axios.delete('/painel/notifications');
        notifications.value = [];
        unreadCount.value = 0;
        meta.value = { current_page: 1, last_page: 1, total: 0 };
        emit('unread-count-update', 0);
    } catch (_) {}
}

async function openNotification(notification) {
    await markRead(notification);
    if (notification.url) {
        close();
        router.visit(notification.url);
    }
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    const now = new Date();
    const diffMs = now - d;
    const diffMins = Math.floor(diffMs / 60000);
    if (diffMins < 1) return 'Agora';
    if (diffMins < 60) return `${diffMins} min`;
    const diffHours = Math.floor(diffMins / 60);
    if (diffHours < 24) return `${diffHours}h`;
    const diffDays = Math.floor(diffHours / 24);
    if (diffDays < 7) return `${diffDays}d`;
    return d.toLocaleDateString();
}

async function activateNotifications() {
    if (typeof window === 'undefined' || typeof window.Notification === 'undefined' || !pushEnabled.value) return;
    activatingPush.value = true;
    try {
        const result = await window.Notification.requestPermission();
        if (result === 'granted') {
            const success = await registerAndSubscribe();
            if (success) {
                await fetchNotifications();
                pushSubscribed.value = true;
            }
        }
    } catch (_) {}
    activatingPush.value = false;
}

const hasUnread = computed(() => unreadCount.value > 0);

async function reactivateNotifications() {
    activatingPush.value = true;
    try {
        const success = await reactivatePush();
        if (success) {
            await fetchNotifications();
            pushSubscribed.value = true;
        }
    } catch (_) {}
    activatingPush.value = false;
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
            <div
                v-if="open"
                class="fixed inset-0 z-[100000] flex justify-end"
                aria-modal="true"
                role="dialog"
                aria-label="Notificações"
            >
                <div
                    class="ep-scrim absolute inset-0"
                    aria-hidden="true"
                    @click="close"
                />
                <div
                    class="np-panel ep-drawer relative flex h-full w-full max-w-md flex-col sm:max-w-sm"
                    @click.stop
                >
                    <div class="flex shrink-0 items-center justify-between gap-3 px-5 pb-3 pt-4">
                        <div class="flex min-w-0 items-center gap-2.5">
                            <span class="ep-kpi__icon h-8 w-8 shrink-0" aria-hidden="true">
                                <Bell class="h-4 w-4" :stroke-width="1.75" />
                            </span>
                            <h2 class="truncate text-[15px] font-semibold tracking-[-0.015em] text-[var(--ep-text)]">
                                Notificações
                            </h2>
                        </div>
                        <button
                            type="button"
                            class="ep-btn-ghost ep-btn-icon h-8 w-8 shrink-0"
                            aria-label="Fechar"
                            @click="close"
                        >
                            <X class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                        </button>
                    </div>

                    <div class="mx-4 rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4 shadow-[var(--ep-glass-highlight)]">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[13px] font-medium text-[var(--ep-text)]">
                                Notificações push
                            </span>
                            <span
                                v-if="pushActive"
                                class="ep-chip ep-chip--pos"
                            >
                                <Check class="h-3.5 w-3.5" :stroke-width="2" aria-hidden="true" />
                                Ativo
                            </span>
                            <span
                                v-else
                                class="ep-chip"
                            >
                                <span class="h-1.5 w-1.5 rounded-full bg-[var(--ep-text-4)]" aria-hidden="true" />
                                Inativo
                            </span>
                        </div>
                        <p
                            v-if="pushEnabled && !pushActive"
                            class="mt-1.5 text-[12px] leading-relaxed text-[var(--ep-text-3)]"
                        >
                            Receba avisos de vendas e pagamentos no navegador ou no app.
                        </p>
                        <p
                            v-if="pushSubscribed && !pushActive"
                            class="mt-1.5 text-[12px] leading-relaxed text-[var(--ep-text-3)]"
                        >
                            Notificações já estão ativas em outro dispositivo. Para ativar neste, permita no navegador.
                        </p>
                        <button
                            v-if="canActivatePush"
                            type="button"
                            class="ep-btn mt-3 w-full"
                            :disabled="activatingPush"
                            @click="activateNotifications"
                        >
                            {{ activatingPush ? 'Aguarde...' : 'Ativar notificações' }}
                        </button>
                        <p
                            v-else-if="pushEnabled && !pushActive && notificationPermissionDenied"
                            class="mt-2 text-[12px] leading-relaxed text-[var(--ep-text-3)]"
                        >
                            Notificações bloqueadas. Habilite nas configurações do navegador para receber avisos.
                        </p>
                        <p
                            v-else-if="pushEnabled && !pushActive && pushErrorLabel"
                            class="mt-2 text-[12px] leading-relaxed text-[var(--ep-warn)]"
                        >
                            {{ pushErrorLabel }}
                        </p>
                        <button
                            v-if="canReactivatePush"
                            type="button"
                            class="ep-btn-secondary mt-3 w-full"
                            :disabled="activatingPush"
                            @click="reactivateNotifications"
                        >
                            {{ activatingPush ? 'Aguarde...' : 'Reativar notificações' }}
                        </button>
                        <p
                            v-else-if="!pushEnabled"
                            class="mt-2 text-[12px] leading-relaxed text-[var(--ep-text-4)]"
                        >
                            Notificações push não configuradas no servidor (chaves VAPID).
                        </p>

                        <div
                            v-if="pushEnabled && (pushActive || pushSubscribed)"
                            class="mt-3 space-y-2 border-t border-[var(--ep-line)] pt-3"
                        >
                            <p class="text-[12px] font-medium text-[var(--ep-text-3)]">
                                Avisar sobre
                            </p>
                            <Toggle
                                v-model="pushPreferences.pix"
                                label="PIX"
                                :disabled="savingPreferences"
                                @update:model-value="savePushPreferences"
                            />
                            <Toggle
                                v-model="pushPreferences.boleto"
                                label="Boleto"
                                :disabled="savingPreferences"
                                @update:model-value="savePushPreferences"
                            />
                            <Toggle
                                v-model="pushPreferences.card"
                                label="Cartão, Apple Pay e Google Pay"
                                :disabled="savingPreferences"
                                @update:model-value="savePushPreferences"
                            />
                        </div>
                    </div>

                    <div class="mt-3 flex shrink-0 items-center justify-between gap-2 border-b border-[var(--ep-line)] px-5 pb-2.5">
                        <span class="text-[12px] tabular-nums text-[var(--ep-text-3)]">
                            {{ meta.total }} {{ meta.total === 1 ? 'notificação' : 'notificações' }}
                        </span>
                        <div class="-mr-2 flex items-center gap-1">
                            <button
                                v-if="notifications.length > 0"
                                type="button"
                                class="ep-btn-ghost h-8 px-2.5 text-[12px] text-[var(--ep-neg)] hover:text-[var(--ep-neg)]"
                                @click="clearAllNotifications"
                            >
                                Limpar
                            </button>
                            <button
                                v-if="hasUnread"
                                type="button"
                                class="ep-btn-ghost h-8 px-2.5 text-[12px] text-[var(--ep-accent)] hover:text-[var(--ep-accent)]"
                                @click="markAllRead"
                            >
                                Marcar todas como lidas
                            </button>
                        </div>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto">
                        <div
                            v-if="loading"
                            class="ep-empty py-12"
                        >
                            <Loader2 class="h-5 w-5 animate-spin text-[var(--ep-accent)]" :stroke-width="1.75" aria-hidden="true" />
                            <span class="ep-empty__text mt-1">Carregando...</span>
                        </div>
                        <div
                            v-else-if="notifications.length === 0"
                            class="ep-empty py-14"
                        >
                            <span class="mb-1 flex h-11 w-11 items-center justify-center rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] text-[var(--ep-text-4)]" aria-hidden="true">
                                <Bell class="h-5 w-5" :stroke-width="1.75" />
                            </span>
                            <p class="ep-empty__title">Nenhuma notificação</p>
                            <p class="ep-empty__text">Avisos de vendas e pagamentos aparecem aqui.</p>
                        </div>
                        <ul
                            v-else
                            class="space-y-0.5 px-2 py-2"
                        >
                            <li
                                v-for="n in notifications"
                                :key="n.id"
                            >
                                <button
                                    type="button"
                                    class="group flex w-full items-start gap-3 rounded-xl px-3 py-3 text-left transition-colors duration-150 hover:bg-[var(--ep-hover)]"
                                    :class="{ 'bg-[color-mix(in_oklab,var(--ep-accent)_8%,transparent)]': !n.read_at }"
                                    @click="openNotification(n)"
                                >
                                    <span
                                        class="mt-[7px] h-1.5 w-1.5 shrink-0 rounded-full"
                                        :class="n.read_at ? 'bg-transparent' : 'bg-[var(--ep-accent)]'"
                                        aria-hidden="true"
                                    />
                                    <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                                        <div class="flex w-full items-start justify-between gap-2">
                                            <span
                                                class="min-w-0 text-[13px] text-[var(--ep-text)]"
                                                :class="n.read_at ? 'font-medium' : 'font-semibold'"
                                            >
                                                {{ n.title }}
                                            </span>
                                            <ExternalLink
                                                v-if="n.url"
                                                class="mt-0.5 h-3.5 w-3.5 shrink-0 text-[var(--ep-text-4)] transition-colors duration-150 group-hover:text-[var(--ep-text-2)]"
                                                :stroke-width="1.75"
                                                aria-hidden="true"
                                            />
                                        </div>
                                        <p
                                            v-if="n.body"
                                            class="line-clamp-2 text-[12.5px] leading-relaxed text-[var(--ep-text-3)]"
                                        >
                                            {{ n.body }}
                                        </p>
                                        <span class="mt-0.5 text-[11.5px] tabular-nums text-[var(--ep-text-4)]">
                                            {{ formatDate(n.created_at) }}
                                        </span>
                                    </div>
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
@keyframes np-slide-in {
    from {
        transform: translateX(24px);
        opacity: 0.6;
    }
}
.np-panel {
    animation: np-slide-in 280ms cubic-bezier(0.23, 1, 0.32, 1);
}
</style>
