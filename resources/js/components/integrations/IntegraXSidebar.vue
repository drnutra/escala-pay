<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';
import Button from '@/components/ui/Button.vue';
import Toggle from '@/components/ui/Toggle.vue';
import { X, Loader2, ExternalLink, MessageCircle } from 'lucide-vue-next';

const INTEGRAX_SUPPORT_WHATSAPP_URL =
    'https://wa.me/551132808396?text=' +
    encodeURIComponent(
        'Olá! Criei uma conta na IntegraX e gerei um token de API. Gostaria de solicitar a ativação do token para uso via Getfy.',
    );

const props = defineProps({
    open: { type: Boolean, default: false },
    integrax_connection: {
        type: Object,
        default: () => ({
            configured: false,
            is_active: false,
            has_token: false,
            api_token: '',
            last_tested_at: null,
            last_error: null,
        }),
    },
});

const emit = defineEmits(['close', 'saved']);

const form = ref({
    api_token: '',
    is_active: true,
});
const testPhone = ref('');
const testMessage = ref('Teste IntegraX via Getfy');
const saving = ref(false);
const testing = ref(false);
const errorMessage = ref(null);
const successMessage = ref(null);

watch(
    () => [props.open, props.integrax_connection],
    () => {
        if (props.open) {
            form.value = {
                api_token: props.integrax_connection?.api_token ?? '',
                is_active: props.integrax_connection?.is_active ?? true,
            };
            errorMessage.value = null;
            successMessage.value = null;
        }
    },
    { immediate: true },
);

async function save() {
    errorMessage.value = null;
    successMessage.value = null;
    if (!form.value.api_token?.trim() && !props.integrax_connection?.configured) {
        errorMessage.value = 'Informe o token da API IntegraX.';
        return;
    }
    saving.value = true;
    try {
        await axios.put('/integracoes/integrax', {
            api_token: form.value.api_token?.trim() || undefined,
            is_active: form.value.is_active,
        });
        successMessage.value = 'Conexão salva com sucesso.';
        emit('saved');
    } catch (e) {
        errorMessage.value = e?.response?.data?.message || 'Falha ao salvar.';
    } finally {
        saving.value = false;
    }
}

async function testConnection() {
    errorMessage.value = null;
    successMessage.value = null;
    if (!testPhone.value.trim()) {
        errorMessage.value = 'Informe um telefone para o teste (com DDD).';
        return;
    }
    testing.value = true;
    try {
        const res = await axios.post('/integracoes/integrax/test', {
            phone: testPhone.value.trim(),
            message: testMessage.value.trim() || undefined,
        });
        if (res.data?.success) {
            successMessage.value = 'SMS de teste enviado com sucesso.';
            emit('saved');
        } else {
            errorMessage.value = res.data?.message || 'Falha no teste.';
        }
    } catch (e) {
        errorMessage.value = e?.response?.data?.message || 'Falha no teste.';
    } finally {
        testing.value = false;
    }
}
</script>

<template>
    <Teleport to="body">
        <div
            v-show="open"
            class="fixed inset-0 z-[100000] flex justify-end"
            aria-modal="true"
            role="dialog"
        >
            <div
                class="ep-scrim fixed inset-0"
                aria-hidden="true"
                @click="emit('close')"
            />
            <aside class="ep-drawer relative flex h-full w-full max-w-md flex-col">
                <div class="flex items-center justify-between gap-3 border-b border-[var(--ep-line)] px-6 py-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-[13px] border border-[var(--ep-glass-border)] bg-[var(--ep-glass-strong)] p-[3px] shadow-[var(--ep-glass-highlight),0_8px_22px_-12px_var(--ep-glow)]">
                            <img src="/images/integrations/integrax.png" alt="" class="size-full rounded-[10px] object-cover" />
                        </span>
                        <div class="min-w-0">
                            <h2 class="text-[16px] font-semibold tracking-[-0.02em] text-[var(--ep-text)]">IntegraX</h2>
                            <p class="text-[12px] text-[var(--ep-text-3)]">SMS — provedor de disparo</p>
                        </div>
                    </div>
                    <button type="button" class="ep-btn-ghost ep-btn-icon shrink-0" aria-label="Fechar" @click="emit('close')">
                        <X class="h-[18px] w-[18px]" :stroke-width="1.75" />
                    </button>
                </div>

                <div class="flex-1 space-y-5 overflow-y-auto px-6 py-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="max-w-[300px] text-[12.5px] leading-[1.5] text-[var(--ep-text-3)]">
                            Cole o token da API para habilitar envios SMS. As mensagens e eventos são configurados por produto, na aba SMS.
                        </p>
                        <a
                            href="https://www.integrax.app/auth/register"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1.5 text-[12.5px] font-medium text-[var(--ep-accent)] transition-opacity hover:opacity-80"
                        >
                            Criar conta na IntegraX
                            <ExternalLink class="h-3.5 w-3.5" :stroke-width="1.75" />
                        </a>
                    </div>

                    <div>
                        <label class="ep-label">Token da API</label>
                        <input
                            v-model="form.api_token"
                            type="password"
                            autocomplete="off"
                            class="ep-input font-mono"
                            placeholder="Token do painel IntegraX"
                        />
                    </div>

                    <div class="rounded-[14px] border border-[color-mix(in_oklab,var(--ep-accent)_28%,transparent)] bg-[color-mix(in_oklab,var(--ep-accent)_8%,transparent)] p-4">
                        <p class="text-[13px] font-medium text-[var(--ep-text)]">Ativação do token</p>
                        <p class="mt-1.5 text-[12.5px] leading-[1.55] text-[var(--ep-text-2)]">
                            Cada token de API gerado na IntegraX precisa ser ativado pelo suporte antes de enviar SMS.
                            Após gerar o token no painel da IntegraX, entre em contato para solicitar a ativação.
                        </p>
                        <a
                            :href="INTEGRAX_SUPPORT_WHATSAPP_URL"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="ep-btn-secondary mt-4 w-full"
                        >
                            <MessageCircle class="h-4 w-4 shrink-0 text-[#25D366]" :stroke-width="1.75" aria-hidden="true" />
                            Solicitar ativação no WhatsApp
                        </a>
                        <p class="mt-2 text-center text-[12px] tabular-nums text-[var(--ep-text-4)]">
                            +55 11 3280-8396
                        </p>
                    </div>

                    <div class="flex items-center justify-between gap-4 rounded-[14px] border border-[var(--ep-line)] bg-[var(--ep-card-2)] px-4 py-3">
                        <div>
                            <p class="text-[13px] font-medium text-[var(--ep-text)]">Integração ativa</p>
                            <p class="text-[12px] text-[var(--ep-text-3)]">Desative para pausar todos os envios SMS</p>
                        </div>
                        <Toggle v-model="form.is_active" />
                    </div>

                    <div v-if="integrax_connection.last_error" class="rounded-[12px] border border-[color-mix(in_oklab,var(--ep-warn)_35%,transparent)] bg-[var(--ep-warn-bg)] px-3 py-2.5 text-[12.5px] text-[var(--ep-warn)]">
                        Último erro: {{ integrax_connection.last_error }}
                    </div>

                    <div class="border-t border-[var(--ep-line)] pt-5">
                        <h3 class="ep-section-title mb-3">Testar envio</h3>
                        <div class="space-y-3">
                            <input
                                v-model="testPhone"
                                type="text"
                                class="ep-input tabular-nums"
                                placeholder="Telefone com DDD (ex: 11999999999)"
                            />
                            <input
                                v-model="testMessage"
                                type="text"
                                maxlength="160"
                                class="ep-input"
                                placeholder="Mensagem de teste (máx. 160)"
                            />
                            <p class="ep-help !mt-1.5 text-right tabular-nums">{{ testMessage.length }}/160 caracteres</p>
                        </div>
                    </div>

                    <div v-if="errorMessage" class="rounded-[12px] border border-[color-mix(in_oklab,var(--ep-neg)_35%,transparent)] bg-[var(--ep-neg-bg)] px-3 py-2.5 text-[12.5px] text-[var(--ep-neg)]">
                        {{ errorMessage }}
                    </div>
                    <div v-if="successMessage" class="rounded-[12px] border border-[color-mix(in_oklab,var(--ep-pos)_35%,transparent)] bg-[var(--ep-pos-bg)] px-3 py-2.5 text-[12.5px] text-[var(--ep-pos)]">
                        {{ successMessage }}
                    </div>
                </div>

                <div class="flex flex-wrap gap-2.5 border-t border-[var(--ep-line)] px-6 py-4">
                    <Button type="button" class="flex-1" :disabled="saving" @click="save">
                        <Loader2 v-if="saving" class="h-4 w-4 animate-spin" />
                        Salvar
                    </Button>
                    <button
                        type="button"
                        :disabled="testing"
                        class="ep-btn-secondary"
                        @click="testConnection"
                    >
                        {{ testing ? 'Enviando...' : 'Enviar teste' }}
                    </button>
                </div>
            </aside>
        </div>
    </Teleport>
</template>
