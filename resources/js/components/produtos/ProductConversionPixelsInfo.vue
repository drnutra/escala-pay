<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { PIXEL_TABS } from '@/lib/conversionPixels';

const props = defineProps({
    integrations: { type: Array, default: () => [] },
});

const grouped = computed(() => {
    const map = {};
    for (const tab of PIXEL_TABS) {
        map[tab.id] = [];
    }
    for (const item of props.integrations || []) {
        const key = item.platform || 'meta';
        if (!map[key]) map[key] = [];
        map[key].push(item);
    }
    return map;
});

const hasAny = computed(() => (props.integrations || []).length > 0);
</script>

<template>
    <div class="space-y-4">
        <p class="text-[12.5px] leading-relaxed text-[var(--ep-text-3)]">
            Os pixels são cadastrados em
            <Link href="/integracoes" class="font-medium text-[var(--ep-accent)] underline-offset-4 hover:underline">Integrações → Pixels e rastreamento</Link>.
            Marque os produtos em cada pixel (como nos webhooks).
        </p>

        <div v-if="hasAny" class="grid gap-3">
            <div
                v-for="tab in PIXEL_TABS"
                :key="tab.id"
                v-show="grouped[tab.id]?.length"
                class="overflow-hidden rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)]"
            >
                <div class="flex items-center justify-between gap-3 border-b border-[var(--ep-line)] px-4 py-3">
                    <h3 class="text-[13px] font-medium text-[var(--ep-text)]">{{ tab.label }}</h3>
                    <span class="text-[12px] tabular-nums text-[var(--ep-text-4)]">{{ grouped[tab.id]?.length || 0 }} {{ grouped[tab.id]?.length === 1 ? 'pixel' : 'pixels' }}</span>
                </div>
                <ul class="divide-y divide-[var(--ep-line)]">
                    <li
                        v-for="item in grouped[tab.id]"
                        :key="item.id"
                        class="flex items-center gap-3 px-4 py-3 transition-colors duration-150 hover:bg-[var(--ep-hover)]"
                    >
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-[13px] font-medium text-[var(--ep-text)]">{{ item.name }}</span>
                            <span class="mt-0.5 block truncate text-[12px] text-[var(--ep-text-3)]">{{ item.summary }}</span>
                        </span>
                        <span
                            class="ep-chip shrink-0"
                            :class="item.is_active ? 'ep-chip--pos' : ''"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                            {{ item.is_active ? 'Ativo' : 'Inativo' }}
                        </span>
                    </li>
                </ul>
            </div>
        </div>

        <p v-else class="ep-empty rounded-2xl border border-dashed border-[var(--ep-line-strong)]">
            <span class="ep-empty__title">Nenhum pixel vinculado a este produto.</span>
            <Link href="/integracoes" class="ep-btn-secondary mt-3 !h-8 !rounded-[10px] !px-3 !text-[12.5px]">
                Configurar em Integrações
            </Link>
        </p>
    </div>
</template>
