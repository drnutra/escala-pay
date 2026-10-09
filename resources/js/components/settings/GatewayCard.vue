<script setup>
import { computed } from 'vue';
import { CreditCard } from 'lucide-vue-next';
import { ChevronRight } from 'lucide-vue-next';
import {
    TooltipRoot,
    TooltipTrigger,
    TooltipContent,
    TooltipPortal,
    TooltipProvider,
} from 'radix-vue';

const props = defineProps({
    gateway: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['click']);

const methodLabels = {
    pix: 'PIX',
    card: 'Cartão',
    boleto: 'Boleto',
    pix_auto: 'Pix Auto',
    pix_parcelado: 'PIX Parcelado',
};

const methods = computed(() =>
    (props.gateway.methods || []).map((m) => methodLabels[m] || m)
);

const imageUrl = computed(() => {
    const img = props.gateway.image;
    if (!img) return null;
    if (img.startsWith('http') || img.startsWith('//')) return img;
    return `/${img.replace(/^\//, '')}`;
});

const countryFlagUrl = computed(() => {
    const custom = props.gateway.country_flag;
    if (custom) return `/images/gateways/paises/${custom.replace(/^\//, '')}`;
    const code = props.gateway.country;
    if (!code) return null;
    return `/images/gateways/paises/${code}.png`;
});

const countryName = computed(() => props.gateway.country_name || null);

const countries = computed(() => {
    const list = props.gateway.countries;
    return Array.isArray(list) && list.length > 0 ? list : null;
});

const hasMultipleCountries = computed(() => countries.value != null);
</script>

<template>
    <button
        type="button"
        class="group relative flex w-full flex-row items-start gap-3.5 rounded-2xl border border-[var(--ep-glass-border)] bg-[linear-gradient(180deg,var(--ep-glass-strong),var(--ep-glass))] p-3.5 text-left shadow-[var(--ep-glass-highlight)] transition-[border-color,box-shadow,transform] duration-150 hover:border-[var(--ep-line-strong)] hover:shadow-[var(--ep-glass-highlight),0_14px_32px_-18px_var(--ep-glow)] active:scale-[0.99]"
        @click="emit('click')"
    >
        <!-- Bandeira(s) do(s) país(es) no canto superior direito -->
        <TooltipProvider :delay-duration="300">
            <TooltipRoot v-if="hasMultipleCountries">
                <TooltipTrigger as-child>
                    <div
                        class="absolute right-3 top-3 z-10 flex shrink-0 items-center -space-x-1"
                        @click.stop
                    >
                        <img
                            v-for="c in countries"
                            :key="c.flag"
                            :src="`/images/gateways/paises/${c.flag.replace(/^\//, '')}`"
                            :alt="c.name"
                            class="h-[18px] w-[18px] rounded-full border border-[var(--ep-glass-border)] object-cover ring-1 ring-[var(--ep-line)]"
                            @error="($e) => ($e.target.style.display = 'none')"
                        />
                    </div>
                </TooltipTrigger>
                <TooltipPortal>
                    <TooltipContent
                        side="bottom"
                        :side-offset="6"
                        class="z-[100001] max-w-[12rem] rounded-xl border border-[var(--ep-glass-border)] bg-[var(--ep-drawer)] px-3 py-2 text-[12.5px] font-medium text-[var(--ep-text)] shadow-[var(--ep-shadow-pop)] backdrop-blur-xl"
                    >
                        <div class="flex flex-col gap-0.5">
                            <span v-for="c in countries" :key="c.flag">{{ c.name }}</span>
                        </div>
                    </TooltipContent>
                </TooltipPortal>
            </TooltipRoot>
            <TooltipRoot v-else-if="countryFlagUrl && countryName">
                <TooltipTrigger as-child>
                    <div
                        class="absolute right-3 top-3 z-10 flex h-[18px] w-[18px] shrink-0 items-center justify-center overflow-hidden rounded-full border border-[var(--ep-glass-border)] bg-[var(--ep-card-2)] ring-1 ring-[var(--ep-line)]"
                        @click.stop
                    >
                        <img
                            :src="countryFlagUrl"
                            :alt="countryName"
                            class="h-full w-full object-cover"
                            @error="($e) => ($e.target.style.display = 'none')"
                        />
                    </div>
                </TooltipTrigger>
                <TooltipPortal>
                    <TooltipContent
                        side="bottom"
                        :side-offset="6"
                        class="z-[100001] rounded-xl border border-[var(--ep-glass-border)] bg-[var(--ep-drawer)] px-3 py-2 text-[12.5px] font-medium text-[var(--ep-text)] shadow-[var(--ep-shadow-pop)] backdrop-blur-xl"
                    >
                        {{ countryName }}
                    </TooltipContent>
                </TooltipPortal>
            </TooltipRoot>
        </TooltipProvider>
        <div
            class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] shadow-[inset_0_1px_0_rgba(255,255,255,0.08)]"
        >
            <img
                v-if="imageUrl"
                :src="imageUrl"
                :alt="gateway.name"
                class="h-full w-full object-cover"
                @error="($e) => ($e.target.style.display = 'none')"
            />
            <CreditCard
                v-else
                class="h-6 w-6 text-[var(--ep-text-4)]"
                :stroke-width="1.75"
                aria-hidden="true"
            />
        </div>
        <div class="flex min-w-0 flex-1 flex-col self-stretch">
            <div class="flex flex-wrap items-center gap-2 pr-12">
                <span class="truncate text-[14px] font-medium tracking-[-0.01em] text-[var(--ep-text)]">
                    {{ gateway.name }}
                </span>
            </div>
            <div class="mt-1.5 flex flex-wrap items-center gap-1">
                <span
                    v-for="method in methods"
                    :key="method"
                    class="ep-chip !h-5 !px-1.5 !text-[11px]"
                >
                    {{ method }}
                </span>
            </div>
            <div class="mt-auto flex items-center justify-between gap-2 pt-2.5">
                <span
                    v-if="gateway.is_connected"
                    class="ep-chip ep-chip--pos"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                    Conectado
                </span>
                <span
                    v-else-if="gateway.is_configured"
                    class="ep-chip ep-chip--warn"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                    Configurado
                </span>
                <span class="ml-auto flex items-center gap-0.5 text-[12px] font-medium text-[var(--ep-text-3)] transition-colors duration-150 group-hover:text-[var(--ep-text)]">
                    Configurar
                    <ChevronRight class="h-3.5 w-3.5 transition-transform duration-150 group-hover:translate-x-0.5" :stroke-width="1.75" aria-hidden="true" />
                </span>
            </div>
        </div>
    </button>
</template>
