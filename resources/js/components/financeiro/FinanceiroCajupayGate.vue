<script setup>
import { Link } from '@inertiajs/vue3';
import { Lock, PlugZap } from 'lucide-vue-next';

defineProps({
    connected: { type: Boolean, default: false },
    /** producer = link para integrações; partner = mensagem para aguardar produtor */
    variant: {
        type: String,
        default: 'producer',
        validator: (v) => ['producer', 'partner'].includes(v),
    },
    gatewaysUrl: { type: String, default: '/integracoes?tab=gateways&gateway=cajupay' },
});
</script>

<template>
    <div class="relative">
        <div
            class="transition duration-300"
            :class="connected ? '' : 'pointer-events-none select-none opacity-35 blur-[3px] saturate-50'"
            :aria-hidden="!connected"
        >
            <slot />
        </div>

        <div
            v-if="!connected"
            class="absolute inset-0 z-10 flex items-start justify-center rounded-[20px] p-4 pt-10 sm:p-8 sm:pt-20"
        >
            <div
                class="ep-modal w-full max-w-[440px] p-6 sm:p-7"
                role="alertdialog"
                aria-labelledby="cajupay-gate-title"
                aria-describedby="cajupay-gate-desc"
            >
                <span class="ep-chip ep-chip--warn">
                    <Lock class="h-3.5 w-3.5" :stroke-width="1.75" aria-hidden="true" />
                    CajuPay desconectada
                </span>

                <h2
                    id="cajupay-gate-title"
                    class="mt-4 text-[19px] font-semibold tracking-[-0.025em] text-[var(--ep-text)]"
                >
                    Saques indisponíveis
                </h2>

                <p
                    id="cajupay-gate-desc"
                    class="mt-2 text-[13px] leading-relaxed text-[var(--ep-text-3)]"
                >
                    <template v-if="variant === 'producer'">
                        A API de saque via PIX só funciona com a
                        <strong class="font-medium text-[var(--ep-text)]">CajuPay</strong>
                        conectada e ativa. Você pode consultar saldos abaixo, mas solicitar saques e
                        aprovar repasses de parceiros ficará bloqueado até a integração.
                    </template>
                    <template v-else>
                        Os saques são processados pela CajuPay da conta do produtor. Enquanto ele não
                        conectar o gateway, você pode acompanhar comissões, mas não solicitar saques.
                    </template>
                </p>

                <div
                    v-if="variant === 'producer'"
                    class="mt-6 flex flex-col gap-3 border-t border-[var(--ep-line)] pt-5 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p class="text-[12px] text-[var(--ep-text-4)]">
                        Integrações → Gateways → CajuPay
                    </p>
                    <Link
                        :href="gatewaysUrl"
                        class="ep-btn w-full sm:w-auto"
                    >
                        <PlugZap class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                        Configurar CajuPay
                    </Link>
                </div>

                <p
                    v-else
                    class="mt-5 rounded-xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] px-3 py-2.5 text-[12.5px] text-[var(--ep-text-3)]"
                >
                    Entre em contato com o produtor do produto para habilitar os saques.
                </p>
            </div>
        </div>
    </div>
</template>
