<script setup>
import { ref, computed, watch, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { CheckCircle2, XCircle, X } from 'lucide-vue-next';

const page = usePage();
const dismissed = ref(false);
let dismissTimer = null;

const flash = computed(() => page.props.flash ?? { success: null, error: null });

const message = computed(() => flash.value?.error ?? flash.value?.success ?? null);
const isError = computed(() => !!flash.value?.error);

const visible = computed(() => !!message.value && !dismissed.value);

function close() {
    dismissed.value = true;
    if (dismissTimer) {
        clearTimeout(dismissTimer);
        dismissTimer = null;
    }
}

watch(
    () => [flash.value?.success, flash.value?.error],
    () => {
        dismissed.value = false;
    },
    { immediate: true }
);

watch(
    visible,
    (v) => {
        if (v && message.value) {
            if (dismissTimer) clearTimeout(dismissTimer);
            dismissTimer = setTimeout(close, 4500);
        }
    },
    { immediate: true }
);

onUnmounted(() => {
    if (dismissTimer) clearTimeout(dismissTimer);
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-300 ease-[cubic-bezier(0.23,1,0.32,1)]"
            enter-from-class="translate-y-2 opacity-0 sm:translate-x-6 sm:translate-y-0"
            enter-to-class="translate-x-0 translate-y-0 opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="translate-x-0 opacity-100"
            leave-to-class="translate-x-6 opacity-0"
        >
            <div
                v-if="visible"
                role="alert"
                aria-live="polite"
                class="ep-modal fixed bottom-4 left-4 right-4 z-[100001] flex items-start gap-3 overflow-hidden rounded-2xl py-3 pl-4 pr-2.5 sm:left-auto sm:w-[380px] sm:max-w-sm"
            >
                <span
                    :class="[
                        'pointer-events-none absolute inset-y-0 left-0 w-[3px]',
                        isError ? 'bg-[var(--ep-neg)]' : 'bg-[var(--ep-pos)]',
                    ]"
                    aria-hidden="true"
                />
                <span
                    :class="[
                        'flex h-7 w-7 shrink-0 items-center justify-center rounded-[9px] border',
                        isError
                            ? 'border-[color-mix(in_oklab,var(--ep-neg)_35%,transparent)] bg-[var(--ep-neg-bg)] text-[var(--ep-neg)]'
                            : 'border-[color-mix(in_oklab,var(--ep-pos)_35%,transparent)] bg-[var(--ep-pos-bg)] text-[var(--ep-pos)]',
                    ]"
                >
                    <XCircle v-if="isError" class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                    <CheckCircle2 v-else class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                </span>
                <p
                    :class="[
                        'min-w-0 flex-1 pt-[5px] text-[13px] font-medium leading-[1.45]',
                        isError ? 'text-[var(--ep-text)]' : 'text-[var(--ep-text-2)]',
                    ]"
                >
                    {{ message }}
                </p>
                <button
                    type="button"
                    class="ep-btn-ghost ep-btn-icon h-7 w-7 shrink-0 rounded-lg text-[var(--ep-text-4)]"
                    aria-label="Fechar"
                    @click="close"
                >
                    <X class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                </button>
            </div>
        </Transition>
    </Teleport>
</template>
