<script setup>
import { ref, watch, computed } from 'vue';
import Button from '@/components/ui/Button.vue';
import { X, ChevronUp, ChevronDown, Plus, Trash2 } from 'lucide-vue-next';

const props = defineProps({
    open: { type: Boolean, default: false },
    method: { type: String, default: '' },
    methodLabel: { type: String, default: '' },
    primarySlug: { type: String, default: '' },
    gateways: { type: Array, default: () => [] },
    modelValue: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'update:modelValue', 'save']);

const localList = ref([]);

watch(
    () => [props.open, props.modelValue],
    ([open, val]) => {
        if (open) {
            localList.value = Array.isArray(val) ? [...val] : [];
        }
    },
    { immediate: true }
);

const availableToAdd = computed(() => {
    const inList = new Set([props.primarySlug, ...localList.value]);
    return (props.gateways || []).filter((g) => g.slug && !inList.has(g.slug));
});

function moveUp(index) {
    if (index <= 0) return;
    const arr = [...localList.value];
    [arr[index - 1], arr[index]] = [arr[index], arr[index - 1]];
    localList.value = arr;
}

function moveDown(index) {
    if (index >= localList.value.length - 1) return;
    const arr = [...localList.value];
    [arr[index], arr[index + 1]] = [arr[index + 1], arr[index]];
    localList.value = arr;
}

function remove(index) {
    localList.value = localList.value.filter((_, i) => i !== index);
}

function addGateway(slug) {
    if (!slug) return;
    localList.value = [...localList.value, slug];
}

function save() {
    emit('update:modelValue', [...localList.value]);
    emit('save', [...localList.value]);
    emit('close');
}

function close() {
    emit('close');
}

function gatewayName(slug) {
    const g = (props.gateways || []).find((x) => x.slug === slug);
    return g?.name ?? slug;
}
</script>

<template>
    <Teleport to="body">
        <div
            v-show="open"
            class="fixed inset-0 z-[100000] flex justify-end"
            aria-modal="true"
            role="dialog"
        >
            <div
                class="ep-scrim fixed inset-0"
                aria-hidden="true"
                @click="close"
            />
            <aside
                class="ep-drawer relative flex h-full w-full max-w-md flex-col overflow-hidden sm:rounded-l-[22px]"
            >
                <div
                    class="flex items-start justify-between gap-3 border-b border-[var(--ep-line)] px-6 py-5"
                >
                    <div class="min-w-0">
                        <p class="text-[12px] text-[var(--ep-text-3)]">Gateways de contingência</p>
                        <h2 class="mt-0.5 truncate text-[16px] font-semibold tracking-[-0.02em] text-[var(--ep-text)]">
                            Redundância – {{ methodLabel }}
                        </h2>
                    </div>
                    <button
                        type="button"
                        class="ep-btn-ghost ep-btn-icon -mr-2 shrink-0"
                        aria-label="Fechar"
                        @click="close"
                    >
                        <X class="h-[18px] w-[18px]" stroke-width="1.75" />
                    </button>
                </div>

                <div class="flex flex-1 flex-col overflow-y-auto px-6 py-5">
                    <p class="mb-5 text-[12.5px] leading-relaxed text-[var(--ep-text-3)]">
                        Se o gateway principal falhar, serão tentados os gateways abaixo, na ordem.
                    </p>

                    <div class="flex items-center gap-3 rounded-2xl border border-[color-mix(in_oklab,var(--ep-accent)_32%,transparent)] bg-[color-mix(in_oklab,var(--ep-accent)_9%,transparent)] px-4 py-3">
                        <span class="h-2 w-2 shrink-0 rounded-full bg-[var(--ep-accent)]" aria-hidden="true" />
                        <span class="min-w-0 flex-1 truncate text-[13px] font-medium text-[var(--ep-text)]">
                            {{ gatewayName(primarySlug) || 'Não definido' }}
                        </span>
                        <span class="ep-chip ep-chip--accent shrink-0">Principal</span>
                    </div>

                    <div class="relative mt-2 space-y-2 pl-0">
                        <div
                            v-for="(slug, index) in localList"
                            :key="slug"
                            class="group flex items-center gap-3 rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] py-2 pl-3 pr-2 transition-colors duration-150 hover:border-[var(--ep-line-strong)]"
                        >
                            <span class="flex h-6 min-w-6 shrink-0 items-center justify-center rounded-lg bg-[var(--ep-active)] px-1.5 text-[11.5px] font-semibold tabular-nums text-[var(--ep-text-2)]">
                                {{ index + 1 }}
                            </span>
                            <span class="min-w-0 flex-1 truncate text-[13px] font-medium text-[var(--ep-text)]">
                                {{ gatewayName(slug) }}
                            </span>
                            <div class="flex shrink-0 items-center gap-0.5">
                                <button
                                    type="button"
                                    class="ep-btn-ghost ep-btn-icon !h-8 !w-8 !rounded-[10px] disabled:!opacity-30"
                                    :disabled="index === 0"
                                    aria-label="Subir"
                                    @click="moveUp(index)"
                                >
                                    <ChevronUp class="h-4 w-4" stroke-width="1.75" />
                                </button>
                                <button
                                    type="button"
                                    class="ep-btn-ghost ep-btn-icon !h-8 !w-8 !rounded-[10px] disabled:!opacity-30"
                                    :disabled="index === localList.length - 1"
                                    aria-label="Descer"
                                    @click="moveDown(index)"
                                >
                                    <ChevronDown class="h-4 w-4" stroke-width="1.75" />
                                </button>
                                <button
                                    type="button"
                                    class="ep-btn-ghost ep-btn-icon !h-8 !w-8 !rounded-[10px] hover:!bg-[var(--ep-neg-bg)] hover:!text-[var(--ep-neg)]"
                                    aria-label="Remover"
                                    @click="remove(index)"
                                >
                                    <Trash2 class="h-4 w-4" stroke-width="1.75" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="availableToAdd.length > 0" class="mt-6">
                        <label class="ep-label">
                            Adicionar gateway
                        </label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="g in availableToAdd"
                                :key="g.slug"
                                type="button"
                                class="ep-btn-secondary !h-8 !gap-1.5 !rounded-[10px] !px-3 !text-[12.5px] hover:!border-[color-mix(in_oklab,var(--ep-accent)_45%,transparent)] hover:!text-[var(--ep-accent)]"
                                @click="addGateway(g.slug)"
                            >
                                <Plus class="h-3.5 w-3.5" stroke-width="2" />
                                {{ g.name }}
                            </button>
                        </div>
                    </div>

                    <div v-else-if="localList.length === 0" class="ep-empty mt-6 rounded-2xl border border-dashed border-[var(--ep-line-strong)]">
                        <p class="ep-empty__title">Sem gateway de contingência</p>
                        <p class="ep-empty__text">
                            Nenhum gateway de redundância configurado. Adicione gateways acima quando houver opções disponíveis.
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-[var(--ep-line)] px-6 py-4">
                    <Button variant="outline" @click="close">
                        Cancelar
                    </Button>
                    <Button @click="save">
                        Salvar
                    </Button>
                </div>
            </aside>
        </div>
    </Teleport>
</template>
