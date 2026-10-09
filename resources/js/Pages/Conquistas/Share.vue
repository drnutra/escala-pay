<script setup>
import { ref } from 'vue';
import Button from '@/components/ui/Button.vue';
import { formatCompactCurrency } from '@/lib/utils';
import { Copy } from 'lucide-vue-next';

defineOptions({ layout: null });

const props = defineProps({
    achievement: {
        type: Object,
        required: true,
    },
    displayUsername: { type: String, default: null },
    shareUrl: { type: String, default: '' },
});

const copied = ref(false);

function getShareUrl() {
    return typeof window !== 'undefined' ? window.location.href : props.shareUrl || '';
}

function copyLink() {
    copied.value = true;
    const url = getShareUrl();
    try {
        if (navigator.clipboard?.writeText) {
            navigator.clipboard.writeText(url).catch(() => {});
        } else {
            const ta = document.createElement('textarea');
            ta.value = url;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
        }
    } catch (_) {}
}
</script>

<template>
    <div class="relative min-h-screen overflow-hidden bg-[var(--ep-canvas)]">
        <!-- Aurora: a luz atrás do vidro -->
        <div class="ep-aurora" aria-hidden="true" />
        <div
            class="pointer-events-none absolute inset-0 opacity-[0.035]"
            style="background-image: radial-gradient(circle at 1px 1px, var(--ep-text) 1px, transparent 0); background-size: 40px 40px;"
            aria-hidden
        />

        <div class="relative z-10 mx-auto flex min-h-screen max-w-md flex-col items-center justify-center px-6 py-16">
            <!-- Card principal -->
            <div
                class="panel-card ep-glow-card w-full max-w-sm !rounded-[28px]"
            >
                <div class="relative px-10 pb-10 pt-12">
                    <!-- Placa com brilho da marca -->
                    <div class="relative mx-auto flex h-36 w-36 items-center justify-center">
                        <div
                            class="absolute inset-0 rounded-full bg-[radial-gradient(closest-side,color-mix(in_oklab,var(--ep-accent)_55%,transparent),color-mix(in_oklab,var(--ep-accent-2)_25%,transparent)_60%,transparent)] blur-2xl"
                            aria-hidden
                        />
                        <div
                            class="relative flex h-28 w-28 shrink-0 items-center justify-center overflow-hidden rounded-[22px] border border-[color-mix(in_oklab,var(--ep-accent)_45%,transparent)] bg-[linear-gradient(180deg,var(--ep-glass-strong),var(--ep-glass))]"
                        >
                            <img
                                v-if="achievement.image"
                                :src="achievement.image"
                                :alt="achievement.name"
                                class="h-20 w-20 object-contain drop-shadow-[0_6px_16px_rgba(0,0,0,0.3)]"
                            />
                        </div>
                    </div>

                    <!-- Valor -->
                    <p class="mt-7 flex justify-center">
                        <span class="ep-chip ep-chip--accent tabular-nums">
                            <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                            R$ {{ formatCompactCurrency(achievement.threshold ?? 0) }} em vendas
                        </span>
                    </p>

                    <!-- Nome da conquista -->
                    <h1 class="mt-3 text-center text-[26px] font-semibold tracking-[-0.035em] text-[var(--ep-text)] [text-shadow:0_2px_24px_color-mix(in_oklab,var(--ep-glow)_55%,transparent)]">
                        {{ achievement.name }}
                    </h1>

                    <!-- Usuário -->
                    <p v-if="displayUsername" class="mt-3 text-center text-[14px] text-[var(--ep-text-2)]">
                        {{ displayUsername }} conquistou
                    </p>
                    <p v-else class="mt-3 text-center text-[14px] text-[var(--ep-text-3)]">
                        Conquista desbloqueada
                    </p>

                    <!-- Brand -->
                    <div class="mt-8 border-t border-[var(--ep-line)] pt-5">
                        <p class="text-center text-[11px] font-medium uppercase tracking-[0.16em] text-[var(--ep-text-4)]">
                            Getfy
                        </p>
                    </div>
                </div>
            </div>

            <!-- Botão copiar no final -->
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                leave-active-class="transition duration-150 ease-in"
                leave-to-class="opacity-0"
            >
                <div v-if="!copied" class="mt-10">
                    <Button
                        variant="outline"
                        size="sm"
                        class="gap-2 rounded-full px-6"
                        @click="copyLink"
                    >
                        <Copy class="h-4 w-4" :stroke-width="1.75" />
                        Copiar link
                    </Button>
                </div>
            </Transition>
        </div>
    </div>
</template>
