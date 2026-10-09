<script setup>
import { computed } from 'vue';
import { Settings } from 'lucide-vue-next';

const props = defineProps({
  title: { type: String, required: true },
  logo: { type: String, required: true },
  description: { type: String, default: '' },
  selected: { type: Boolean, default: false },
  configured: { type: Boolean, default: false },
});
const emit = defineEmits(['select', 'configure']);

const ariaPressed = computed(() => (props.selected ? 'true' : 'false'));
</script>

<template>
  <div
    class="group relative flex items-stretch rounded-[16px] border transition-[border-color,box-shadow,background-color] duration-200 ease-[var(--ep-ease-out)]"
    :class="props.selected
      ? 'border-[color-mix(in_oklab,var(--ep-accent)_55%,transparent)] bg-[color-mix(in_oklab,var(--ep-accent)_9%,var(--ep-card-2))] shadow-[var(--ep-glass-highlight),0_0_0_3px_color-mix(in_oklab,var(--ep-accent)_16%,transparent),0_12px_30px_-14px_var(--ep-glow)]'
      : 'border-[var(--ep-line)] bg-[var(--ep-card-2)] hover:border-[var(--ep-line-strong)] hover:bg-[var(--ep-hover)]'"
  >
    <button
      type="button"
      class="flex min-w-0 flex-1 flex-col items-center justify-center gap-2 rounded-[16px] py-5 text-center focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[color-mix(in_oklab,var(--ep-accent)_45%,transparent)]"
      :class="selected ? 'px-10' : 'px-4'"
      :aria-pressed="ariaPressed"
      @click="$emit('select')"
    >
      <span class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-[14px] border border-[var(--ep-glass-border)] bg-[var(--ep-glass-strong)] p-[3px] shadow-[var(--ep-glass-highlight),0_8px_22px_-12px_var(--ep-glow)]">
        <img :src="logo" alt="" class="size-full rounded-[11px] object-contain" />
      </span>
      <div class="text-[13.5px] font-semibold tracking-[-0.01em] text-[var(--ep-text)]">{{ title }}</div>
      <div v-if="description" class="text-[12.5px] leading-[1.45] text-[var(--ep-text-3)]">{{ description }}</div>
      <div
        v-if="configured"
        class="ep-chip ep-chip--pos mt-1"
        title="Configurado"
      >
        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
          <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
        </svg>
        Configurado
      </div>
    </button>

    <div
      v-if="selected"
      class="absolute right-2 top-2 z-10 flex items-center justify-center"
    >
      <button
        type="button"
        class="ep-btn-ghost ep-btn-icon !h-8 !w-8 !rounded-[10px]"
        title="Configurar provedor"
        aria-label="Configurar provedor"
        @click.stop.prevent="$emit('configure')"
      >
        <Settings class="h-4 w-4" :stroke-width="1.75" />
      </button>
    </div>
  </div>
</template>

