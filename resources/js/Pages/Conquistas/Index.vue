<script setup>
import { useForm } from '@inertiajs/vue3';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import Button from '@/components/ui/Button.vue';
import { formatCompactCurrency } from '@/lib/utils';
import { Share2, Loader2 } from 'lucide-vue-next';

defineOptions({ layout: LayoutInfoprodutor });

const props = defineProps({
    progress: {
        type: Object,
        required: true,
        default: () => ({}),
    },
    username: { type: String, default: null },
});

function getShareUrl(slug) {
    const base = window.location.origin + `/conquistas/${slug}/share`;
    const u = props.username ? props.username.replace(/^@/, '') : '';
    return u ? `${base}?u=${encodeURIComponent(u)}` : base;
}

function shareAchievement(slug) {
    const url = getShareUrl(slug);
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
    window.open(url, '_blank', 'noopener');
}

const usernameForm = useForm({
    username: props.username ?? '',
});

function saveUsername() {
    usernameForm.put('/meu-perfil/username', { preserveScroll: true });
}
</script>

<template>
    <div class="mx-auto max-w-5xl space-y-5">
        <header class="min-w-0">
            <h1 class="text-[22px] font-semibold tracking-[-0.025em] text-[var(--ep-text)]">
                Conquistas
            </h1>
            <p class="mt-1 text-[13px] text-[var(--ep-text-3)]">
                Sua evolução baseada em vendas processadas por gateways de pagamento.
            </p>
        </header>

        <!-- Resumo (herói) -->
        <section class="panel-card ep-glow-card p-6" aria-labelledby="conquistas-resumo">
            <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 id="conquistas-resumo" class="text-[13px] font-medium text-[var(--ep-text-2)]">Vendas válidas</h2>
                        <span class="ep-chip ep-chip--accent">
                            <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                            {{ progress.current_achievement?.name ?? 'Primeira placa a caminho' }}
                        </span>
                    </div>
                    <p class="mt-4 flex items-baseline gap-2 text-[var(--ep-text)]">
                        <span class="text-[15px] font-medium text-[var(--ep-text-3)]">R$</span>
                        <span class="text-[clamp(36px,3.4vw,46px)] font-semibold leading-none tracking-[-0.045em] tabular-nums [text-shadow:0_2px_24px_color-mix(in_oklab,var(--ep-glow)_60%,transparent)]">{{ formatCompactCurrency(progress.total_valid_sales ?? 0) }}</span>
                    </p>
                    <p class="mt-3 text-[12.5px] text-[var(--ep-text-3)]">Pedidos pagos e confirmados pelos gateways contam para a próxima placa.</p>
                </div>
                <div v-if="progress.next_achievement" class="w-full md:max-w-sm">
                    <div class="flex items-baseline justify-between gap-3 text-[12px]">
                        <span class="text-[var(--ep-text-3)]">Progresso</span>
                        <span class="font-semibold tabular-nums text-[var(--ep-text)]">{{ progress.progress_percent ?? 0 }}%</span>
                    </div>
                    <div class="mt-2 h-2.5 w-full overflow-hidden rounded-full bg-[var(--ep-active)] shadow-[inset_0_1px_2px_rgba(0,0,0,0.18)]">
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-[var(--ep-accent)] to-[var(--ep-accent-2)] transition-[width] duration-700 ease-[cubic-bezier(0.23,1,0.32,1)]"
                            :style="{ width: `${progress.progress_percent ?? 0}%` }"
                        />
                    </div>
                    <p class="mt-2.5 text-[12px] text-[var(--ep-text-3)]">
                        Próximo: <span class="font-medium text-[var(--ep-text)]">{{ progress.next_achievement?.name }}</span> <span class="tabular-nums text-[var(--ep-text-4)]">(R$ {{ formatCompactCurrency(progress.next_achievement?.threshold ?? 0) }})</span>
                    </p>
                </div>
            </div>
        </section>

        <!-- Trilho de placas -->
        <section aria-labelledby="conquistas-trilho">
            <div class="mb-3 flex items-center justify-between gap-3 px-1">
                <h2 id="conquistas-trilho" class="ep-section-title">Trilho de placas</h2>
                <span class="text-[12px] tabular-nums text-[var(--ep-text-4)]">{{ (progress.achievements ?? []).filter((x) => x.unlocked).length }} de {{ (progress.achievements ?? []).length }} desbloqueadas</span>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="a in (progress.achievements ?? [])"
                    :key="a.slug"
                    class="panel-card relative overflow-hidden transition-[opacity,border-color] duration-200"
                    :class="(progress.current_achievement?.slug ?? progress.next_achievement?.slug) === a.slug
                        ? 'ep-glow-card'
                        : a.unlocked
                            ? 'hover:border-[var(--ep-line-strong)]'
                            : 'opacity-60 saturate-50'"
                >
                    <span
                        class="ep-chip ep-chip--accent absolute right-3 top-3"
                        :class="(progress.current_achievement?.slug ?? progress.next_achievement?.slug) === a.slug ? '' : '!hidden'"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                        {{ a.unlocked ? 'Atual' : 'Próxima' }}
                    </span>
                    <div class="flex flex-col items-center px-5 pb-5 pt-10 text-center">
                        <div
                            class="relative flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl border bg-[var(--ep-card-2)]"
                            :class="[
                                { grayscale: !a.unlocked },
                                (progress.current_achievement?.slug ?? progress.next_achievement?.slug) === a.slug
                                    ? 'border-[color-mix(in_oklab,var(--ep-accent)_45%,transparent)]'
                                    : 'border-[var(--ep-line)] shadow-[var(--ep-glass-highlight)]',
                            ]"
                        >
                            <img
                                v-if="a.image"
                                :src="a.image"
                                :alt="a.name"
                                class="h-14 w-14 object-contain drop-shadow-[0_4px_12px_rgba(0,0,0,0.25)]"
                            />
                        </div>
                        <h3 class="mt-4 text-[14px] font-semibold tracking-[-0.015em] text-[var(--ep-text)]">{{ a.name }}</h3>
                        <p
                            class="mt-1.5 text-[12px]"
                            :class="a.unlocked ? 'text-[var(--ep-pos)]' : 'tabular-nums text-[var(--ep-text-3)]'"
                        >
                            <template v-if="a.unlocked">
                                Desbloqueado
                            </template>
                            <template v-else>
                                R$ {{ formatCompactCurrency(a.threshold) }} em vendas válidas
                            </template>
                        </p>
                        <Button
                            v-if="a.unlocked"
                            variant="outline"
                            size="sm"
                            class="mt-4"
                            @click="shareAchievement(a.slug)"
                        >
                            <Share2 class="h-4 w-4" :stroke-width="1.75" />
                            Compartilhar
                        </Button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Username para compartilhar -->
        <section class="panel-card overflow-hidden" aria-labelledby="conquistas-username">
            <div class="border-b border-[var(--ep-line)] px-6 py-4">
                <h2 id="conquistas-username" class="text-[15px] font-semibold tracking-[-0.015em] text-[var(--ep-text)]">Nome para compartilhar</h2>
                <p class="mt-1 text-[13px] text-[var(--ep-text-3)]">
                    Configure um @ para aparecer nas imagens de conquistas compartilhadas.
                </p>
            </div>
            <form class="flex flex-col gap-4 p-6 sm:flex-row sm:items-end" @submit.prevent="saveUsername">
                <div class="min-w-0 flex-1">
                    <label for="username" class="ep-label">@username</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[13.5px] text-[var(--ep-text-4)]" aria-hidden="true">@</span>
                        <input
                            id="username"
                            v-model="usernameForm.username"
                            type="text"
                            placeholder="meunome"
                            maxlength="64"
                            class="ep-input !pl-7"
                        />
                    </div>
                    <p v-if="usernameForm.errors.username" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">
                        {{ usernameForm.errors.username }}
                    </p>
                </div>
                <Button type="submit" :disabled="usernameForm.processing">
                    <Loader2 v-if="usernameForm.processing" class="h-4 w-4 animate-spin" :stroke-width="1.75" />
                    Salvar
                </Button>
            </form>
        </section>
    </div>
</template>
