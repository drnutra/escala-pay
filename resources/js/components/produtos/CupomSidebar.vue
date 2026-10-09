<script setup>
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { X, ChevronDown, Check } from 'lucide-vue-next';
import Button from '@/components/ui/Button.vue';
import Toggle from '@/components/ui/Toggle.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    produtos: { type: Array, default: () => [] },
    coupon: { type: Object, default: null },
});

const emit = defineEmits(['close', 'success']);

const isEdit = computed(() => !!props.coupon);
const productsDropdownOpen = ref(false);

const form = useForm({
    code: '',
    type: 'percent',
    value: '',
    product_ids: [],
    min_amount: '',
    max_uses: '',
    valid_from: '',
    valid_until: '',
    is_active: true,
});

function toggleProduct(id) {
    const idx = form.product_ids.indexOf(id);
    if (idx === -1) {
        form.product_ids = [...form.product_ids, id];
    } else {
        form.product_ids = form.product_ids.filter((pid) => pid !== id);
    }
}

function isProductSelected(id) {
    return form.product_ids.includes(id);
}

const productsLabel = computed(() => {
    if (!form.product_ids.length) return 'Todos os produtos';
    if (form.product_ids.length === props.produtos.length) return 'Todos os produtos';
    if (form.product_ids.length === 1) {
        const p = props.produtos.find((x) => x.id === form.product_ids[0]);
        return p ? p.name : '1 produto';
    }
    return `${form.product_ids.length} produtos selecionados`;
});

function closeProductsDropdown() {
    productsDropdownOpen.value = false;
}

function handleClickOutsideProducts(event) {
    const el = document.querySelector('[data-cupom-products-dropdown]');
    if (el && !el.contains(event.target)) closeProductsDropdown();
}

onMounted(() => {
    document.addEventListener('click', handleClickOutsideProducts);
});
onUnmounted(() => {
    document.removeEventListener('click', handleClickOutsideProducts);
});

function close() {
    form.reset();
    emit('close');
}

function submit() {
    const payload = {
        code: form.code,
        type: form.type,
        value: parseFloat(form.value) || 0,
        product_ids: form.product_ids,
        min_amount: form.min_amount ? parseFloat(form.min_amount) : null,
        max_uses: form.max_uses ? parseInt(form.max_uses, 10) : null,
        valid_from: form.valid_from || null,
        valid_until: form.valid_until || null,
        is_active: form.is_active,
    };
    if (isEdit.value) {
        form.transform(() => payload).put(`/produtos/cupons/${props.coupon.id}`, {
            onSuccess: () => {
                close();
                emit('success');
            },
        });
    } else {
        form.transform(() => payload).post('/produtos/cupons', {
            onSuccess: () => {
                close();
                emit('success');
            },
        });
    }
}

watch(
    () => [props.open, props.coupon],
    () => {
        if (props.open && props.coupon) {
            form.code = props.coupon.code;
            form.type = props.coupon.type;
            form.value = String(props.coupon.value ?? '');
            form.product_ids = Array.isArray(props.coupon.product_ids) ? [...props.coupon.product_ids] : (props.coupon.product_id ? [props.coupon.product_id] : []);
            form.min_amount = props.coupon.min_amount != null ? String(props.coupon.min_amount) : '';
            form.max_uses = props.coupon.max_uses != null ? String(props.coupon.max_uses) : '';
            form.valid_from = props.coupon.valid_from ? props.coupon.valid_from.slice(0, 16) : '';
            form.valid_until = props.coupon.valid_until ? props.coupon.valid_until.slice(0, 16) : '';
            form.is_active = !!props.coupon.is_active;
        } else if (props.open && !props.coupon) {
            form.reset();
            form.type = 'percent';
            form.is_active = true;
            form.product_ids = [];
        }
    },
    { immediate: true }
);
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-[100000] flex justify-end"
            aria-modal="true"
            role="dialog"
            :aria-labelledby="isEdit ? 'sidebar-edit-cupom' : 'sidebar-new-cupom'"
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
                        <p class="text-[11.5px] font-medium text-[var(--ep-text-4)]">Cupom de desconto</p>
                        <h2 :id="isEdit ? 'sidebar-edit-cupom' : 'sidebar-new-cupom'" class="mt-0.5 text-[17px] font-semibold tracking-[-0.02em] text-[var(--ep-text)]">
                            {{ isEdit ? 'Editar cupom' : 'Novo cupom' }}
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
                    <form class="space-y-5" @submit.prevent="submit">
                        <div>
                            <label class="ep-label">
                                Código *
                            </label>
                            <input
                                v-model="form.code"
                                type="text"
                                required
                                class="ep-input font-mono tracking-[0.03em]"
                                placeholder="Ex: PROMO20"
                            />
                            <p v-if="form.errors.code" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">
                                {{ form.errors.code }}
                            </p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="ep-label">
                                    Tipo *
                                </label>
                                <select
                                    v-model="form.type"
                                    required
                                    class="ep-input"
                                >
                                    <option value="percent">Percentual (%)</option>
                                    <option value="fixed">Valor fixo (R$)</option>
                                </select>
                            </div>
                            <div>
                                <label class="ep-label">
                                    Valor *
                                </label>
                                <input
                                    v-model="form.value"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    required
                                    class="ep-input font-medium tabular-nums"
                                    :placeholder="form.type === 'percent' ? '0–100' : '0,00'"
                                />
                                <p v-if="form.errors.value" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">
                                    {{ form.errors.value }}
                                </p>
                            </div>
                        </div>
                        <div>
                            <label class="ep-label">
                                Produtos
                            </label>
                            <div class="relative" data-cupom-products-dropdown>
                                <button
                                    type="button"
                                    class="ep-input flex items-center justify-between gap-2 text-left"
                                    aria-haspopup="listbox"
                                    :aria-expanded="productsDropdownOpen"
                                    @click="productsDropdownOpen = !productsDropdownOpen"
                                >
                                    <span class="truncate">{{ productsLabel }}</span>
                                    <ChevronDown
                                        class="h-4 w-4 shrink-0 text-[var(--ep-text-3)] transition-transform duration-200"
                                        :class="{ 'rotate-180': productsDropdownOpen }"
                                        :stroke-width="1.75"
                                    />
                                </button>
                                <div
                                    v-show="productsDropdownOpen"
                                    class="absolute left-0 right-0 top-full z-50 mt-1.5 max-h-56 overflow-auto rounded-[14px] border border-[var(--ep-glass-border)] bg-[var(--ep-drawer)] p-1 shadow-[var(--ep-shadow-pop)] backdrop-blur-2xl backdrop-saturate-150"
                                    role="listbox"
                                >
                                    <button
                                        v-for="p in produtos"
                                        :key="p.id"
                                        type="button"
                                        role="option"
                                        :aria-selected="isProductSelected(p.id)"
                                        class="flex w-full items-center gap-2.5 rounded-[10px] px-2.5 py-2 text-left text-[13px] text-[var(--ep-text-2)] transition-colors duration-150 hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)]"
                                        @click="toggleProduct(p.id)"
                                    >
                                        <span
                                            class="flex h-4 w-4 shrink-0 items-center justify-center rounded-[5px] border transition-colors duration-150"
                                            :class="isProductSelected(p.id) ? 'border-[var(--ep-accent)] bg-[var(--ep-accent)] text-white dark:text-[#061126]' : 'border-[var(--ep-line-strong)] bg-[var(--ep-input)]'"
                                        >
                                            <Check v-if="isProductSelected(p.id)" class="h-3 w-3" stroke-width="3" />
                                        </span>
                                        <span class="min-w-0 truncate">{{ p.name }}</span>
                                    </button>
                                    <p v-if="!produtos.length" class="px-2.5 py-2 text-[12.5px] text-[var(--ep-text-4)]">
                                        Nenhum produto cadastrado.
                                    </p>
                                </div>
                            </div>
                            <p class="ep-help">
                                Nenhum selecionado = cupom vale para todos os produtos.
                            </p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="ep-label">
                                    Pedido mínimo (R$)
                                </label>
                                <input
                                    v-model="form.min_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="ep-input tabular-nums"
                                    placeholder="Opcional"
                                />
                                <p v-if="form.errors.min_amount" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">
                                    {{ form.errors.min_amount }}
                                </p>
                            </div>
                            <div>
                                <label class="ep-label">
                                    Máximo de usos
                                </label>
                                <input
                                    v-model="form.max_uses"
                                    type="number"
                                    min="1"
                                    class="ep-input tabular-nums"
                                    placeholder="Ilimitado"
                                />
                                <p v-if="form.errors.max_uses" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">
                                    {{ form.errors.max_uses }}
                                </p>
                            </div>
                        </div>
                        <div class="space-y-4 rounded-[14px] border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-3.5">
                            <p class="ep-section-title">Validade</p>
                            <div>
                                <label class="ep-label">
                                    Válido de
                                </label>
                                <input
                                    v-model="form.valid_from"
                                    type="datetime-local"
                                    class="ep-input tabular-nums"
                                />
                            </div>
                            <div>
                                <label class="ep-label">
                                    Válido até
                                </label>
                                <input
                                    v-model="form.valid_until"
                                    type="datetime-local"
                                    class="ep-input tabular-nums"
                                />
                                <p v-if="form.errors.valid_until" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">
                                    {{ form.errors.valid_until }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 rounded-[14px] border border-[var(--ep-line)] bg-[var(--ep-card-2)] px-3.5 py-3">
                            <Toggle v-model="form.is_active" label="Cupom ativo" />
                        </div>
                        <div class="sticky bottom-0 -mx-6 -mb-5 flex gap-2 border-t border-[var(--ep-line)] bg-[var(--ep-drawer)] px-6 py-4 backdrop-blur-xl">
                            <Button type="submit" :disabled="form.processing">
                                {{ isEdit ? 'Salvar' : 'Criar cupom' }}
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
