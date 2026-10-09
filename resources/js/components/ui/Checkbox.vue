<script setup>
import { Check } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    label: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
    class: { type: [String, Object, Array], default: '' },
});

const emit = defineEmits(['update:modelValue']);

function toggle() {
    if (props.disabled) return;
    emit('update:modelValue', !props.modelValue);
}
</script>

<template>
    <label
        :class="[
            'flex w-full cursor-pointer items-center gap-3 text-[13px] text-[var(--ep-text-2)] transition-colors',
            disabled && 'cursor-not-allowed opacity-60',
            cn(props.class),
        ]"
        @click="toggle"
    >
        <span
            role="checkbox"
            :aria-checked="modelValue"
            :aria-label="label || (modelValue ? 'Marcado' : 'Desmarcado')"
            :class="[
                'relative flex h-[18px] w-[18px] shrink-0 items-center justify-center rounded-[6px] border transition-[background-color,border-color,box-shadow] duration-150',
                'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--ep-accent)]',
                modelValue
                    ? 'border-[var(--ep-accent)] bg-[var(--ep-accent)] text-[#061126]'
                    : 'border-[var(--ep-input-border)] bg-[var(--ep-input)] hover:border-[var(--ep-line-strong)]',
                disabled && 'pointer-events-none',
            ]"
            tabindex="0"
            @keydown.enter.prevent="toggle"
            @keydown.space.prevent="toggle"
        >
            <Check v-if="modelValue" class="h-3 w-3 stroke-[2.5]" />
        </span>
        <span v-if="label" class="select-none">{{ label }}</span>
        <slot v-else />
    </label>
</template>
