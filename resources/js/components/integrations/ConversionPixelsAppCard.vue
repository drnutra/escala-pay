<script setup>
import { PIXEL_CARD_LOGOS } from '@/lib/conversionPixels';
import { ChevronRight } from 'lucide-vue-next';

defineProps({
    app: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['click']);

function baseStyle(logo) {
    return {
        left: `${logo.left}%`,
        top: `${logo.top}%`,
        zIndex: logo.z,
        '--rot': `${logo.rotate}deg`,
        '--scale': String(logo.scale),
    };
}
</script>

<template>
    <button
        type="button"
        class="pixel-apps-card panel-card group flex h-full w-full flex-col p-1.5 text-left transition-[transform,border-color,box-shadow] duration-200 ease-[var(--ep-ease-out)] hover:-translate-y-0.5 hover:border-[var(--ep-line-strong)] active:scale-[0.99]"
        @click="emit('click')"
    >
        <!-- Palco com os logos flutuando: raio concêntrico (20px − 6px = 14px) -->
        <div
            class="relative h-[104px] shrink-0 overflow-hidden rounded-[14px] border border-[var(--ep-line)] bg-[var(--ep-card-2)]"
        >
            <div
                class="absolute inset-0 bg-[radial-gradient(ellipse_80%_90%_at_72%_18%,color-mix(in_oklab,var(--ep-accent)_22%,transparent),transparent_60%),radial-gradient(ellipse_60%_70%_at_12%_100%,color-mix(in_oklab,var(--ep-accent-2)_16%,transparent),transparent_70%)]"
                aria-hidden="true"
            />
            <div
                class="pointer-events-none absolute left-[18%] top-[78%] h-16 w-24 -translate-x-1/2 -translate-y-1/2 rounded-full bg-[color-mix(in_oklab,var(--ep-accent-2)_14%,transparent)] blur-2xl"
                aria-hidden="true"
            />
            <div
                class="pointer-events-none absolute right-[8%] top-[12%] h-14 w-20 rounded-full bg-[color-mix(in_oklab,var(--ep-accent)_22%,transparent)] blur-2xl transition-opacity duration-500 group-hover:opacity-100 opacity-70"
                aria-hidden="true"
            />

            <div class="relative h-full w-full">
                <div
                    v-for="logo in PIXEL_CARD_LOGOS"
                    :key="logo.id"
                    class="logo-chip absolute transition-transform duration-500 ease-[cubic-bezier(0.34,1.56,0.64,1)] will-change-transform"
                    :data-logo="logo.id"
                    :style="baseStyle(logo)"
                >
                    <img
                        :src="logo.image"
                        :alt="logo.id"
                        class="object-contain drop-shadow-[0_6px_14px_rgba(0,0,0,0.28)] transition-[filter] duration-300 group-hover:drop-shadow-[0_8px_18px_var(--ep-glow)]"
                        :class="logo.id === 'meta' ? 'h-11 w-11 sm:h-12 sm:w-12' : 'h-8 w-8 sm:h-9 sm:w-9'"
                        loading="lazy"
                        @error="($e) => ($e.target.style.opacity = '0.35')"
                    />
                </div>
            </div>

            <span class="ep-chip absolute bottom-2 right-2 !h-5 !px-1.5 text-[10.5px] text-[var(--ep-text-3)]">
                + scripts
            </span>
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

<style scoped>
.logo-chip {
    transform: translate(-50%, -50%) rotate(var(--rot)) scale(var(--scale));
}

/* Hover: cada logo sai numa direção diferente (assimétrico) */
.pixel-apps-card:hover .logo-chip[data-logo='tiktok'] {
    transform: translate(calc(-50% - 10px), calc(-50% + 4px)) rotate(-28deg) scale(calc(var(--scale) * 1.06));
}

.pixel-apps-card:hover .logo-chip[data-logo='google_ads'] {
    transform: translate(calc(-50% - 14px), calc(-50% - 6px)) rotate(-16deg) scale(calc(var(--scale) * 1.05));
}

.pixel-apps-card:hover .logo-chip[data-logo='meta'] {
    transform: translate(calc(-50% + 6px), calc(-50% - 12px)) rotate(2deg) scale(calc(var(--scale) * 1.12));
}

.pixel-apps-card:hover .logo-chip[data-logo='google_analytics'] {
    transform: translate(calc(-50% + 12px), calc(-50% - 8px)) rotate(24deg) scale(calc(var(--scale) * 1.08));
}
</style>
