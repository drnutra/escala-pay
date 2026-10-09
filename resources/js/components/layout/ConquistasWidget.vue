<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { formatCompactCurrency } from '@/lib/utils';

const props = defineProps({
    variant: { type: String, default: 'header' }, // 'header' | 'sidebar' | 'dashboard'
});

const page = usePage();
const progress = computed(() => page.props.achievementsProgress ?? null);

const iconUrl = computed(() => {
    if (!progress.value) return null;
    const curr = progress.value.current_achievement;
    const achievements = progress.value.achievements ?? [];
    const first = achievements[0];
    if (curr?.image) return curr.image;
    if (first?.image) return first.image;
    return null;
});

const isLocked = computed(() => {
    if (!progress.value) return true;
    return progress.value.current_achievement === null;
});

const progressPercent = computed(() => {
    return progress.value?.progress_percent ?? 0;
});

const nextLabel = computed(() => {
    const next = progress.value?.next_achievement;
    if (!next) return null;
    return formatCompactCurrency(next.threshold);
});

const totalLabel = computed(() => {
    const total = progress.value?.total_valid_sales ?? 0;
    return formatCompactCurrency(total);
});

const compactBR = new Intl.NumberFormat('pt-BR', { notation: 'compact', maximumFractionDigits: 1 });
const totalBR = computed(() => compactBR.format(progress.value?.total_valid_sales ?? 0));
const nextBR = computed(() => {
    const next = progress.value?.next_achievement;
    return next ? compactBR.format(next.threshold ?? 0) : null;
});
const remainingBR = computed(() => {
    const next = progress.value?.next_achievement;
    if (!next) return null;
    return compactBR.format(Math.max(0, (next.threshold ?? 0) - (progress.value?.total_valid_sales ?? 0)));
});

const panelNavPrefetch = ['hover', 'click'];
</script>

<template>
    <!-- Card da jornada (sidebar e dashboard mobile) -->
    <Link
        v-if="progress && (props.variant === 'sidebar' || props.variant === 'dashboard')"
        href="/conquistas"
        :prefetch="panelNavPrefetch"
        class="group block rounded-xl border border-[var(--ep-line)] bg-[var(--ep-card)] px-3 py-2.5 shadow-[var(--ep-highlight)] transition-colors duration-150 hover:border-[var(--ep-line-strong)]"
        title="Conquistas"
    >
        <div class="flex items-center gap-2">
            <span
                class="flex h-5 w-5 shrink-0 items-center justify-center overflow-hidden"
                :class="{ 'opacity-50 grayscale': isLocked }"
            >
                <img v-if="iconUrl" :src="iconUrl" alt="" class="h-5 w-5 object-contain" />
            </span>
            <span class="text-[12px] font-medium text-[var(--ep-text-2)]">Jornada de faturamento</span>
        </div>
        <div class="mt-2.5 flex items-baseline justify-between gap-2 tabular-nums">
            <span class="text-[13px] font-semibold text-[var(--ep-text)]">R$ {{ totalBR }}</span>
            <span v-if="nextBR" class="text-[11.5px] text-[var(--ep-text-4)]">meta R$ {{ nextBR }}</span>
        </div>
        <div class="mt-1.5 h-1 w-full overflow-hidden rounded-full bg-[var(--ep-active)]">
            <div
                class="h-full rounded-full bg-[var(--color-primary)] transition-[width] duration-500 ease-[cubic-bezier(0.23,1,0.32,1)]"
                :style="{ width: `${Math.max(progressPercent, progressPercent > 0 ? 2 : 0)}%` }"
            />
        </div>
        <p v-if="remainingBR" class="mt-1.5 text-[11.5px] text-[var(--ep-text-4)]">
            Faltam R$ {{ remainingBR }} para a próxima placa
        </p>
    </Link>
    <Link
        v-else-if="progress"
        href="/conquistas"
        :prefetch="panelNavPrefetch"
        class="group flex shrink-0 items-center gap-3 rounded-full px-2 py-1.5 transition-opacity hover:opacity-90"
        :class="{
            'flex-col items-stretch gap-2 !rounded-2xl !px-4 !py-3': props.variant === 'sidebar',
            'w-full !rounded-2xl px-4 py-3': props.variant === 'dashboard',
        }"
        title="Conquistas"
    >
        <div
            class="flex shrink-0 items-center justify-center overflow-hidden rounded-full"
            :class="[
                props.variant === 'sidebar' ? 'h-12 w-12' : 'h-8 w-8',
                { 'opacity-60 grayscale': isLocked },
            ]"
        >
            <img
                v-if="iconUrl"
                :src="iconUrl"
                alt=""
                :class="[
                    props.variant === 'sidebar' ? 'h-8 w-8' : 'h-7 w-7',
                    'object-contain',
                ]"
            />
        </div>
        <div
            class="min-w-0 flex-1"
            :class="[
                props.variant === 'sidebar' ? 'w-full' : '',
                props.variant === 'dashboard' ? 'w-full' : '',
                props.variant === 'header' ? 'hidden w-[130px] sm:block' : '',
            ]"
        >
            <p
                v-if="props.variant === 'header' || props.variant === 'dashboard'"
                class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-400"
            >
                FATURAMENTO
            </p>
            <div
                class="w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700"
                :class="[
                    props.variant === 'sidebar' || props.variant === 'dashboard' ? 'h-2.5' : 'h-1.5',
                ]"
            >
                <div
                    class="h-full rounded-full bg-[var(--color-primary)] transition-all duration-500"
                    :style="{ width: `${progressPercent}%` }"
                />
            </div>
            <p
                class="mt-1 truncate text-zinc-500 dark:text-zinc-400"
                :class="[
                    props.variant === 'sidebar' || props.variant === 'dashboard' ? 'text-xs' : 'text-[11px]',
                ]"
            >
                {{ totalLabel }}
                <span v-if="nextLabel"> → {{ nextLabel }}</span>
            </p>
        </div>
    </Link>
</template>
