<script setup>
import { Link } from '@inertiajs/vue3';
import { Info } from 'lucide-vue-next';

defineProps({
    variant: {
        type: String,
        default: 'producer',
        validator: (v) => ['producer', 'partner'].includes(v),
    },
    gatewaysUrl: { type: String, default: '/integracoes?tab=gateways&gateway=cajupay' },
});
</script>

<template>
    <p
        class="inline-flex max-w-full flex-wrap items-center gap-x-2 gap-y-1 rounded-xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] px-3 py-2 text-[12px] leading-relaxed text-[var(--ep-text-3)]"
        role="note"
    >
        <Info class="h-3.5 w-3.5 shrink-0 text-[var(--ep-accent)]" :stroke-width="1.75" aria-hidden="true" />
        <span>
            <template v-if="variant === 'producer'">
                Saques e repasses de parceiros usam exclusivamente a
                <span class="font-medium text-[var(--ep-text-2)]">API CajuPay</span>
                (transferência PIX).
            </template>
            <template v-else>
                Saques via PIX são processados pela
                <span class="font-medium text-[var(--ep-text-2)]">API CajuPay</span>
                da conta do produtor.
            </template>
        </span>
        <Link
            v-if="variant === 'producer'"
            :href="gatewaysUrl"
            class="shrink-0 font-medium text-[var(--ep-accent)] underline-offset-2 transition-colors duration-150 hover:underline"
        >
            Ver integração
        </Link>
    </p>
</template>
