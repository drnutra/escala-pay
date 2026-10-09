<script setup>
const props = defineProps({
    modelValue: { type: Boolean, default: false },
    label: { type: String, default: '' },
    name: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

function toggle() {
    if (props.disabled) return;
    emit('update:modelValue', !props.modelValue);
}
</script>

<template>
    <div class="flex items-center gap-3" :class="{ 'cursor-not-allowed opacity-60': disabled }">
        <button
            type="button"
            role="switch"
            :disabled="disabled"
            :aria-checked="modelValue"
            :aria-label="label || (modelValue ? 'Ativo' : 'Inativo')"
            :class="[
                'relative inline-flex h-6 w-11 shrink-0 rounded-full border border-[var(--ep-line-strong)] transition-[background-color,box-shadow] duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--ep-accent)]',
                modelValue
                    ? 'bg-[var(--ep-accent)]'
                    : 'bg-[var(--ep-active)]',
            ]"
            @click="toggle"
        >
            <span
                :class="[
                    'pointer-events-none mt-px inline-block h-5 w-5 transform rounded-full bg-white shadow-[0_1px_3px_rgba(0,0,0,0.35)] ring-0 transition-transform duration-200 ease-[cubic-bezier(0.23,1,0.32,1)]',
                    modelValue ? 'translate-x-[21px]' : 'translate-x-px',
                ]"
            />
        </button>
        <label v-if="label" class="text-[13px] font-medium text-[var(--ep-text-2)]" @click="toggle">
            {{ label }}
        </label>
    </div>
</template>
