<script setup>
import { computed, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { Copy, Check, ExternalLink } from 'lucide-vue-next';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import Button from '@/components/ui/Button.vue';
import ConversionPixelsForm from '@/components/produtos/ConversionPixelsForm.vue';
import HorizontalScrollTabs from '@/components/ui/HorizontalScrollTabs.vue';
import { mergeConversionPixels } from '@/lib/conversionPixels';

defineOptions({ layout: LayoutInfoprodutor });

const props = defineProps({
    produto: { type: Object, required: true },
    partner_type: { type: String, required: true },
    affiliate_status: { type: String, default: null },
    commission_percent: { type: Number, default: null },
    affiliate: { type: Object, default: null },
    links: { type: Array, default: () => [] },
    can_use_links: { type: Boolean, default: false },
    can_edit_pixels: { type: Boolean, default: false },
    tab: { type: String, default: 'overview' },
});

const activeTab = ref(props.tab || 'overview');

const tabs = computed(() => {
    const items = [{ id: 'overview', label: 'Visão geral' }];
    if (props.partner_type === 'afiliado') {
        items.push({ id: 'links', label: 'Links' });
        items.push({ id: 'pixels', label: 'Pixels' });
    }
    return items;
});

const priceLabel = computed(() => {
    const value = Number(props.produto.price ?? 0);
    const currency = props.produto.currency || 'BRL';
    if (!value) return null;
    try {
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency }).format(value);
    } catch {
        return `R$ ${value.toFixed(2)}`;
    }
});

const pixelsData = mergeConversionPixels(props.affiliate?.affiliate_pixels ?? {});

const pixelsForm = useForm({
    affiliate_pixels: pixelsData,
});

function savePixels() {
    pixelsForm.put(`/parceiro/produtos/${props.produto.id}/pixels`, { preserveScroll: true });
}

const copiedIndex = ref(null);

async function copyUrl(url, index) {
    try {
        await navigator.clipboard.writeText(url);
        copiedIndex.value = index;
        setTimeout(() => {
            copiedIndex.value = null;
        }, 2000);
    } catch {
        // ignore
    }
}

function isPendingAffiliate() {
    return props.partner_type === 'afiliado' && props.affiliate_status === 'pending';
}
</script>

<template>
    <div class="space-y-5">
        <Link href="/parceiro/produtos" class="inline-flex items-center gap-1 text-[12.5px] font-medium text-[var(--ep-text-3)] transition-colors duration-150 hover:text-[var(--ep-text)]">
            ← Meus produtos
        </Link>

        <header class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <h1 class="text-[22px] font-semibold tracking-[-0.025em] text-[var(--ep-text)]">{{ produto.name }}</h1>
                <p class="mt-1 text-[13px] text-[var(--ep-text-3)]">
                    Modo: <span class="capitalize text-[var(--ep-text-2)]">{{ partner_type }}</span> · somente leitura
                    <span v-if="commission_percent != null"> · <span class="tabular-nums text-[var(--ep-text-2)]">{{ commission_percent }}%</span> comissão (líquido)</span>
                </p>
            </div>
            <span
                v-if="affiliate_status"
                class="ep-chip"
                :class="affiliate_status === 'approved' ? 'ep-chip--pos' : 'ep-chip--warn'"
            >
                <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                {{ affiliate_status === 'approved' ? 'Aprovado' : 'Aguardando aprovação' }}
            </span>
        </header>

        <div
            v-if="isPendingAffiliate()"
            class="panel-card flex flex-col items-start gap-3 px-4 py-3 text-[13px] leading-relaxed text-[var(--ep-text-2)] sm:flex-row"
            role="status"
        >
            <span class="ep-chip ep-chip--warn shrink-0">
                <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                Pendente
            </span>
            <p>Sua afiliação está aguardando aprovação do produtor. Após aprovar, você poderá copiar links e configurar pixels.</p>
        </div>

        <HorizontalScrollTabs
            aria-label="Abas do produto"
            nav-class="ep-tabs"
            :bleed="false"
        >
            <button
                v-for="t in tabs"
                :key="t.id"
                type="button"
                class="ep-tab shrink-0"
                :class="activeTab === t.id ? 'ep-tab--active' : ''"
                @click="activeTab = t.id"
            >
                {{ t.label }}
            </button>
        </HorizontalScrollTabs>

        <!-- Visão geral -->
        <div v-show="activeTab === 'overview'" class="panel-card overflow-hidden p-6">
            <div class="grid gap-6 md:grid-cols-[240px_1fr]">
                <div
                    v-if="produto.image_url"
                    class="aspect-square overflow-hidden rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] shadow-[var(--ep-glass-highlight)] md:max-w-[240px]"
                >
                    <img :src="produto.image_url" :alt="produto.name" class="h-full w-full object-cover" />
                </div>
                <div
                    v-else
                    class="flex aspect-square max-h-48 items-center justify-center rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] text-4xl font-semibold text-[var(--ep-text-4)] shadow-[var(--ep-glass-highlight)] md:max-w-[240px]"
                >
                    {{ produto.name?.charAt(0) }}
                </div>
                <dl class="min-w-0 divide-y divide-[var(--ep-line)]">
                    <div class="pb-4">
                        <dt class="text-[12.5px] font-medium text-[var(--ep-text-3)]">Preço</dt>
                        <dd class="mt-1 text-[24px] font-semibold tabular-nums tracking-[-0.03em] text-[var(--ep-text)]">
                            {{ priceLabel || '—' }}
                        </dd>
                    </div>
                    <div v-if="affiliate?.affiliate_code && can_use_links" class="py-4">
                        <dt class="text-[12.5px] font-medium text-[var(--ep-text-3)]">Seu código (ref)</dt>
                        <dd class="mt-1.5">
                            <span class="ep-chip ep-chip--accent font-mono">{{ affiliate.affiliate_code }}</span>
                        </dd>
                    </div>
                    <div v-if="produto.description" class="pt-4">
                        <dt class="text-[12.5px] font-medium text-[var(--ep-text-3)]">Descrição</dt>
                        <dd class="mt-1.5 whitespace-pre-line text-[13.5px] leading-relaxed text-[var(--ep-text-2)]">
                            {{ produto.description }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Links -->
        <div v-show="activeTab === 'links'">
            <div v-if="!can_use_links" class="panel-card ep-empty">
                <p class="ep-empty__title">Links bloqueados</p>
                <p class="ep-empty__text">Links disponíveis após aprovação da afiliação.</p>
            </div>
            <div v-else-if="links.length" class="panel-card ep-data overflow-hidden">
                <div class="px-5 py-4">
                    <h2 class="text-[13px] font-medium text-[var(--ep-text-2)]">Links de divulgação</h2>
                    <p class="mt-0.5 text-[12.5px] text-[var(--ep-text-3)]">Use estes links nas suas campanhas. O parâmetro <code class="rounded-md bg-[var(--ep-active)] px-1 py-0.5 font-mono text-[11.5px] text-[var(--ep-text-2)]">ref</code> identifica suas vendas.</p>
                </div>
                <ul class="divide-y divide-[var(--ep-line)] border-t border-[var(--ep-line)]">
                    <li
                        v-for="(l, i) in links"
                        :key="i"
                        class="flex flex-col gap-3 px-5 py-4 transition-colors duration-150 hover:bg-[var(--ep-hover)] sm:flex-row sm:items-center"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="text-[13px] font-medium text-[var(--ep-text)]">{{ l.label }}</p>
                            <p class="mt-1 break-all font-mono text-[12px] text-[var(--ep-text-3)]">{{ l.url }}</p>
                        </div>
                        <div class="flex shrink-0 flex-wrap gap-2">
                            <button type="button" class="ep-btn-secondary !h-8 !px-3" @click="copyUrl(l.url, i)">
                                <Check v-if="copiedIndex === i" class="h-4 w-4 text-[var(--ep-pos)]" :stroke-width="1.75" aria-hidden="true" />
                                <Copy v-else class="h-4 w-4 text-[var(--ep-text-3)]" :stroke-width="1.75" aria-hidden="true" />
                                {{ copiedIndex === i ? 'Copiado' : 'Copiar' }}
                            </button>
                            <a
                                :href="l.url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="ep-btn-ghost !h-8 !px-3"
                            >
                                <ExternalLink class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                                Abrir
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
            <div v-else class="panel-card ep-empty">
                <p class="ep-empty__title">Sem links adicionais</p>
                <p class="ep-empty__text">Nenhum link adicional configurado para este produto.</p>
            </div>
        </div>

        <!-- Pixels -->
        <div v-show="activeTab === 'pixels'">
            <div v-if="!can_edit_pixels" class="panel-card ep-empty">
                <p class="ep-empty__title">Pixels bloqueados</p>
                <p class="ep-empty__text">Configuração de pixels disponível após aprovação da afiliação.</p>
            </div>
            <div v-else class="panel-card space-y-5 p-6">
                <div>
                    <h2 class="text-[15px] font-semibold tracking-[-0.015em] text-[var(--ep-text)]">Pixels de conversão</h2>
                    <p class="mt-1 text-[13px] text-[var(--ep-text-3)]">
                        Seus pixels substituem os do produtor no checkout acessado com seu link de afiliado.
                    </p>
                </div>
                <ConversionPixelsForm
                    v-model="pixelsForm.affiliate_pixels"
                    :allow-custom-script="false"
                    :allow-gtm="false"
                />
                <div class="flex justify-end border-t border-[var(--ep-line)] pt-5">
                    <Button variant="primary" :disabled="pixelsForm.processing" @click="savePixels">
                        Salvar pixels
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
