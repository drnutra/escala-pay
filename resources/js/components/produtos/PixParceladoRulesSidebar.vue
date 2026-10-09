<script setup>
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import Button from '@/components/ui/Button.vue';
import { X, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    open: { type: Boolean, default: false },
    productId: { type: [String, Number], required: true },
    priceBrl: { type: Number, default: 0 },
    modelValue: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['close', 'update:modelValue', 'save']);

const local = ref({});
const loading = ref(false);
const loadError = ref('');
const platformHint = ref(null);
const fieldErrors = ref({});

function emptyRules() {
    return {
        max_installments: null,
        down_payment_cents: null,
        min_down_payment_bps: null,
        max_down_payment_bps: null,
        early_payment_discount_bps: 0,
        payoff_discount_bps: 0,
        overdue_payoff_discount_bps: 0,
    };
}

watch(
    () => [props.open, props.modelValue],
    ([open, val]) => {
        if (open) {
            local.value = { ...emptyRules(), ...(val && typeof val === 'object' ? val : {}) };
            fieldErrors.value = {};
            fetchPlatformRules();
        }
    },
    { immediate: true },
);

async function fetchPlatformRules() {
    loading.value = true;
    loadError.value = '';
    try {
        const { data } = await axios.get(`/produtos/${props.productId}/pix-parcelado/platform-rules`);
        platformHint.value = data?.merged ?? data;
    } catch (e) {
        loadError.value = e?.response?.data?.message || 'Não foi possível carregar regras da plataforma.';
        platformHint.value = null;
    } finally {
        loading.value = false;
    }
}

const maxInstallmentsHint = computed(() => {
    const max = platformHint.value?.platform_max_installments ?? platformHint.value?.max_installments;
    return max ? `${max}x` : '—';
});

function bpsToPercent(bps) {
    if (bps === null || bps === undefined || bps === '') return '';
    return String(Number(bps) / 100);
}

function percentToBps(val) {
    if (val === '' || val == null) return null;
    const n = parseFloat(String(val).replace(',', '.'));
    if (Number.isNaN(n)) return null;
    return Math.round(n * 100);
}

function centsToReais(cents) {
    if (cents === null || cents === undefined || cents === '') return '';
    return (Number(cents) / 100).toFixed(2).replace('.', ',');
}

function reaisToCents(val) {
    if (val === '' || val == null) return null;
    const n = parseFloat(String(val).replace(/\./g, '').replace(',', '.'));
    if (Number.isNaN(n)) return null;
    return Math.round(n * 100);
}

const downPaymentReais = computed({
    get: () => centsToReais(local.value.down_payment_cents),
    set: (v) => {
        local.value.down_payment_cents = reaisToCents(v);
    },
});

function save() {
    fieldErrors.value = {};
    emit('update:modelValue', { ...local.value });
    emit('save', { ...local.value });
    emit('close');
}

function close() {
    emit('close');
}
</script>

<template>
    <Teleport to="body">
        <div v-show="open" class="fixed inset-0 z-[100000] flex justify-end" aria-modal="true" role="dialog">
            <div class="ep-scrim fixed inset-0" aria-hidden="true" @click="close" />
            <aside class="ep-drawer relative flex h-full w-full max-w-md flex-col overflow-hidden sm:rounded-l-[22px]">
                <div class="flex items-start justify-between gap-3 border-b border-[var(--ep-line)] px-6 py-5">
                    <div class="min-w-0">
                        <p class="flex items-center gap-1.5 text-[12px] text-[var(--ep-text-3)]">
                            <span class="h-1.5 w-1.5 rounded-full bg-[var(--ep-pix)]" aria-hidden="true" />
                            Pix · CajuPay
                        </p>
                        <h2 class="mt-0.5 text-[16px] font-semibold tracking-[-0.02em] text-[var(--ep-text)]">Regras PIX Parcelado</h2>
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
                        Configure entrada, parcelas e descontos congelados no plano CajuPay. Valor mínimo R$ 50,00.
                    </p>

                    <div v-if="loading" class="mb-5 flex items-center gap-2 rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] px-4 py-3.5 text-[12.5px] text-[var(--ep-text-3)]">
                        <Loader2 class="h-4 w-4 animate-spin text-[var(--ep-accent)]" stroke-width="1.75" />
                        Carregando faixas da plataforma…
                    </div>
                    <p v-else-if="loadError" class="mb-5 flex items-start gap-2.5 rounded-2xl border border-[color-mix(in_oklab,var(--ep-warn)_35%,transparent)] bg-[var(--ep-warn-bg)] px-4 py-3 text-[12.5px] leading-relaxed text-[var(--ep-text-2)]">
                        <span class="mt-[7px] h-1.5 w-1.5 shrink-0 rounded-full bg-[var(--ep-warn)]" aria-hidden="true" />
                        <span>{{ loadError }}</span>
                    </p>
                    <div v-else-if="platformHint" class="ep-glow-card mb-5 flex items-end justify-between gap-4 rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4">
                        <div class="min-w-0">
                            <p class="text-[12px] font-medium text-[var(--ep-text-3)]">Limite da plataforma</p>
                            <p class="mt-1 text-[12.5px] leading-relaxed text-[var(--ep-text-3)]">
                                Com preço <span class="font-medium tabular-nums text-[var(--ep-text-2)]">R$ {{ priceBrl.toFixed(2).replace('.', ',') }}</span>, a plataforma permite até
                                <strong class="font-medium text-[var(--ep-text)]">{{ maxInstallmentsHint }}</strong> parcelas.
                            </p>
                        </div>
                        <span class="shrink-0 text-[28px] font-semibold leading-none tracking-[-0.03em] tabular-nums text-[var(--ep-text)]">{{ maxInstallmentsHint }}</span>
                    </div>

                    <div class="space-y-6">
                        <fieldset class="space-y-4">
                            <legend class="ep-section-title mb-3">Parcelas e entrada</legend>
                            <div>
                                <label class="ep-label">Máximo de parcelas (opcional)</label>
                                <div class="relative">
                                    <input
                                        v-model.number="local.max_installments"
                                        type="number"
                                        min="1"
                                        max="24"
                                        placeholder="Usar só limite da plataforma"
                                        class="ep-input pr-9 tabular-nums"
                                    />
                                    <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[12.5px] text-[var(--ep-text-4)]" aria-hidden="true">x</span>
                                </div>
                            </div>
                            <div>
                                <label class="ep-label">Entrada fixa (R$)</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[12.5px] text-[var(--ep-text-4)]" aria-hidden="true">R$</span>
                                    <input
                                        v-model="downPaymentReais"
                                        type="text"
                                        inputmode="decimal"
                                        placeholder="Opcional"
                                        class="ep-input pl-9 tabular-nums"
                                    />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="ep-label">Entrada mínima (%)</label>
                                    <div class="relative">
                                        <input
                                            :value="bpsToPercent(local.min_down_payment_bps)"
                                            type="text"
                                            inputmode="decimal"
                                            placeholder="Ex.: 20"
                                            class="ep-input pr-8 tabular-nums"
                                            @input="local.min_down_payment_bps = percentToBps($event.target.value)"
                                        />
                                        <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[12.5px] text-[var(--ep-text-4)]" aria-hidden="true">%</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="ep-label">Entrada máxima (%)</label>
                                    <div class="relative">
                                        <input
                                            :value="bpsToPercent(local.max_down_payment_bps)"
                                            type="text"
                                            inputmode="decimal"
                                            placeholder="Ex.: 60"
                                            class="ep-input pr-8 tabular-nums"
                                            @input="local.max_down_payment_bps = percentToBps($event.target.value)"
                                        />
                                        <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[12.5px] text-[var(--ep-text-4)]" aria-hidden="true">%</span>
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        <div class="ep-divider" />

                        <fieldset class="space-y-4">
                            <legend class="ep-section-title mb-3">Descontos</legend>
                            <div>
                                <label class="ep-label">Desconto antecipação (%)</label>
                                <div class="relative">
                                    <input
                                        :value="bpsToPercent(local.early_payment_discount_bps)"
                                        type="text"
                                        inputmode="decimal"
                                        class="ep-input pr-8 tabular-nums"
                                        @input="local.early_payment_discount_bps = percentToBps($event.target.value) ?? 0"
                                    />
                                    <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[12.5px] text-[var(--ep-text-4)]" aria-hidden="true">%</span>
                                </div>
                            </div>
                            <div>
                                <label class="ep-label">Desconto quitação (%)</label>
                                <div class="relative">
                                    <input
                                        :value="bpsToPercent(local.payoff_discount_bps)"
                                        type="text"
                                        inputmode="decimal"
                                        class="ep-input pr-8 tabular-nums"
                                        @input="local.payoff_discount_bps = percentToBps($event.target.value) ?? 0"
                                    />
                                    <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[12.5px] text-[var(--ep-text-4)]" aria-hidden="true">%</span>
                                </div>
                            </div>
                            <div>
                                <label class="ep-label">Desconto quitação em atraso (%)</label>
                                <div class="relative">
                                    <input
                                        :value="bpsToPercent(local.overdue_payoff_discount_bps)"
                                        type="text"
                                        inputmode="decimal"
                                        class="ep-input pr-8 tabular-nums"
                                        @input="local.overdue_payoff_discount_bps = percentToBps($event.target.value) ?? 0"
                                    />
                                    <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[12.5px] text-[var(--ep-text-4)]" aria-hidden="true">%</span>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                </div>

                <div class="border-t border-[var(--ep-line)] px-6 py-4">
                    <Button type="button" class="w-full" @click="save">Salvar regras</Button>
                </div>
            </aside>
        </div>
    </Teleport>
</template>
