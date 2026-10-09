<script setup>
import { computed, inject } from 'vue';
import { PanelsTopLeft, Bell } from 'lucide-vue-next';
import { useSidebar } from '@/composables/useSidebar';
import ThemeToggler from '@/components/layout/ThemeToggler.vue';
import UserMenu from '@/components/layout/UserMenu.vue';

defineProps({
    pageTitle: { type: String, default: null },
    pageTitleBadge: { type: String, default: null },
});

const { toggleSidebar, isMobileOpen, isMobile } = useSidebar();

const openNotificationsPanel = inject('openNotificationsPanel', () => {});
const notificationsUnreadCount = inject('notificationsUnreadCount', { value: 0 });
const unreadBadge = computed(() => Math.max(0, notificationsUnreadCount?.value ?? 0));
</script>

<template>
    <header
        class="z-[99998] flex h-16 w-full shrink-0 items-center justify-between gap-4 px-4 md:px-6 lg:px-5 lg:pt-3"
    >
        <div class="flex min-w-0 flex-1 items-center gap-2.5">
            <button
                v-if="isMobile && !isMobileOpen"
                type="button"
                class="-ml-1.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-[var(--ep-text-3)] transition-colors duration-150 hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)]"
                aria-label="Abrir menu"
                @click="toggleSidebar"
            >
                <PanelsTopLeft class="h-[18px] w-[18px]" :stroke-width="1.75" aria-hidden="true" />
            </button>
            <template v-if="pageTitle">
                <h1 class="ep-header-title truncate text-[20px] font-semibold tracking-[-0.025em] text-[var(--ep-text)]">
                    {{ pageTitle }}
                </h1>
                <span
                    v-if="pageTitleBadge"
                    class="shrink-0 truncate max-w-[160px] md:max-w-[220px] rounded-md border border-[var(--ep-line)] bg-[var(--ep-card-2)] px-2 py-0.5 text-[11.5px] font-medium text-[var(--ep-text-2)]"
                    :title="pageTitleBadge"
                >
                    {{ pageTitleBadge }}
                </span>
            </template>
        </div>
        <div class="flex shrink-0 items-center gap-1">
            <ThemeToggler compact />
            <button
                type="button"
                class="relative flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-[var(--ep-text-3)] transition-colors duration-150 hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)]"
                aria-label="Notificações"
                @click="openNotificationsPanel()"
            >
                <Bell class="h-[18px] w-[18px]" :stroke-width="1.75" aria-hidden="true" />
                <span
                    v-if="unreadBadge > 0"
                    class="absolute right-0.5 top-0.5 flex h-[15px] min-w-[15px] items-center justify-center rounded-full bg-[var(--color-primary)] px-1 text-[9.5px] font-semibold tabular-nums text-white ring-2 ring-[var(--ep-surface)]"
                >
                    {{ unreadBadge > 99 ? '99+' : unreadBadge }}
                </span>
            </button>
            <div class="mx-1.5 h-5 w-px bg-[var(--ep-line)]" aria-hidden="true" />
            <UserMenu />
        </div>
    </header>
</template>
