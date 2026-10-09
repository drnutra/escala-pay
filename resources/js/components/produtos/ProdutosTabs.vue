<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Package, TicketPercent, Users, Handshake, UserPlus } from 'lucide-vue-next';
import BetaBadge from '@/components/ui/BetaBadge.vue';
import HorizontalScrollTabs from '@/components/ui/HorizontalScrollTabs.vue';

const page = usePage();
const isProdutos = computed(() => {
    const url = page.url;
    return url === '/produtos' || url.startsWith('/produtos?') || url.match(/^\/produtos\/\d+/);
});
const isCupons = computed(() => page.url.startsWith('/produtos/cupons'));
const isAlunos = computed(() => page.url.startsWith('/produtos/alunos'));
const isCoprodutores = computed(() => page.url.startsWith('/produtos/co-produtores'));
const isAfiliadosProgramas = computed(() => page.url.startsWith('/produtos/afiliados-programas'));
</script>

<template>
    <HorizontalScrollTabs aria-label="Abas de produtos" nav-class="ep-tabs">
        <Link
            href="/produtos"
            :class="[
                'ep-tab',
                isProdutos
                    ? 'ep-tab--active [&>svg]:text-[var(--ep-accent)]'
                    : 'border border-transparent',
            ]"
        >
            <Package class="h-4 w-4 shrink-0" :stroke-width="1.75" aria-hidden="true" />
            Produtos
        </Link>
        <Link
            href="/produtos/cupons"
            :class="[
                'ep-tab',
                isCupons
                    ? 'ep-tab--active [&>svg]:text-[var(--ep-accent)]'
                    : 'border border-transparent',
            ]"
        >
            <TicketPercent class="h-4 w-4 shrink-0" :stroke-width="1.75" aria-hidden="true" />
            Cupons
        </Link>
        <Link
            href="/produtos/alunos"
            :class="[
                'ep-tab',
                isAlunos
                    ? 'ep-tab--active [&>svg]:text-[var(--ep-accent)]'
                    : 'border border-transparent',
            ]"
        >
            <Users class="h-4 w-4 shrink-0" :stroke-width="1.75" aria-hidden="true" />
            Alunos
        </Link>
        <Link
            href="/produtos/co-produtores"
            :class="[
                'ep-tab',
                isCoprodutores
                    ? 'ep-tab--active [&>svg]:text-[var(--ep-accent)]'
                    : 'border border-transparent',
            ]"
        >
            <Handshake class="h-4 w-4 shrink-0" :stroke-width="1.75" aria-hidden="true" />
            <span class="flex items-center gap-1.5">
                Co-produtores
                <BetaBadge size="xs" />
            </span>
        </Link>
        <Link
            href="/produtos/afiliados-programas"
            :class="[
                'ep-tab',
                isAfiliadosProgramas
                    ? 'ep-tab--active [&>svg]:text-[var(--ep-accent)]'
                    : 'border border-transparent',
            ]"
        >
            <UserPlus class="h-4 w-4 shrink-0" :stroke-width="1.75" aria-hidden="true" />
            <span class="flex items-center gap-1.5">
                Afiliados
                <BetaBadge size="xs" />
            </span>
        </Link>
    </HorizontalScrollTabs>
</template>
