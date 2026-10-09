<script setup>
import { ref, computed } from 'vue';
import { Pencil, Check, X } from 'lucide-vue-next';
import { formatBRL } from '@/composables/useTrackingPanel';

const props = defineProps({
    amount: { type: Number, default: 0 },
    adSpendMeta: { type: Object, default: () => ({}) },
    period: { type: String, default: 'hoje' },
    valuesVisible: { type: Boolean, default: true },
});

const emit = defineEmits(['save-daily', 'save-period', 'clear-period']);

const editing = ref(false);
const inputValue = ref('0');
const usePeriodOverride = ref(false);

function startEdit() {
    inputValue.value = String(props.amount ?? 0);
    usePeriodOverride.value = props.adSpendMeta?.override ?? false;
    editing.value = true;
}

function cancelEdit() {
    editing.value = false;
}

async function save() {
    const amount = parseFloat(String(inputValue.value).replace(',', '.')) || 0;
    if (usePeriodOverride.value || props.period !== 'hoje') {
        emit('save-period', amount);
    } else {
        const today = new Date().toISOString().slice(0, 10);
        emit('save-daily', { date: today, amount });
    }
    editing.value = false;
}

const displayAmount = computed(() =>
    props.valuesVisible ? formatBRL(props.amount) : '••••••'
);

const subtitle = computed(() => {
    if (props.adSpendMeta?.override) {
        return 'Valor único do período';
    }
    return props.period === 'hoje' ? 'Editável — hoje' : 'Soma diária do período';
});
</script>

<template>
    <div class="panel-card ep-kpi min-w-0">
        <div class="flex items-center justify-between gap-2">
            <span class="ep-kpi__label truncate">Gasto em anúncios</span>
            <button
                v-if="!editing"
                type="button"
                class="ep-btn-ghost ep-btn-icon -my-1 -mr-1.5 h-8 w-8 shrink-0 text-[var(--ep-text-3)]"
                aria-label="Editar gasto em anúncios"
                title="Editar gasto em anúncios"
                @click="startEdit"
            >
                <Pencil class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
            </button>
            <div v-else class="-my-1 -mr-1 flex shrink-0 gap-1">
                <button type="button" class="ep-btn ep-btn-icon h-8 w-8" aria-label="Salvar gasto" @click="save">
                    <Check class="h-4 w-4" :stroke-width="2" aria-hidden="true" />
                </button>
                <button type="button" class="ep-btn-secondary ep-btn-icon h-8 w-8" aria-label="Cancelar edição" @click="cancelEdit">
                    <X class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                </button>
            </div>
        </div>
        <p v-if="!editing" class="ep-kpi__value mt-1 truncate">{{ displayAmount }}</p>
        <div v-else class="mt-1 flex items-center gap-2">
            <span class="text-[13px] font-medium text-[var(--ep-text-3)]">R$</span>
            <input
                v-model="inputValue"
                type="number"
                min="0"
                step="0.01"
                class="ep-input h-9 min-w-0 max-w-[150px] text-[15px] font-semibold tabular-nums"
            />
        </div>
        <p class="ep-kpi__meta flex items-center gap-1.5">
            <span
                class="h-1.5 w-1.5 shrink-0 rounded-full"
                :class="adSpendMeta?.override ? 'bg-[var(--ep-warn)]' : 'bg-[var(--ep-accent)]'"
                aria-hidden="true"
            />
            <span class="truncate">{{ subtitle }}</span>
        </p>
        <label v-if="editing && period !== 'hoje'" class="mt-1 flex cursor-pointer items-center gap-2 text-[12px] text-[var(--ep-text-3)]">
            <input v-model="usePeriodOverride" type="checkbox" class="h-3.5 w-3.5 rounded border-[var(--ep-input-border)] accent-[var(--ep-accent)]" />
            Usar valor único para todo o período
        </label>
        <button
            v-if="adSpendMeta?.override && !editing"
            type="button"
            class="self-start text-[12px] font-medium text-[var(--ep-accent)] transition-opacity duration-150 hover:opacity-80"
            @click="emit('clear-period')"
        >
            Voltar à soma diária
        </button>
    </div>
</template>
