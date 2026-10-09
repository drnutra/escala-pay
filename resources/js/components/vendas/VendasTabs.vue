<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { CircleDollarSign, Repeat } from 'lucide-vue-next';
import HorizontalScrollTabs from '@/components/ui/HorizontalScrollTabs.vue';

const page = usePage();
const isVendas = computed(() => {
    const url = page.url;
    return url === '/vendas' || url.startsWith('/vendas?');
});
const isAssinaturas = computed(() => page.url.startsWith('/vendas/assinaturas'));
</script>

<template>
    <HorizontalScrollTabs aria-label="Abas de vendas" nav-class="ep-tabs">
        <Link
            href="/vendas"
            :class="[
                'ep-tab border',
                isVendas ? 'ep-tab--active' : 'border-transparent',
            ]"
        >
            <CircleDollarSign
                class="h-4 w-4 shrink-0"
                :class="isVendas ? 'text-[var(--ep-accent)]' : ''"
                :stroke-width="1.75"
                aria-hidden="true"
            />
            Vendas
        </Link>
        <Link
            href="/vendas/assinaturas"
            :class="[
                'ep-tab border',
                isAssinaturas ? 'ep-tab--active' : 'border-transparent',
            ]"
        >
            <Repeat
                class="h-4 w-4 shrink-0"
                :class="isAssinaturas ? 'text-[var(--ep-accent)]' : ''"
                :stroke-width="1.75"
                aria-hidden="true"
            />
            Assinaturas
        </Link>
    </HorizontalScrollTabs>
</template>
