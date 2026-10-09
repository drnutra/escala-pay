<script setup>
import { ref, onMounted, onUnmounted, watch, computed } from 'vue';
import { X, Smartphone, Share, Bell } from 'lucide-vue-next';
import { usePwaInstall } from '@/composables/usePwaInstall';
import { usePanelPushSubscribe } from '@/composables/usePanelPushSubscribe';
import { usePage } from '@inertiajs/vue3';

const slug = 'painel';
const {
    installPromptEvent,
    showIosInstructions,
    showNotificationPromptAfterInstall,
    isStandalone,
    isIos,
    isMobile,
    tryGetDismissed,
    dismiss,
    triggerInstall,
    registerListener,
    unregisterListener,
    syncInstallPromptFromWindow,
    openIosInstructions,
} = usePwaInstall(slug);

const { registerAndSubscribe, pushRegistered, lastPushError } = usePanelPushSubscribe();
const page = usePage();
const appName = computed(() => page.props.appSettings?.app_name || 'Getfy');
const pushEnabled = computed(() => !!page.props.push_enabled);

const showBanner = ref(false);
const notificationPromptLoading = ref(false);

const NOTIFICATION_PROMPT_STORAGE_KEY = 'panel_notification_prompt_dismissed';
const NOTIFICATION_PROMPT_COOLDOWN_MS = 7 * 24 * 60 * 60 * 1000; // 7 dias

function wasNotificationPromptDismissedRecently() {
    try {
        const raw = localStorage.getItem(NOTIFICATION_PROMPT_STORAGE_KEY);
        if (!raw) return false;
        const ts = parseInt(raw, 10);
        return Date.now() - ts < NOTIFICATION_PROMPT_COOLDOWN_MS;
    } catch {
        return false;
    }
}

function shouldShowNotificationPromptInStandalone() {
    if (typeof window === 'undefined' || typeof Notification === 'undefined') return false;
    return (
        isStandalone.value &&
        pushEnabled.value &&
        Notification.permission === 'default' &&
        !pushRegistered.value &&
        !wasNotificationPromptDismissedRecently()
    );
}

watch(
    installPromptEvent,
    (e) => {
        if (e && !isStandalone.value && !tryGetDismissed()) {
            showBanner.value = true;
        }
    },
    { immediate: true }
);

async function install() {
    await triggerInstall();
    showBanner.value = false;
}

function closeNotificationPrompt(dismissed = false) {
    if (dismissed) {
        try {
            localStorage.setItem(NOTIFICATION_PROMPT_STORAGE_KEY, Date.now().toString());
        } catch {}
    }
    showNotificationPromptAfterInstall.value = false;
}

async function allowNotifications() {
    if (typeof Notification === 'undefined' || !pushEnabled.value) {
        closeNotificationPrompt();
        return;
    }
    notificationPromptLoading.value = true;
    try {
        const result = await Notification.requestPermission();
        if (result === 'granted') {
            await registerAndSubscribe();
        }
    } catch (_) {}
    notificationPromptLoading.value = false;
    closeNotificationPrompt(false);
}

let iosPromptTimer = null;
let notificationPromptTimer = null;
onMounted(() => {
    if (isStandalone.value) {
        // Atrasar um pouco para o layout estabilizar e pushRegistered (se outro componente chamar) ter valor estável
        notificationPromptTimer = setTimeout(() => {
            if (shouldShowNotificationPromptInStandalone()) {
                showNotificationPromptAfterInstall.value = true;
            }
        }, 400);
        return;
    }
    registerListener();
    syncInstallPromptFromWindow();
    if (installPromptEvent.value && !tryGetDismissed()) {
        showBanner.value = true;
    }
    // iOS: não tem beforeinstallprompt; exibir card "Adicionar à tela inicial" após breve delay
    if (isIos.value && !isStandalone.value && !tryGetDismissed()) {
        iosPromptTimer = setTimeout(() => {
            if (!isStandalone.value && !tryGetDismissed()) {
                openIosInstructions();
            }
        }, 600);
    }
});

onUnmounted(() => {
    if (iosPromptTimer) clearTimeout(iosPromptTimer);
    if (notificationPromptTimer) clearTimeout(notificationPromptTimer);
    unregisterListener();
});
</script>

<template>
    <!-- Banner Android: prompt de instalação fixo no mobile -->
    <Transition
        enter-active-class="transition duration-300 ease-[cubic-bezier(0.23,1,0.32,1)]"
        enter-from-class="translate-y-full opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-full opacity-0"
    >
        <div
            v-if="showBanner && installPromptEvent && !isStandalone"
            class="fixed bottom-0 left-0 right-0 z-[99999] px-3 pb-[max(0.75rem,env(safe-area-inset-bottom))]"
        >
            <div class="ep-modal mx-auto flex max-w-md items-center justify-between gap-3 rounded-[20px] p-3 pl-3.5">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="ep-kpi__icon h-11 w-11 shrink-0 rounded-xl">
                        <Smartphone class="h-5 w-5" :stroke-width="1.75" aria-hidden="true" />
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-[14px] font-semibold tracking-[-0.01em] text-[var(--ep-text)]">Instalar {{ appName }}</p>
                        <p class="truncate text-[12.5px] text-[var(--ep-text-3)]">Acesso rápido pela tela inicial</p>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-1">
                    <button
                        type="button"
                        class="ep-btn"
                        @click="install"
                    >
                        Instalar
                    </button>
                    <button
                        type="button"
                        class="ep-btn-ghost ep-btn-icon shrink-0"
                        aria-label="Fechar"
                        @click="dismiss(); showBanner = false"
                    >
                        <X class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                    </button>
                </div>
            </div>
        </div>
    </Transition>

    <!-- Modal iOS: instruções para adicionar à tela inicial -->
    <Transition
        enter-active-class="transition duration-300 ease-[cubic-bezier(0.23,1,0.32,1)]"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
    >
        <div
            v-if="showIosInstructions && isIos && !isStandalone"
            class="fixed inset-0 z-[99999] flex items-end justify-center p-4 pb-[max(2rem,calc(env(safe-area-inset-bottom)+1rem))] sm:items-center sm:p-6"
        >
            <!-- Backdrop -->
            <div
                class="ep-scrim absolute inset-0"
                aria-hidden="true"
                @click="dismiss"
            />
            <!-- Card -->
            <div
                class="ep-modal relative w-full max-w-md p-6"
            >
                <div class="flex items-start gap-4">
                    <div class="ep-kpi__icon h-11 w-11 shrink-0 rounded-xl">
                        <Share class="h-5 w-5" :stroke-width="1.75" aria-hidden="true" />
                    </div>
                    <div class="min-w-0 flex-1 pr-6">
                        <h3 class="text-[16px] font-semibold tracking-[-0.015em] text-[var(--ep-text)]">
                            Adicionar à tela inicial
                        </h3>
                        <p class="mt-3 text-[13px] leading-relaxed text-[var(--ep-text-3)]">
                            No Safari, toque no ícone <strong class="font-semibold text-[var(--ep-text)]">Compartilhar</strong>
                            (quadrado com seta para cima) na barra inferior.
                        </p>
                        <p class="mt-2 text-[13px] leading-relaxed text-[var(--ep-text-3)]">
                            Em seguida, toque em <strong class="font-semibold text-[var(--ep-text)]">« Adicionar à Tela de Início »</strong>.
                        </p>
                        <button
                            type="button"
                            class="ep-btn mt-6 h-11 w-full"
                            @click="dismiss"
                        >
                            Entendi
                        </button>
                    </div>
                    <button
                        type="button"
                        class="ep-btn-ghost ep-btn-icon absolute right-3 top-3 h-8 w-8"
                        aria-label="Fechar"
                        @click="dismiss"
                    >
                        <X class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                    </button>
                </div>
            </div>
        </div>
    </Transition>

    <!-- Modal: permitir notificações após instalar o PWA -->
    <Transition
        enter-active-class="transition duration-300 ease-[cubic-bezier(0.23,1,0.32,1)]"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
    >
        <div
            v-if="showNotificationPromptAfterInstall && pushEnabled && typeof Notification !== 'undefined'"
            class="fixed inset-0 z-[99999] flex items-center justify-center p-4"
        >
            <div
                class="ep-scrim absolute inset-0"
                aria-hidden="true"
                @click="closeNotificationPrompt(false)"
            />
            <div
                class="ep-modal relative w-full max-w-md p-6"
            >
                <div class="flex items-start gap-4">
                    <div class="ep-kpi__icon h-11 w-11 shrink-0 rounded-xl">
                        <Bell class="h-5 w-5" :stroke-width="1.75" aria-hidden="true" />
                    </div>
                    <div class="min-w-0 flex-1 pr-6">
                        <h3 class="text-[16px] font-semibold tracking-[-0.015em] text-[var(--ep-text)]">
                            Permitir notificações?
                        </h3>
                        <p class="mt-2 text-[13px] leading-relaxed text-[var(--ep-text-3)]">
                            Receba avisos de novas vendas (PIX, boleto, cartão, Apple Pay e Google Pay) no {{ appName }}.
                        </p>
                        <div class="mt-6 flex flex-col gap-2 sm:flex-row sm:justify-end">
                            <button
                                type="button"
                                class="ep-btn-secondary h-11 sm:h-10"
                                :disabled="notificationPromptLoading"
                                @click="closeNotificationPrompt(true)"
                            >
                                Agora não
                            </button>
                            <button
                                type="button"
                                class="ep-btn h-11 sm:h-10"
                                :disabled="notificationPromptLoading"
                                @click="allowNotifications"
                            >
                                {{ notificationPromptLoading ? 'Aguarde...' : 'Permitir' }}
                            </button>
                        </div>
                        <p
                            v-if="lastPushError && Notification.permission === 'granted' && !pushRegistered"
                            class="mt-3 text-[12px] leading-relaxed text-[var(--ep-warn)]"
                        >
                            Não foi possível concluir a ativação agora. Abra o painel de notificações e tente novamente.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="ep-btn-ghost ep-btn-icon absolute right-3 top-3 h-8 w-8"
                        aria-label="Fechar"
                        @click="closeNotificationPrompt(true)"
                    >
                        <X class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>
