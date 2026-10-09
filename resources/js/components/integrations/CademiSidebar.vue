<script setup>
import { computed, ref, watch } from 'vue';
import axios from 'axios';
import Button from '@/components/ui/Button.vue';
import Toggle from '@/components/ui/Toggle.vue';
import { X, Plus, Pencil, Trash2, ArrowLeft, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    open: { type: Boolean, default: false },
    cademi_integrations: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'saved']);

const editingIntegration = ref(null);
const isCreating = ref(false);

const showingForm = computed(
    () => editingIntegration.value !== null || isCreating.value
);

const form = ref({
    name: '',
    base_url: '',
    api_key: '', // legacy (not shown)
    postback_token: '',
    is_active: true,
    product_ids: [], // kept for backward compatibility (not shown in UI)
});
const saving = ref(false);
const deleting = ref(null);
const confirmingDeleteId = ref(null);
const errorMessage = ref(null);

watch(
    () => [props.open, props.cademi_integrations],
    () => {
        if (!props.open) resetForm();
    }
);

function resetForm() {
    editingIntegration.value = null;
    isCreating.value = false;
    confirmingDeleteId.value = null;
    form.value = {
        name: '',
        base_url: '',
        api_key: '',
        postback_token: '',
        is_active: true,
        product_ids: [],
    };
    errorMessage.value = null;
}

function startNew() {
    editingIntegration.value = null;
    isCreating.value = true;
    form.value = {
        name: '',
        base_url: '',
        api_key: '',
        postback_token: '',
        is_active: true,
        product_ids: [],
    };
    errorMessage.value = null;
}

function editIntegration(integration) {
    isCreating.value = false;
    editingIntegration.value = integration;
    form.value = {
        name: integration.name,
        base_url: integration.base_url ?? '',
        api_key: integration.api_key ?? '',
        postback_token: integration.postback_token ?? '',
        is_active: integration.is_active ?? true,
        product_ids: [...(integration.product_ids || [])],
    };
    errorMessage.value = null;
}

function cancelEdit() {
    resetForm();
}

// product_ids kept only for backward compatibility in API payloads

async function save() {
    errorMessage.value = null;
    if (!form.value.name?.trim()) {
        errorMessage.value = 'Informe o nome da integração.';
        return;
    }
    if (!form.value.base_url?.trim()) {
        errorMessage.value = 'Informe a Base URL (ex.: https://seu-subdominio.cademi.com.br).';
        return;
    }
    if (isCreating.value) {
        if (!form.value.postback_token?.trim()) {
            errorMessage.value = 'Informe o Token de Postback.';
            return;
        }
    }

    saving.value = true;
    try {
        const payload = {
            name: form.value.name.trim(),
            base_url: form.value.base_url.trim().replace(/\/+$/, ''),
            is_active: form.value.is_active,
            product_ids: form.value.product_ids,
        };
        // api_key hidden (legacy)
        if (form.value.api_key?.trim()) payload.api_key = form.value.api_key.trim();
        if (form.value.postback_token?.trim())
            payload.postback_token = form.value.postback_token.trim();

        if (editingIntegration.value) {
            await axios.put(
                `/integracoes/cademi/${editingIntegration.value.id}`,
                payload
            );
        } else {
            if (!payload.postback_token) {
                errorMessage.value = 'Informe o Token de Postback.';
                saving.value = false;
                return;
            }
            await axios.post('/integracoes/cademi', payload);
        }
        emit('saved');
        resetForm();
    } catch (err) {
        errorMessage.value =
            err.response?.data?.message || 'Erro ao salvar integração.';
    } finally {
        saving.value = false;
    }
}

function requestDelete(integration) {
    confirmingDeleteId.value = integration.id;
}

function cancelDelete() {
    confirmingDeleteId.value = null;
}

async function confirmRemove(integration) {
    if (!integration) return;
    deleting.value = integration.id;
    confirmingDeleteId.value = null;
    try {
        await axios.delete(`/integracoes/cademi/${integration.id}`);
        emit('saved');
        if (editingIntegration.value?.id === integration.id) resetForm();
    } catch (err) {
        errorMessage.value =
            err.response?.data?.message || 'Erro ao excluir integração.';
    } finally {
        deleting.value = null;
    }
}

function close() {
    emit('close');
}

function productSummary(integration) {
    return 'Configurar no produto';
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
                @click="close"
            />
            <aside
                class="ep-drawer relative flex h-full w-full max-w-lg flex-col"
            >
                <div
                    class="flex items-center justify-between gap-3 border-b border-[var(--ep-line)] px-6 py-4"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-[13px] border border-[var(--ep-glass-border)] bg-[var(--ep-glass-strong)] p-[3px] shadow-[var(--ep-glass-highlight),0_8px_22px_-12px_var(--ep-glow)]">
                            <img src="/images/integrations/cademi.png" alt="" class="size-full rounded-[10px] object-cover" />
                        </span>
                        <div class="min-w-0">
                            <h2 class="text-[16px] font-semibold tracking-[-0.02em] text-[var(--ep-text)]">
                                Cademí
                            </h2>
                            <p class="text-[12px] text-[var(--ep-text-3)]">Área de membros externa</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="ep-btn-ghost ep-btn-icon shrink-0"
                        aria-label="Fechar"
                        @click="close"
                    >
                        <X class="h-[18px] w-[18px]" :stroke-width="1.75" />
                    </button>
                </div>

                <div class="flex flex-1 flex-col overflow-y-auto px-6 py-5">
                    <p class="mb-5 text-[12.5px] leading-[1.55] text-[var(--ep-text-3)]">
                        Conecte sua Cademí para usar como área de membros externa e conceder acesso automaticamente após a compra.
                    </p>

                    <template v-if="!showingForm">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h3 class="ep-section-title">Conexões</h3>
                            <Button variant="outline" size="sm" @click="startNew">
                                <Plus class="h-3.5 w-3.5" :stroke-width="1.75" />
                                Nova integração
                            </Button>
                        </div>

                        <ul v-if="cademi_integrations.length" class="space-y-2">
                            <li
                                v-for="i in cademi_integrations"
                                :key="i.id"
                                class="flex items-center justify-between gap-3 rounded-[14px] border border-[var(--ep-line)] bg-[var(--ep-card-2)] px-4 py-3 transition-colors duration-150 hover:border-[var(--ep-line-strong)] hover:bg-[var(--ep-hover)]"
                            >
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="truncate text-[13.5px] font-medium text-[var(--ep-text)]">
                                            {{ i.name }}
                                        </span>
                                        <span
                                            v-if="i.is_active"
                                            class="ep-chip ep-chip--pos shrink-0"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                                            Ativo
                                        </span>
                                        <span
                                            v-else
                                            class="ep-chip shrink-0"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full bg-current opacity-60" aria-hidden="true" />
                                            Inativo
                                        </span>
                                    </div>
                                    <p class="mt-1 text-[12px] text-[var(--ep-text-3)]">
                                        {{ i.configured ? 'Chave configurada' : 'Chave não configurada' }} · {{ productSummary(i) }}
                                    </p>
                                    <p v-if="i.base_url" class="mt-0.5 truncate font-mono text-[11.5px] text-[var(--ep-text-4)]">
                                        {{ i.base_url }}
                                    </p>
                                </div>
                                <div class="flex shrink-0 items-center gap-0.5">
                                    <button
                                        type="button"
                                        class="ep-btn-ghost ep-btn-icon !h-8 !w-8 !rounded-[10px]"
                                        aria-label="Editar"
                                        @click="editIntegration(i)"
                                    >
                                        <Pencil class="h-4 w-4" :stroke-width="1.75" />
                                    </button>
                                    <button
                                        type="button"
                                        class="ep-btn-ghost ep-btn-icon !h-8 !w-8 !rounded-[10px] hover:!bg-[var(--ep-neg-bg)] hover:!text-[var(--ep-neg)]"
                                        aria-label="Excluir"
                                        @click="requestDelete(i)"
                                    >
                                        <Trash2 class="h-4 w-4" :stroke-width="1.75" />
                                    </button>
                                </div>
                            </li>
                        </ul>
                        <p
                            v-else
                            class="ep-empty rounded-[14px] border border-dashed border-[var(--ep-line-strong)] bg-[var(--ep-card-2)] !text-[12.5px] !text-[var(--ep-text-3)]"
                        >
                            <span class="ep-empty__title block">Nenhuma integração configurada.</span>
                            <span class="ep-empty__text block">Clique em "Nova integração" para começar.</span>
                        </p>
                    </template>

                    <template v-else>
                        <div class="mb-4 flex items-center gap-2">
                            <button
                                type="button"
                                class="ep-btn-ghost ep-btn-icon !h-8 !w-8 !rounded-[10px]"
                                aria-label="Voltar"
                                @click="cancelEdit"
                            >
                                <ArrowLeft class="h-[18px] w-[18px]" :stroke-width="1.75" />
                            </button>
                            <span class="text-[14px] font-semibold tracking-[-0.01em] text-[var(--ep-text)]">
                                {{ isCreating ? 'Nova integração' : 'Editar integração' }}
                            </span>
                        </div>

                        <p class="mb-5 rounded-[12px] border border-[var(--ep-line)] bg-[var(--ep-card-2)] px-3 py-2.5 text-[12px] leading-[1.55] text-[var(--ep-text-3)] [&_a]:font-medium [&_a]:text-[var(--ep-accent)] [&_strong]:font-medium [&_strong]:text-[var(--ep-text-2)]">
                            Recomendado: Postback em <span class="font-mono">{{ form.base_url || 'https://(seu-subdominio).cademi.com.br' }}/api/postback/custom</span>
                            usando o Token em ⚙️ → Configurações.
                        </p>

                        <div class="space-y-4">
                            <div>
                                <label
                                    for="cademi-name"
                                    class="ep-label"
                                >
                                    Nome da integração
                                </label>
                                <input
                                    id="cademi-name"
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Ex: Cademí Principal"
                                    class="ep-input"
                                />
                            </div>

                            <div>
                                <label
                                    for="cademi-base-url"
                                    class="ep-label"
                                >
                                    Base URL
                                </label>
                                <input
                                    id="cademi-base-url"
                                    v-model="form.base_url"
                                    type="text"
                                    autocomplete="off"
                                    placeholder="https://seu-subdominio.cademi.com.br"
                                    class="ep-input font-mono"
                                />
                            </div>

                            <div>
                                <label
                                    for="cademi-postback-token"
                                    class="ep-label"
                                >
                                    Token de Postback
                                </label>
                                <input
                                    id="cademi-postback-token"
                                    v-model="form.postback_token"
                                    type="text"
                                    autocomplete="off"
                                    :placeholder="editingIntegration ? 'Deixe em branco para manter o atual' : 'Digite o token'"
                                    class="ep-input font-mono"
                                />
                            </div>

                            <div class="flex items-center justify-between gap-4 rounded-[14px] border border-[var(--ep-line)] bg-[var(--ep-card-2)] px-4 py-3">
                                <div>
                                    <span class="block text-[13px] font-medium text-[var(--ep-text)]">
                                        Integração ativa
                                    </span>
                                    <span class="text-[12px] text-[var(--ep-text-3)]">
                                        Permitir sincronização com a Cademí
                                    </span>
                                </div>
                                <Toggle v-model="form.is_active" />
                            </div>
                        </div>

                        <p
                            v-if="errorMessage"
                            class="mt-4 rounded-[12px] border border-[color-mix(in_oklab,var(--ep-neg)_35%,transparent)] bg-[var(--ep-neg-bg)] px-3 py-2.5 text-[12.5px] text-[var(--ep-neg)]"
                        >
                            {{ errorMessage }}
                        </p>

                        <div class="mt-6 flex gap-2.5 border-t border-[var(--ep-line)] pt-5">
                            <Button class="flex-1" :disabled="saving" @click="save">
                                <Loader2 v-if="saving" class="h-4 w-4 animate-spin" />
                                Salvar
                            </Button>
                            <Button variant="outline" @click="cancelEdit">Cancelar</Button>
                        </div>
                    </template>
                </div>
            </aside>
        </div>
    </Teleport>

    <Teleport to="body">
        <div
            v-if="confirmingDeleteId"
            class="fixed inset-0 z-[100001] flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
        >
            <div class="ep-scrim fixed inset-0" @click="cancelDelete" />
            <div class="ep-modal relative w-full max-w-sm p-6">
                <p class="text-[15px] font-semibold tracking-[-0.015em] text-[var(--ep-text)]">Excluir integração?</p>
                <p class="mt-1.5 text-[13px] leading-[1.55] text-[var(--ep-text-3)]">
                    Deseja realmente excluir esta integração Cademí?
                </p>
                <div class="mt-6 flex justify-end gap-2.5">
                    <Button variant="outline" @click="cancelDelete">Cancelar</Button>
                    <Button
                        variant="danger"
                        class="ep-btn-danger !h-9"
                        :disabled="deleting !== null"
                        @click="confirmRemove(cademi_integrations.find(i => i.id === confirmingDeleteId))"
                    >
                        <Loader2 v-if="deleting === confirmingDeleteId" class="h-4 w-4 animate-spin" />
                        Excluir
                    </Button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

