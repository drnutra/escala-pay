<script setup>
import { computed } from 'vue';
import Checkbox from '@/components/ui/Checkbox.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    platform: { type: String, required: true },
    block: { type: Object, required: true },
    integrations: { type: Array, default: () => [] },
    disabled: { type: Boolean, default: false },
});

const selectedIds = computed(() => {
    const ids = props.block?.integration_ids;
    return Array.isArray(ids) ? ids.map((id) => Number(id)) : [];
});

const usesIntegrations = computed(() => selectedIds.value.length > 0);

function isSelected(id) {
    return selectedIds.value.includes(Number(id));
}

function toggleIntegration(id, checked) {
    if (!Array.isArray(props.block.integration_ids)) {
        props.block.integration_ids = [];
    }
    const numId = Number(id);
    if (checked) {
        if (!props.block.integration_ids.includes(numId)) {
            props.block.integration_ids.push(numId);
        }
        props.block.entries = [];
    } else {
        props.block.integration_ids = props.block.integration_ids.filter((x) => x !== numId);
    }
}

function ensureBlockFlags() {
    if (props.block.fire_purchase_on_pix === undefined) {
        props.block.fire_purchase_on_pix = true;
    }
    if (props.block.fire_purchase_on_boleto === undefined) {
        props.block.fire_purchase_on_boleto = true;
    }
    if (props.block.disable_order_bump_events === undefined) {
        props.block.disable_order_bump_events = false;
    }
}

function onUseIntegrationsChange(checked) {
    if (checked) {
        ensureBlockFlags();
        if (!Array.isArray(props.block.integration_ids)) {
            props.block.integration_ids = [];
        }
    } else {
        props.block.integration_ids = [];
    }
}
</script>

<template>
    <div class="space-y-4 rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4">
        <div class="flex items-center justify-between gap-3">
            <p class="ep-section-title">Integrações cadastradas</p>
            <span class="ep-chip tabular-nums">{{ integrations.length }}</span>
        </div>
        <p v-if="integrations.length === 0" class="text-[12.5px] text-[var(--ep-text-4)]">
            Nenhuma integração nesta plataforma.
            <Link href="/integracoes" class="ml-1 font-medium text-[var(--ep-accent)] underline-offset-4 hover:underline">Cadastrar em Integrações</Link>
        </p>
        <template v-else>
            <Checkbox
                :model-value="usesIntegrations"
                label="Usar integrações cadastradas (recomendado)"
                :disabled="disabled"
                @update:model-value="onUseIntegrationsChange"
            />
            <div v-if="usesIntegrations" class="space-y-2">
                <label
                    v-for="item in integrations"
                    :key="item.id"
                    class="flex cursor-pointer items-start gap-3 rounded-xl border px-3.5 py-3 transition-colors duration-150"
                    :class="isSelected(item.id)
                        ? 'border-[color-mix(in_oklab,var(--ep-accent)_45%,transparent)] bg-[color-mix(in_oklab,var(--ep-accent)_10%,transparent)]'
                        : 'border-[var(--ep-line)] bg-[var(--ep-input)] hover:border-[var(--ep-line-strong)]'"
                >
                    <input
                        type="checkbox"
                        class="mt-0.5 h-4 w-4 shrink-0 cursor-pointer accent-[var(--ep-accent)]"
                        :checked="isSelected(item.id)"
                        :disabled="disabled"
                        @change="toggleIntegration(item.id, $event.target.checked)"
                    />
                    <span class="min-w-0">
                        <span class="block truncate text-[13px] font-medium text-[var(--ep-text)]">{{ item.name }}</span>
                        <span class="mt-0.5 block text-[12px] text-[var(--ep-text-3)]">{{ item.summary }}</span>
                    </span>
                </label>
            </div>
            <div v-if="usesIntegrations" class="space-y-3 border-t border-[var(--ep-line)] pt-4">
                <Checkbox v-model="block.fire_purchase_on_pix" label="Disparar Purchase ao gerar PIX (não na aprovação)?" :disabled="disabled" />
                <Checkbox v-model="block.fire_purchase_on_boleto" label="Disparar Purchase ao gerar boleto (não na aprovação)?" :disabled="disabled" />
                <Checkbox v-model="block.disable_order_bump_events" label="Desativar eventos de order bumps?" :disabled="disabled" />
            </div>
        </template>
    </div>
</template>
