<script setup>
import {
    SelectContent,
    SelectItem,
    SelectItemIndicator,
    SelectItemText,
    SelectPortal,
    SelectRoot,
    SelectTrigger,
    SelectValue,
    SelectViewport,
} from 'radix-vue';
import { ChevronDown, Check } from 'lucide-vue-next';
import { computed } from 'vue';
import { cn } from '@/lib/utils';

const VALUE_NONE = '__none__';

const props = defineProps({
    modelValue: { type: String, default: '' },
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: 'Selecione...' },
    label: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

// Radix usa valor interno; '' mapeamos para VALUE_NONE para evitar bugs
const internalValue = computed({
    get: () => (props.modelValue === '' ? VALUE_NONE : props.modelValue),
    set: (v) => emit('update:modelValue', v === VALUE_NONE ? '' : v),
});

// Opções com '' substituído por VALUE_NONE para o Select
const selectOptions = computed(() =>
    props.options.map((opt) => ({
        value: opt.value === '' ? VALUE_NONE : opt.value,
        label: opt.label,
    }))
);

const triggerClass = cn(
    'ep-input flex !h-11 cursor-pointer items-center justify-between gap-2 text-left',
    'hover:border-[var(--ep-line-strong)]',
    'data-[placeholder]:text-[var(--ep-text-4)]'
);
</script>

<template>
    <SelectRoot
        v-model="internalValue"
        :disabled="disabled"
    >
        <SelectTrigger
            :class="triggerClass"
            type="button"
            :aria-label="label || placeholder"
        >
            <SelectValue :placeholder="placeholder" />
            <ChevronDown
                class="h-4 w-4 shrink-0 text-[var(--ep-text-4)]"
                aria-hidden="true"
            />
        </SelectTrigger>
        <SelectPortal to="body">
            <SelectContent
                class="ep-modal z-[9999] min-w-[var(--radix-select-trigger-width)] overflow-hidden !rounded-xl py-1"
                :side-offset="4"
                position="popper"
                :avoid-collisions="true"
            >
                <SelectViewport class="p-1">
                    <SelectItem
                        v-for="opt in selectOptions"
                        :key="String(opt.value)"
                        :value="opt.value"
                        class="relative flex cursor-pointer select-none items-center rounded-lg py-2 pl-9 pr-4 text-[13px] text-[var(--ep-text-2)] outline-none transition-colors data-[highlighted]:bg-[var(--ep-hover)] data-[highlighted]:text-[var(--ep-text)] data-[state=checked]:font-medium data-[state=checked]:text-[var(--ep-text)]"
                    >
                        <SelectItemIndicator class="absolute left-3 flex h-4 w-4 items-center justify-center">
                            <Check class="h-4 w-4 text-[var(--ep-accent)]" />
                        </SelectItemIndicator>
                        <SelectItemText>{{ opt.label }}</SelectItemText>
                    </SelectItem>
                </SelectViewport>
            </SelectContent>
        </SelectPortal>
    </SelectRoot>
</template>
