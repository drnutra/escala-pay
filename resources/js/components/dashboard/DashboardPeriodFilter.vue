<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
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
import {
    Sun,
    Moon,
    CalendarRange,
    Calendar,
    CalendarClock,
    Infinity,
    ChevronDown,
    Check,
} from 'lucide-vue-next';

const props = defineProps({
    modelValue: { type: String, required: true },
});

const emit = defineEmits(['update:modelValue']);

const periodOptions = [
    { value: 'hoje', label: 'Hoje', icon: Sun },
    { value: 'ontem', label: 'Ontem', icon: Moon },
    { value: '7dias', label: '7 dias', icon: CalendarRange },
    { value: 'mes', label: 'Mês', icon: Calendar },
    { value: 'ano', label: 'Ano', icon: CalendarClock },
    { value: 'total', label: 'Total', icon: Infinity },
];

const trackRef = ref(null);
const buttonRefs = ref([]);
const indicator = ref({ left: 0, width: 0, opacity: 0 });

const activeOption = computed(() =>
    periodOptions.find((o) => o.value === props.modelValue) ?? periodOptions[0],
);

const activeIndex = computed(() =>
    Math.max(0, periodOptions.findIndex((o) => o.value === props.modelValue)),
);

function setButtonRef(el, index) {
    if (el) {
        buttonRefs.value[index] = el;
    }
}

function updateIndicator() {
    if (typeof window !== 'undefined' && !window.matchMedia('(min-width: 1024px)').matches) {
        return;
    }
    const track = trackRef.value;
    const btn = buttonRefs.value[activeIndex.value];
    if (!track || !btn) {
        return;
    }
    const trackRect = track.getBoundingClientRect();
    const btnRect = btn.getBoundingClientRect();
    indicator.value = {
        left: btnRect.left - trackRect.left,
        width: btnRect.width,
        opacity: 1,
    };
}

function select(value) {
    if (value !== props.modelValue) {
        emit('update:modelValue', value);
    }
}

let resizeObserver;

onMounted(() => {
    nextTick(updateIndicator);
    resizeObserver = new ResizeObserver(() => updateIndicator());
    if (trackRef.value) {
        resizeObserver.observe(trackRef.value);
    }
    window.addEventListener('resize', updateIndicator);
});

onBeforeUnmount(() => {
    resizeObserver?.disconnect();
    window.removeEventListener('resize', updateIndicator);
});

watch(
    () => props.modelValue,
    () => nextTick(updateIndicator),
);
</script>

<template>
    <div class="w-full max-w-full">

        <!-- Mobile: dropdown -->
        <div class="flex items-center gap-2 lg:hidden">
            <SelectRoot :model-value="modelValue" @update:model-value="select">
                <SelectTrigger
                    type="button"
                    aria-label="Período"
                    class="flex h-9 min-w-0 flex-1 cursor-pointer items-center justify-between gap-2 rounded-lg border border-[var(--ep-line)] bg-[var(--ep-card)] px-3 text-left text-[13px] font-medium text-[var(--ep-text)] shadow-[var(--ep-highlight)] transition-colors duration-150 hover:border-[var(--ep-line-strong)]"
                >
                    <span class="flex min-w-0 items-center gap-2 truncate">
                        <component
                            :is="activeOption.icon"
                            class="h-4 w-4 shrink-0 text-[var(--ep-text-3)]"
                            aria-hidden="true"
                        />
                        <SelectValue :placeholder="activeOption.label" />
                    </span>
                    <ChevronDown class="h-4 w-4 shrink-0 text-[var(--ep-text-4)]" aria-hidden="true" />
                </SelectTrigger>
                <SelectPortal to="body">
                    <SelectContent
                        class="z-[9999] min-w-[var(--radix-select-trigger-width)] overflow-hidden rounded-xl border border-[var(--ep-line)] bg-[var(--ep-card)] shadow-[var(--ep-shadow-pop)]"
                        :side-offset="6"
                        position="popper"
                        :avoid-collisions="true"
                    >
                        <SelectViewport class="p-1">
                            <SelectItem
                                v-for="opt in periodOptions"
                                :key="opt.value"
                                :value="opt.value"
                                class="relative flex cursor-pointer select-none items-center gap-2 rounded-lg py-2 pl-2.5 pr-9 text-[13px] text-[var(--ep-text-2)] outline-none data-[highlighted]:bg-[var(--ep-hover)] data-[highlighted]:text-[var(--ep-text)] data-[state=checked]:font-medium data-[state=checked]:text-[var(--ep-text)]"
                            >
                                <component :is="opt.icon" class="h-4 w-4 shrink-0 opacity-70" aria-hidden="true" />
                                <SelectItemText>{{ opt.label }}</SelectItemText>
                                <SelectItemIndicator class="absolute right-3 flex h-4 w-4 items-center justify-center">
                                    <Check class="h-4 w-4 text-[var(--ep-text)]" />
                                </SelectItemIndicator>
                            </SelectItem>
                        </SelectViewport>
                    </SelectContent>
                </SelectPortal>
            </SelectRoot>
            <slot name="trailing" />
        </div>

        <!-- Desktop: barra segmentada -->
        <div class="hidden w-full items-center gap-2 lg:flex">
            <div
                ref="trackRef"
                class="relative inline-flex h-9 w-max items-center gap-0.5 rounded-lg border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-[3px]"
                role="tablist"
                aria-label="Período do dashboard"
            >
                <div
                    class="pointer-events-none absolute top-[3px] bottom-[3px] z-0 rounded-md bg-[var(--ep-card)] shadow-[0_1px_2px_rgba(0,0,0,0.08)] ring-1 ring-[var(--ep-line-strong)] transition-[left,width,opacity] duration-250 ease-[cubic-bezier(0.23,1,0.32,1)] dark:bg-[#202227]"
                    :style="{
                        left: `${indicator.left}px`,
                        width: `${indicator.width}px`,
                        opacity: indicator.opacity,
                    }"
                    aria-hidden="true"
                />

                <template v-for="(opt, index) in periodOptions" :key="opt.value">
                    <div
                        v-if="index === 3"
                        class="mx-0.5 h-4 w-px shrink-0 self-center bg-[var(--ep-line-strong)]"
                        aria-hidden="true"
                    />
                    <button
                        :ref="(el) => setButtonRef(el, index)"
                        type="button"
                        role="tab"
                        :aria-selected="modelValue === opt.value"
                        class="relative z-10 flex h-full shrink-0 items-center rounded-md px-3 text-[13px] font-medium transition-colors duration-150"
                        :class="modelValue === opt.value
                            ? 'text-[var(--ep-text)]'
                            : 'text-[var(--ep-text-3)] hover:text-[var(--ep-text)]'"
                        @click="select(opt.value)"
                    >
                        <span class="whitespace-nowrap">{{ opt.label }}</span>
                    </button>
                </template>
            </div>
            <div class="ml-auto flex items-center gap-2"><slot name="trailing" /></div>
        </div>
    </div>
</template>
