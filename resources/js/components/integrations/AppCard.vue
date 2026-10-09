<script setup>
import { computed } from 'vue';
import { Zap, ChevronRight } from 'lucide-vue-next';

const props = defineProps({
    app: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['click']);

const imageUrl = computed(() => {
    const img = props.app.image;
    if (!img) return null;
    if (img.startsWith('http') || img.startsWith('//')) return img;
    return `/${img.replace(/^\//, '')}`;
});
</script>

<template>
    <button
        type="button"
        class="panel-card group flex h-full w-full flex-col p-1.5 text-left transition-[transform,border-color,box-shadow] duration-200 ease-[var(--ep-ease-out)] hover:-translate-y-0.5 hover:border-[var(--ep-line-strong)] active:scale-[0.99]"
        @click="emit('click')"
    >
        <!-- Palco do logo: raio concêntrico (card 20px − 6px = 14px) -->
        <div
            class="relative flex shrink-0 items-center justify-center overflow-hidden rounded-[14px] border border-[var(--ep-line)] bg-[var(--ep-card-2)]"
            :class="app.imageCover ? 'h-[104px] p-3' : 'h-[104px]'"
        >
            <div
                class="pointer-events-none absolute left-1/2 top-1/2 h-24 w-24 -translate-x-1/2 -translate-y-1/2 rounded-full bg-[radial-gradient(closest-side,color-mix(in_oklab,var(--ep-accent)_30%,transparent),transparent)] opacity-60 blur-xl transition-opacity duration-300 group-hover:opacity-100"
                aria-hidden="true"
            />
            <div
                v-if="imageUrl"
                class="relative flex items-center justify-center overflow-hidden border border-[var(--ep-glass-border)] shadow-[var(--ep-glass-highlight),0_10px_28px_-12px_var(--ep-glow)] transition-transform duration-300 ease-[var(--ep-ease-out)] group-hover:scale-[1.04]"
                :class="
                    app.imageCover
                        ? 'size-full rounded-[12px] bg-white p-2'
                        : 'h-14 w-14 rounded-[16px] bg-[var(--ep-glass-strong)] p-[3px]'
                "
            >
                <img
                    :src="imageUrl"
                    :alt="app.name"
                    class="max-h-full max-w-full object-contain"
                    :class="app.imageCover ? '' : 'size-full rounded-[13px] object-cover'"
                    @error="($e) => ($e.target.style.display = 'none')"
                />
            </div>
            <Zap
                v-else
                class="relative h-6 w-6 text-[var(--ep-accent)]"
                :stroke-width="1.75"
                aria-hidden="true"
            />
        </div>

        <div class="flex flex-1 flex-col px-3.5 pb-3 pt-3.5">
            <div class="text-[14px] font-semibold tracking-[-0.01em] text-[var(--ep-text)]">
                {{ app.name }}
            </div>
            <p
                v-if="app.description"
                class="mt-1 line-clamp-2 text-[12.5px] leading-[1.5] text-[var(--ep-text-3)]"
            >
                {{ app.description }}
            </p>
            <div class="mt-auto flex items-center gap-2 pt-4">
                <span
                    v-if="app.status"
                    class="ep-chip"
                    :class="app.status === 'active' ? 'ep-chip--pos' : ''"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                    {{ app.status === 'active' ? 'Conectado' : app.status }}
                </span>
                <span
                    class="ep-btn-secondary ml-auto !h-8 !rounded-[10px] !px-3 text-[12.5px] transition-colors group-hover:border-[var(--ep-line-strong)]"
                    aria-hidden="true"
                >
                    {{ app.status === 'active' ? 'Gerenciar' : 'Configurar' }}
                    <ChevronRight class="h-3.5 w-3.5 text-[var(--ep-text-3)] transition-transform duration-200 group-hover:translate-x-0.5" :stroke-width="1.75" />
                </span>
            </div>
        </div>
    </button>
</template>
