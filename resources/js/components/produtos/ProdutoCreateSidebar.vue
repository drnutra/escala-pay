<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import {
    Smartphone,
    Users,
    BookOpen,
    Link,
    CreditCard,
    ChevronRight,
    X,
    Truck,
} from 'lucide-vue-next';
import { ArrowLeft } from 'lucide-vue-next';
import Button from '@/components/ui/Button.vue';
import Toggle from '@/components/ui/Toggle.vue';
import PluginSlotHost from '@/components/plugins/PluginSlotHost.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    productTypes: { type: Array, default: () => [] },
    billingTypes: { type: Array, default: () => [] },
    exchangeRates: { type: Object, default: () => ({ brl_eur: 0.16, brl_usd: 0.18 }) },
    pluginFormSections: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'success']);

const step = ref(1);
const selectedType = ref(null);

const typeIcons = {
    aplicativo: Smartphone,
    area_membros: Users,
    link: Link,
    link_pagamento: CreditCard,
    produto_fisico: Truck,
};

function iconForType(type) {
    if (type?.icon && typeIcons[type.icon]) {
        return typeIcons[type.icon];
    }
    return typeIcons[type.value] || BookOpen;
}

const form = useForm({
    name: '',
    description: '',
    type: '',
    billing_type: 'one_time',
    price: '',
    currency: 'BRL',
    is_active: true,
    image: null,
    deliverable_link: '',
});

const priceNum = computed(() => parseFloat(form.price) || 0);
const priceEur = computed(() => (priceNum.value * (props.exchangeRates.brl_eur ?? 0.16)).toFixed(2));
const priceUsd = computed(() => (priceNum.value * (props.exchangeRates.brl_usd ?? 0.18)).toFixed(2));

const availableTypes = computed(() =>
    props.productTypes.filter((t) => t.available)
);

function selectType(type) {
    if (!type.available) return;
    selectedType.value = type.value;
    form.type = type.value;
    step.value = 2;
}

function back() {
    step.value = 1;
    selectedType.value = null;
    form.type = '';
}

function close() {
    step.value = 1;
    selectedType.value = null;
    form.reset();
    emit('close');
}

function submit() {
    form.post('/produtos', {
        forceFormData: true,
        onSuccess: () => {
            close();
            emit('success');
        },
    });
}

function onFileChange(e) {
    const file = e.target.files?.[0];
    form.image = file || null;
}

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) {
            step.value = 1;
            selectedType.value = null;
            form.reset();
        }
    }
);
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-[100000] flex justify-end"
            aria-modal="true"
            role="dialog"
            aria-labelledby="sidebar-title"
        >
            <div
                class="ep-scrim fixed inset-0"
                aria-hidden="true"
                @click="close"
            />
            <aside
                class="ep-drawer relative z-[100001] flex h-full w-full max-w-md flex-col sm:w-[440px] sm:rounded-l-[22px]"
                @click.stop
            >
                <div
                    class="flex shrink-0 items-start justify-between gap-3 border-b border-[var(--ep-line)] px-6 pb-4 pt-5"
                >
                    <div class="min-w-0">
                        <p class="text-[11.5px] font-medium tabular-nums text-[var(--ep-text-4)]">
                            Etapa {{ step }} de 2
                        </p>
                        <h2 id="sidebar-title" class="mt-0.5 text-[17px] font-semibold tracking-[-0.02em] text-[var(--ep-text)]">
                            {{ step === 1 ? 'Novo produto' : 'Criar produto' }}
                        </h2>
                    </div>
                    <button
                        type="button"
                        class="ep-btn-ghost ep-btn-icon -mr-2 !h-8 !w-8 !rounded-[10px] text-[var(--ep-text-3)]"
                        aria-label="Fechar"
                        @click="close"
                    >
                        <X class="h-[18px] w-[18px]" :stroke-width="1.75" />
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-5">
                    <!-- Step 1: Tipo -->
                    <div v-if="step === 1" class="space-y-4">
                        <p class="text-[13px] text-[var(--ep-text-3)]">
                            Escolha o tipo de entrega do produto.
                        </p>
                        <div class="grid gap-2.5">
                            <button
                                v-for="t in productTypes"
                                :key="t.value"
                                type="button"
                                :disabled="!t.available"
                                :class="[
                                    'group flex items-center gap-3.5 rounded-[14px] border p-3.5 text-left transition-[border-color,background-color] duration-150',
                                    t.available
                                        ? 'border-[var(--ep-line)] bg-[var(--ep-card-2)] hover:border-[color-mix(in_oklab,var(--ep-accent)_45%,transparent)] hover:bg-[var(--ep-hover)]'
                                        : 'cursor-not-allowed border-[var(--ep-line)] bg-transparent opacity-55',
                                ]"
                                @click="selectType(t)"
                            >
                                <span
                                    class="ep-kpi__icon h-10 w-10 shrink-0 rounded-[12px]"
                                >
                                    <component
                                        :is="iconForType(t)"
                                        class="h-[18px] w-[18px]"
                                        :stroke-width="1.75"
                                    />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[13.5px] font-medium text-[var(--ep-text)]">
                                            {{ t.label }}
                                        </span>
                                        <span
                                            v-if="!t.available"
                                            class="ep-chip ep-chip--warn"
                                        >
                                            Em breve
                                        </span>
                                    </div>
                                    <p class="mt-0.5 text-[12.5px] leading-snug text-[var(--ep-text-3)]">
                                        {{ t.description }}
                                    </p>
                                </div>
                                <ChevronRight
                                    v-if="t.available"
                                    class="h-4 w-4 shrink-0 text-[var(--ep-text-4)] transition-[transform,color] duration-150 group-hover:translate-x-0.5 group-hover:text-[var(--ep-text-2)]"
                                    :stroke-width="1.75"
                                />
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: Formulário -->
                    <form v-else class="space-y-5" @submit.prevent="submit">
                        <div>
                            <button
                                type="button"
                                class="ep-btn-ghost -ml-2 !h-8 !px-2 text-[12.5px]"
                                @click="back"
                            >
                                <ArrowLeft class="h-3.5 w-3.5" :stroke-width="1.75" aria-hidden="true" />
                                Voltar ao tipo
                            </button>
                        </div>
                        <div>
                            <label class="ep-label">
                                Nome *
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="ep-input"
                                placeholder="Ex: Curso de Desenvolvimento Web"
                            />
                            <p v-if="form.errors.name" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">
                                {{ form.errors.name }}
                            </p>
                        </div>
                        <div>
                            <label class="ep-label">
                                Tipo de cobrança
                            </label>
                            <div class="ep-tabs flex w-full">
                                <button
                                    v-for="bt in billingTypes"
                                    :key="bt.value"
                                    type="button"
                                    :class="[
                                        'ep-tab flex-1 justify-center',
                                        form.billing_type === bt.value
                                            ? 'ep-tab--active'
                                            : 'border border-transparent',
                                    ]"
                                    @click="form.billing_type = bt.value"
                                >
                                    {{ bt.label }}
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="ep-label">
                                Descrição
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                class="ep-input"
                                placeholder="Breve descrição do produto"
                            />
                        </div>
                        <div v-if="form.type === 'link'">
                            <label class="ep-label">
                                Link do entregável
                            </label>
                            <input
                                v-model="form.deliverable_link"
                                type="url"
                                class="ep-input"
                                placeholder="https://..."
                            />
                            <p class="ep-help">
                                Enviado por e-mail após a compra.
                            </p>
                            <p v-if="form.errors.deliverable_link" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">
                                {{ form.errors.deliverable_link }}
                            </p>
                        </div>
                        <div>
                            <label class="ep-label">
                                Preço (BRL) *
                            </label>
                            <div class="relative">
                                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[12.5px] font-medium text-[var(--ep-text-3)]" aria-hidden="true">R$</span>
                                <input
                                    v-model="form.price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    required
                                    class="ep-input pl-9 font-medium tabular-nums"
                                    placeholder="0,00"
                                />
                            </div>
                            <p class="ep-help tabular-nums">
                                ≈ € {{ priceEur }} · $ {{ priceUsd }}
                            </p>
                            <p v-if="form.errors.price" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">
                                {{ form.errors.price }}
                            </p>
                        </div>
                        <div>
                            <label class="ep-label">
                                Imagem
                            </label>
                            <div class="rounded-[14px] border border-dashed border-[var(--ep-line-strong)] bg-[var(--ep-card-2)] p-3">
                                <input
                                    type="file"
                                    accept="image/*"
                                    class="block w-full cursor-pointer text-[12.5px] text-[var(--ep-text-3)] file:mr-3 file:h-8 file:cursor-pointer file:rounded-[10px] file:border file:border-solid file:border-[var(--ep-glass-border)] file:bg-[var(--ep-glass-strong)] file:px-3 file:text-[12.5px] file:font-medium file:text-[var(--ep-text)] hover:file:border-[var(--ep-line-strong)]"
                                    @change="onFileChange"
                                />
                                <p class="mt-2 text-[12px] text-[var(--ep-text-4)]">
                                    Exibida em formato quadrado (1:1). Recomendado enviar imagem quadrada.
                                </p>
                            </div>
                            <p v-if="form.image" class="mt-1.5 truncate text-[12px] text-[var(--ep-text-2)]">
                                {{ form.image.name }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2 rounded-[14px] border border-[var(--ep-line)] bg-[var(--ep-card-2)] px-3.5 py-3">
                            <Toggle v-model="form.is_active" label="Produto ativo" />
                        </div>
                        <div v-if="pluginFormSections?.length" class="space-y-2 border-t border-[var(--ep-line)] pt-5">
                            <PluginSlotHost
                                layout="stack"
                                :items="pluginFormSections"
                                :context="{ isCreate: true }"
                            />
                        </div>
                        <div class="sticky bottom-0 -mx-6 -mb-5 flex gap-2 border-t border-[var(--ep-line)] bg-[var(--ep-drawer)] px-6 py-4 backdrop-blur-xl">
                            <Button type="submit" :disabled="form.processing">
                                Criar produto
                            </Button>
                            <Button type="button" variant="outline" @click="close">
                                Cancelar
                            </Button>
                        </div>
                    </form>
                </div>
            </aside>
        </div>
    </Teleport>
</template>
