<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import Button from '@/components/ui/Button.vue';
import GatewayRedundancySidebar from '@/components/produtos/GatewayRedundancySidebar.vue';
import { ChevronDown, Settings2, Palette } from 'lucide-vue-next';
import { ArrowLeft, Webhook, ShieldCheck, KeyRound, Lock, Layers, Tag, RotateCcw } from 'lucide-vue-next';

defineOptions({ layout: LayoutInfoprodutor });

const props = defineProps({
    gateways_by_method: { type: Object, default: () => ({}) },
    default_payment_gateways: { type: Object, default: () => ({}) },
});

const pg = props.default_payment_gateways || {};
const form = useForm({
    name: '',
    payment_gateways: {
        pix: pg.pix ?? '',
        pix_redundancy: Array.isArray(pg.pix_redundancy) ? pg.pix_redundancy : [],
        card: pg.card ?? '',
        card_redundancy: Array.isArray(pg.card_redundancy) ? pg.card_redundancy : [],
        boleto: pg.boleto ?? '',
        boleto_redundancy: Array.isArray(pg.boleto_redundancy) ? pg.boleto_redundancy : [],
        pix_auto: pg.pix_auto ?? '',
        pix_auto_redundancy: Array.isArray(pg.pix_auto_redundancy) ? pg.pix_auto_redundancy : [],
        apple_pay: pg.apple_pay ?? '',
        apple_pay_redundancy: Array.isArray(pg.apple_pay_redundancy) ? pg.apple_pay_redundancy : [],
        google_pay: pg.google_pay ?? '',
        google_pay_redundancy: Array.isArray(pg.google_pay_redundancy) ? pg.google_pay_redundancy : [],
        crypto: pg.crypto ?? '',
        crypto_redundancy: Array.isArray(pg.crypto_redundancy) ? pg.crypto_redundancy : [],
    },
    webhook_url: '',
    default_return_url: '',
    webhook_secret: '',
    allowed_ips: '',
    is_active: true,
    checkout_sidebar_bg: '',
});

function gatewayOptions(method) {
    const list = props.gateways_by_method?.[method] ?? [];
    return [
        { value: '', label: 'Nenhum' },
        ...list.map((g) => ({ value: g.slug, label: g.name })),
    ];
}

const METHOD_LABELS = { pix: 'PIX', card: 'Cartão', boleto: 'Boleto', pix_auto: 'PIX automático', apple_pay: 'Apple Pay', google_pay: 'Google Pay', crypto: 'Criptomoeda' };
const redundancySidebarOpen = ref(false);
const redundancySidebarMethod = ref(null);

function openRedundancySidebar(method) {
    redundancySidebarMethod.value = method;
    redundancySidebarOpen.value = true;
}

function canShowRedundancy(slug) {
    return slug !== '' && slug != null;
}

function submit() {
    form.post('/aplicacoes-api');
}
</script>

<template>
    <div class="space-y-5">
        <!-- Cabeçalho -->
        <div class="flex flex-col gap-3">
            <a
                href="/aplicacoes-api"
                class="inline-flex w-fit items-center gap-1.5 text-[12.5px] font-medium text-[var(--ep-text-3)] transition-colors duration-150 hover:text-[var(--ep-text)]"
            >
                <ArrowLeft class="h-3.5 w-3.5" :stroke-width="1.75" />
                API de Pagamentos
            </a>
            <div>
                <h1 class="text-[22px] font-semibold leading-tight tracking-[-0.025em] text-[var(--ep-text)]">Nova aplicação API</h1>
                <p class="mt-1 max-w-xl text-[13px] leading-relaxed text-[var(--ep-text-3)]">
                    Configure os gateways que esta aplicação poderá usar para processar pagamentos.
                </p>
            </div>
        </div>

        <form class="grid grid-cols-1 items-start gap-4 xl:grid-cols-[minmax(0,1fr)_300px]" @submit.prevent="submit">
            <div class="min-w-0 space-y-4">
                <!-- Identificação -->
                <section class="panel-card p-5 sm:p-6">
                    <h2 class="ep-section-title flex items-center gap-2">
                        <Tag class="h-4 w-4 text-[var(--ep-text-3)]" :stroke-width="1.75" />
                        Identificação
                    </h2>
                    <div class="mt-4">
                        <label for="api-app-name" class="ep-label">Nome</label>
                        <input id="api-app-name" v-model="form.name" type="text" required class="ep-input" placeholder="Ex.: Loja XYZ" />
                        <p v-if="form.errors.name" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">{{ form.errors.name }}</p>
                    </div>
                </section>

                <!-- Aparência do checkout -->
                <section class="panel-card p-5 sm:p-6">
                    <h2 class="ep-section-title flex items-center gap-2">
                        <Palette class="h-4 w-4 text-[var(--ep-text-3)]" :stroke-width="1.75" />
                        Cor de fundo do checkout
                    </h2>
                    <p class="ep-help !mt-1">Cor da coluna esquerda (resumo) no Checkout Pro.</p>
                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <input
                            :value="form.checkout_sidebar_bg || '#18181b'"
                            type="color"
                            class="h-[38px] w-12 cursor-pointer rounded-[10px] border border-[var(--ep-input-border)] bg-[var(--ep-input)] p-1"
                            :title="form.checkout_sidebar_bg || '#18181b'"
                            @input="form.checkout_sidebar_bg = $event.target.value"
                        />
                        <input
                            v-model="form.checkout_sidebar_bg"
                            type="text"
                            class="ep-input !w-32 font-mono !text-[12.5px] tabular-nums"
                            placeholder="#18181b"
                            maxlength="7"
                        />
                        <button type="button" class="ep-btn-ghost !h-[38px]" @click="form.checkout_sidebar_bg = ''">
                            <RotateCcw class="h-3.5 w-3.5" :stroke-width="1.75" />
                            Restaurar padrão
                        </button>
                    </div>
                    <p v-if="form.errors.checkout_sidebar_bg" class="mt-2 text-[12px] text-[var(--ep-neg)]">{{ form.errors.checkout_sidebar_bg }}</p>
                </section>

                <!-- Gateways -->
                <section class="panel-card p-5 sm:p-6">
                    <h2 class="ep-section-title flex items-center gap-2">
                        <Settings2 class="h-4 w-4 text-[var(--ep-text-3)]" :stroke-width="1.75" />
                        Gateways por método de pagamento
                    </h2>
                    <p class="ep-help !mt-1">
                        Selecione o gateway principal e a ordem de redundância para cada método.
                    </p>
                    <div class="mt-4 overflow-hidden rounded-[14px] border border-[var(--ep-line)] bg-[var(--ep-card-2)]">
                        <template v-for="method in ['pix', 'card', 'boleto', 'apple_pay', 'google_pay', 'pix_auto', 'crypto']" :key="method">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-2 border-b border-[var(--ep-line)] px-4 py-3 last:border-b-0">
                                <span class="flex w-32 shrink-0 items-center gap-2 text-[13px] font-medium text-[var(--ep-text-2)]">
                                    <span
                                        class="h-2 w-2 shrink-0 rounded-full"
                                        :class="method === 'pix' ? 'bg-[var(--ep-pix)]' : method === 'card' ? 'bg-[var(--ep-cartao)]' : method === 'boleto' ? 'bg-[var(--ep-boleto)]' : 'bg-[var(--ep-text-4)]'"
                                    />
                                    {{ METHOD_LABELS[method] || method }}
                                </span>
                                <select
                                    v-model="form.payment_gateways[method]"
                                    class="ep-input min-w-[180px] flex-1 sm:max-w-xs"
                                >
                                    <option v-for="opt in gatewayOptions(method)" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                </select>
                                <button
                                    v-if="canShowRedundancy(form.payment_gateways[method])"
                                    type="button"
                                    class="ep-btn-secondary !h-8 !px-3 !text-[12.5px]"
                                    @click="openRedundancySidebar(method)"
                                >
                                    <Layers class="h-3.5 w-3.5" :stroke-width="1.75" />
                                    Redundância
                                </button>
                            </div>
                        </template>
                    </div>
                </section>

                <!-- Webhooks e segurança -->
                <section class="panel-card p-5 sm:p-6">
                    <h2 class="ep-section-title flex items-center gap-2">
                        <Webhook class="h-4 w-4 text-[var(--ep-text-3)]" :stroke-width="1.75" />
                        Webhooks e retorno
                    </h2>
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="min-w-0">
                            <label for="api-app-webhook-url" class="ep-label">URL do webhook (opcional)</label>
                            <input id="api-app-webhook-url" v-model="form.webhook_url" type="url" class="ep-input font-mono !text-[12.5px]" placeholder="https://..." />
                            <p class="ep-help">Receberá notificações de pagamento (order.completed, etc.).</p>
                            <p v-if="form.errors.webhook_url" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">{{ form.errors.webhook_url }}</p>
                        </div>
                        <div class="min-w-0">
                            <label for="api-app-return-url" class="ep-label">URL de retorno padrão (opcional)</label>
                            <input id="api-app-return-url" v-model="form.default_return_url" type="url" class="ep-input font-mono !text-[12.5px]" placeholder="https://..." />
                            <p class="ep-help">
                                Usada no Checkout Pro quando a sessão não enviar <code class="rounded-md bg-[var(--ep-card-2)] px-1 py-px font-mono text-[11.5px] text-[var(--ep-text-3)]">return_url</code>.
                            </p>
                            <p v-if="form.errors.default_return_url" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">{{ form.errors.default_return_url }}</p>
                        </div>
                    </div>

                    <div class="ep-divider my-5" />

                    <h3 class="ep-section-title flex items-center gap-2">
                        <ShieldCheck class="h-4 w-4 text-[var(--ep-text-3)]" :stroke-width="1.75" />
                        Segurança
                    </h3>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label for="api-app-webhook-secret" class="ep-label">Webhook secret (opcional)</label>
                            <input id="api-app-webhook-secret" v-model="form.webhook_secret" type="password" autocomplete="off" class="ep-input font-mono" placeholder="Secret para validar assinatura HMAC" />
                            <p class="ep-help">Usado para assinar o body do webhook (<span class="font-mono">X-Getfy-Signature</span>). Recomendado para produção.</p>
                            <p v-if="form.errors.webhook_secret" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">{{ form.errors.webhook_secret }}</p>
                        </div>

                        <div>
                            <label for="api-app-allowed-ips" class="ep-label">IPs permitidos (opcional)</label>
                            <textarea id="api-app-allowed-ips" v-model="form.allowed_ips" rows="3" class="ep-input font-mono !text-[12.5px]" placeholder="Um IP por linha ou separados por vírgula. Vazio = todos permitidos."></textarea>
                            <p v-if="form.errors.allowed_ips" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">{{ form.errors.allowed_ips }}</p>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Lateral: publicação + credenciais -->
            <aside class="min-w-0 space-y-4 xl:sticky xl:top-6">
                <section class="panel-card ep-glow-card p-5">
                    <h2 class="ep-section-title">Publicação</h2>
                    <div class="mt-3 flex items-start gap-3 rounded-[12px] border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-3">
                        <input v-model="form.is_active" type="checkbox" id="is_active" class="mt-0.5 h-4 w-4 shrink-0 cursor-pointer rounded accent-[var(--ep-accent)]" />
                        <label for="is_active" class="cursor-pointer">
                            <span class="block text-[13px] font-medium text-[var(--ep-text)]">Aplicação ativa</span>
                            <span class="mt-0.5 block text-[12px] text-[var(--ep-text-4)]">Você pode alterar isso depois.</span>
                        </label>
                    </div>
                    <div class="ep-divider my-4" />
                    <div class="flex flex-col gap-2">
                        <button type="submit" class="ep-btn w-full" :disabled="form.processing">Criar aplicação</button>
                        <a href="/aplicacoes-api" class="ep-btn-ghost w-full">Cancelar</a>
                    </div>
                </section>

                <section class="panel-card p-5">
                    <h2 class="ep-section-title flex items-center gap-2">
                        <KeyRound class="h-4 w-4 text-[var(--ep-text-3)]" :stroke-width="1.75" />
                        API key
                    </h2>
                    <div class="mt-3 flex h-9 items-center gap-2 rounded-[10px] border border-[var(--ep-input-border)] bg-[var(--ep-input)] px-3 font-mono text-[12.5px] text-[var(--ep-text-4)]">
                        <Lock class="h-3.5 w-3.5 shrink-0" :stroke-width="1.75" aria-hidden="true" />
                        <span class="truncate tracking-[0.2em]" aria-label="API key ainda não gerada">••••••••••••••••••••</span>
                    </div>
                    <p class="ep-help leading-relaxed">
                        Gerada ao criar a aplicação e exibida uma única vez. Copie e guarde em local seguro.
                    </p>
                </section>
            </aside>
        </form>

        <GatewayRedundancySidebar
            :open="redundancySidebarOpen"
            :method="redundancySidebarMethod"
            :method-label="METHOD_LABELS[redundancySidebarMethod] || redundancySidebarMethod"
            :primary-slug="redundancySidebarMethod ? (form.payment_gateways[redundancySidebarMethod] || '') : ''"
            :gateways="gateways_by_method[redundancySidebarMethod] || []"
            :model-value="redundancySidebarMethod ? (form.payment_gateways[redundancySidebarMethod + '_redundancy'] || []) : []"
            @update:model-value="(val) => redundancySidebarMethod && (form.payment_gateways[redundancySidebarMethod + '_redundancy'] = val)"
            @save="(val) => { if (redundancySidebarMethod) { form.payment_gateways[redundancySidebarMethod + '_redundancy'] = val; } redundancySidebarOpen = false; }"
            @close="redundancySidebarOpen = false"
        />
    </div>
</template>
