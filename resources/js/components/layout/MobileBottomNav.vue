<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutDashboard, CircleDollarSign, Package, Settings } from 'lucide-vue-next';
import { usePwaInstall } from '@/composables/usePwaInstall';
import { isNavItemActive } from '@/lib/nav';

const page = usePage();
const { isStandalone } = usePwaInstall('painel');
const appSettings = () => page.props.appSettings ?? {};
const logoUrl = () => appSettings().app_logo_icon ?? 'https://cdn.getfy.cloud/collapsed-logo.png';

const navItems = [
    { name: 'Home', href: '/dashboard', icon: LayoutDashboard },
    { name: 'Vendas', href: '/vendas', icon: CircleDollarSign },
    { name: 'Produtos', href: '/produtos', icon: Package },
    { name: 'Ajustes', href: '/configuracoes', icon: Settings },
];

const navVisible = ref(true);
const lastScrollY = ref(0);
const SCROLL_THRESHOLD = 20;
const TOP_THRESHOLD = 80;

function isActive(href) {
    return isNavItemActive(page.url, href);
}

const panelNavPrefetch = ['hover', 'click'];

function onScroll() {
    if (typeof window === 'undefined') return;
    const y = window.scrollY ?? window.pageYOffset;
    if (y <= TOP_THRESHOLD) {
        navVisible.value = true;
    } else if (y > lastScrollY.value && y - lastScrollY.value > SCROLL_THRESHOLD) {
        navVisible.value = false;
        lastScrollY.value = y;
    } else if (y < lastScrollY.value && lastScrollY.value - y > SCROLL_THRESHOLD) {
        navVisible.value = true;
        lastScrollY.value = y;
    }
    lastScrollY.value = y;
}

onMounted(() => {
    lastScrollY.value = typeof window !== 'undefined' ? (window.scrollY ?? window.pageYOffset) : 0;
    window.addEventListener('scroll', onScroll, { passive: true });
});

onUnmounted(() => {
    if (typeof window !== 'undefined') window.removeEventListener('scroll', onScroll);
});
</script>

<template>
    <nav
        v-if="isStandalone"
        class="ep-modal fixed bottom-4 left-4 right-4 z-[99998] mx-auto flex max-w-md items-center justify-between gap-1 rounded-[22px] px-2 py-1.5 lg:hidden transition-transform duration-300 ease-[cubic-bezier(0.23,1,0.32,1)]"
        aria-label="Navegação principal"
        role="navigation"
        :style="{ transform: navVisible ? 'translateY(0)' : 'translateY(calc(100% + 2rem))' }"
    >
        <!-- Home e Vendas -->
        <Link
            v-for="item in navItems.slice(0, 2)"
            :key="item.href"
            :href="item.href"
            :prefetch="panelNavPrefetch"
            :aria-current="isActive(item.href) ? 'page' : undefined"
            :aria-label="item.name"
            class="flex flex-1 flex-col items-center gap-1 rounded-2xl border-0 px-2 py-2 text-left text-[11px] font-medium no-underline transition-colors duration-150 cursor-pointer touch-manipulation"
            :class="
                isActive(item.href)
                    ? 'bg-[var(--ep-active)] text-[var(--ep-accent)] shadow-[inset_0_1px_0_rgba(255,255,255,0.08)] [&>svg]:drop-shadow-[0_0_6px_var(--ep-glow)]'
                    : 'text-[var(--ep-text-3)] hover:text-[var(--ep-text)]'
            "
        >
            <component :is="item.icon" class="h-5 w-5 shrink-0" :stroke-width="1.75" aria-hidden="true" />
            <span>{{ item.name }}</span>
        </Link>

        <!-- Logo central -->
        <Link
            href="/dashboard"
            :prefetch="panelNavPrefetch"
            aria-label="Home"
            class="-mt-7 flex shrink-0 cursor-pointer touch-manipulation flex-col items-center justify-center border-0 bg-transparent px-1 no-underline"
        >
            <span
                class="flex h-14 w-14 items-center justify-center rounded-full border border-[var(--ep-glass-border)] border-t-[var(--ep-glass-border-top)] bg-[linear-gradient(145deg,color-mix(in_oklab,var(--ep-accent)_42%,var(--ep-drawer)),color-mix(in_oklab,var(--ep-accent-2)_30%,var(--ep-drawer)))] shadow-[inset_0_1px_0_rgba(255,255,255,0.3),0_10px_28px_-8px_var(--ep-glow)] backdrop-blur-xl transition-transform duration-150 active:scale-[0.96]"
            >
                <img
                    :src="logoUrl()"
                    alt=""
                    class="h-8 w-8 object-contain drop-shadow-[0_1px_2px_rgba(0,0,0,0.25)]"
                    aria-hidden="true"
                />
            </span>
        </Link>

        <!-- Produtos e Ajustes -->
        <Link
            v-for="item in navItems.slice(2)"
            :key="item.href"
            :href="item.href"
            :prefetch="panelNavPrefetch"
            :aria-current="isActive(item.href) ? 'page' : undefined"
            :aria-label="item.name"
            class="flex flex-1 flex-col items-center gap-1 rounded-2xl border-0 px-2 py-2 text-left text-[11px] font-medium no-underline transition-colors duration-150 cursor-pointer touch-manipulation"
            :class="
                isActive(item.href)
                    ? 'bg-[var(--ep-active)] text-[var(--ep-accent)] shadow-[inset_0_1px_0_rgba(255,255,255,0.08)] [&>svg]:drop-shadow-[0_0_6px_var(--ep-glow)]'
                    : 'text-[var(--ep-text-3)] hover:text-[var(--ep-text)]'
            "
        >
            <component :is="item.icon" class="h-5 w-5 shrink-0" :stroke-width="1.75" aria-hidden="true" />
            <span>{{ item.name }}</span>
        </Link>
    </nav>
</template>
