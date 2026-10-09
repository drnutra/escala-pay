<script setup>
import { computed, ref, watch } from 'vue';
import axios from 'axios';
import Button from '@/components/ui/Button.vue';
import Checkbox from '@/components/ui/Checkbox.vue';
import Toggle from '@/components/ui/Toggle.vue';
import { X, Plus, Pencil, Trash2, ArrowLeft, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    open: { type: Boolean, default: false },
    spedy_integrations: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'saved']);

const editingIntegration = ref(null);
const isCreating = ref(false);

const showingForm = computed(
    () => editingIntegration.value !== null || isCreating.value
);

const form = ref({
    name: '',
    api_key: '',
    environment: 'production',
    is_active: true,
    product_ids: [],
});
const saving = ref(false);
const deleting = ref(null);
const confirmingDeleteId = ref(null);
const errorMessage = ref(null);

watch(
    () => [props.open, props.spedy_integrations],
    () => {
        if (!props.open) {
            resetForm();
        }
    }
);

function resetForm() {
    editingIntegration.value = null;
    isCreating.value = false;
    confirmingDeleteId.value = null;
    form.value = {
        name: '',
        api_key: '',
        environment: 'production',
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
        api_key: '',
        environment: 'production',
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
        api_key: integration.api_key ?? '',
        environment: integration.environment ?? 'production',
        is_active: integration.is_active ?? true,
        product_ids: [...(integration.product_ids || [])],
    };
    errorMessage.value = null;
}

function cancelEdit() {
    resetForm();
}

function toggleProduct(productId) {
    const idx = form.value.product_ids.indexOf(productId);
    if (idx >= 0) {
        form.value.product_ids.splice(idx, 1);
    } else {
        form.value.product_ids.push(productId);
    }
}

function isProductSelected(productId) {
    return form.value.product_ids.includes(productId);
}

async function save() {
    errorMessage.value = null;
    if (!form.value.name?.trim()) {
        errorMessage.value = 'Informe o nome da integração.';
        return;
    }
    if (isCreating.value && !form.value.api_key?.trim()) {
        errorMessage.value = 'Informe a chave de API ao criar uma integração.';
        return;
    }

    saving.value = true;
    try {
        const payload = {
            name: form.value.name.trim(),
            environment: form.value.environment,
            is_active: form.value.is_active,
            product_ids: form.value.product_ids,
        };
        if (form.value.api_key?.trim()) {
            payload.api_key = form.value.api_key.trim();
        }

        if (editingIntegration.value) {
            await axios.put(
                `/integracoes/spedy/${editingIntegration.value.id}`,
                payload
            );
        } else {
            if (!payload.api_key) {
                errorMessage.value = 'Informe a chave de API.';
                saving.value = false;
                return;
            }
            await axios.post('/integracoes/spedy', payload);
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
        await axios.delete(`/integracoes/spedy/${integration.id}`);
        emit('saved');
        if (editingIntegration.value?.id === integration.id) {
            resetForm();
        }
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
    if (!integration.product_ids?.length) return 'Todos os produtos';
    const n = integration.product_ids.length;
    return n === 1 ? '1 produto' : `${n} produtos`;
}

function environmentLabel(env) {
    return env === 'sandbox' ? 'Sandbox (testes)' : 'Produção';
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
                            <img src="/images/integrations/spedy.png" alt="" class="size-full rounded-[10px] object-cover" />
                        </span>
                        <div class="min-w-0">
                            <h2 class="text-[16px] font-semibold tracking-[-0.02em] text-[var(--ep-text)]">
                                Spedy
                            </h2>
                            <p class="text-[12px] text-[var(--ep-text-3)]">Notas fiscais automáticas</p>
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
                        Emissão automática de notas fiscais. Ao concluir uma venda, a Spedy recebe os dados e emite NF-e/NFS-e.
                    </p>

                    <!-- Lista de integrações -->
                    <template v-if="!showingForm">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h3 class="ep-section-title">Conexões</h3>
                            <Button variant="outline" size="sm" @click="startNew">
                                <Plus class="h-3.5 w-3.5" :stroke-width="1.75" />
                                Nova integração
                            </Button>
                        </div>

                        <ul v-if="spedy_integrations.length" class="space-y-2">
                            <li
                                v-for="i in spedy_integrations"
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
                                        {{ i.configured ? 'Chave configurada' : 'Chave não configurada' }} · {{ environmentLabel(i.environment) }} · {{ productSummary(i) }}
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

                    <!-- Formulário (novo ou editar) -->
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
                            Para obter a chave de API: acesse o
                            <a
                                href="https://app.spedy.com.br"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="underline hover:no-underline"
                            >Backoffice Spedy</a>
                            (produção) ou
                            <a
                                href="https://sandbox-app.spedy.com.br"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="underline hover:no-underline"
                            >Sandbox</a>, vá em <strong>Perfil → Minha empresa → Credenciais da API</strong>.
                        </p>

                        <div class="space-y-4">
                            <div>
                                <label
                                    for="spedy-name"
                                    class="ep-label"
                                >
                                    Nome da integração
                                </label>
                                <input
                                    id="spedy-name"
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Ex: Notas fiscais principal"
                                    class="ep-input"
                                />
                            </div>

                            <div>
                                <label
                                    for="spedy-api-key"
                                    class="ep-label"
                                >
                                    Chave de API
                                </label>
                                <input
                                    id="spedy-api-key"
                                    v-model="form.api_key"
                                    type="text"
                                    autocomplete="off"
                                    :placeholder="editingIntegration ? 'Deixe em branco para manter a atual' : 'Digite a chave'"
                                    class="ep-input font-mono"
                                />
                            </div>

                            <div>
                                <label
                                    for="spedy-environment"
                                    class="ep-label"
                                >
                                    Ambiente
                                </label>
                                <select
                                    id="spedy-environment"
                                    v-model="form.environment"
                                    class="ep-input"
                                >
                                    <option value="production">Produção</option>
                                    <option value="sandbox">Sandbox (testes)</option>
                                </select>
                            </div>

                            <div class="flex items-center justify-between gap-4 rounded-[14px] border border-[var(--ep-line)] bg-[var(--ep-card-2)] px-4 py-3">
                                <div>
                                    <span class="block text-[13px] font-medium text-[var(--ep-text)]">
                                        Integração ativa
                                    </span>
                                    <span class="text-[12px] text-[var(--ep-text-3)]">
                                        Emitir notas fiscais ao concluir vendas
                                    </span>
                                </div>
                                <Toggle v-model="form.is_active" />
                            </div>

                            <div class="text-left">
                                <span class="ep-label text-left">
                                    Produtos atribuídos
                                </span>
                                <p class="mb-2.5 text-left text-[12px] leading-[1.5] text-[var(--ep-text-4)]">
                                    Selecione os produtos para os quais esta integração emitirá notas. Deixe vazio para todos os produtos.
                                </p>
                                <div
                                    v-if="products.length"
                                    class="max-h-48 space-y-0.5 overflow-y-auto rounded-[12px] border border-[var(--ep-input-border)] bg-[var(--ep-input)] p-1.5 text-left"
                                >
                                    <label
                                        v-for="p in products"
                                        :key="p.id"
                                        class="flex cursor-pointer items-center justify-start gap-2.5 rounded-[9px] px-2.5 py-1.5 transition-colors duration-150 hover:bg-[var(--ep-hover)]"
                                    >
                                        <span class="shrink-0 w-fit">
                                            <Checkbox
                                                :model-value="isProductSelected(p.id)"
                                                @update:model-value="toggleProduct(p.id)"
                                            />
                                        </span>
                                        <span class="text-left text-[13px] text-[var(--ep-text)]">{{ p.name }}</span>
                                    </label>
                                </div>
                                <p v-else class="rounded-[12px] border border-dashed border-[var(--ep-line-strong)] bg-[var(--ep-card-2)] px-3 py-2.5 text-[12px] text-[var(--ep-text-4)]">
                                    Nenhum produto cadastrado.
                                </p>
                            </div>
                        </div>

                        <p
                            v-if="errorMessage"
                            class="mt-4 rounded-[12px] border border-[color-mix(in_oklab,var(--ep-neg)_35%,transparent)] bg-[var(--ep-neg-bg)] px-3 py-2.5 text-[12.5px] text-[var(--ep-neg)]"
                        >
                            {{ errorMessage }}
                        </p>

                        <div class="mt-6 flex gap-2.5 border-t border-[var(--ep-line)] pt-5">
                            <Button
                                class="flex-1"
                                :disabled="saving"
                                @click="save"
                            >
                                <Loader2
                                    v-if="saving"
                                    class="h-4 w-4 animate-spin"
                                />
                                Salvar
                            </Button>
                            <Button variant="outline" @click="cancelEdit">
                                Cancelar
                            </Button>
                        </div>
                    </template>
                </div>
            </aside>
        </div>
    </Teleport>

    <!-- Modal confirmar exclusão -->
    <Teleport to="body">
        <div
            v-if="confirmingDeleteId"
            class="fixed inset-0 z-[100001] flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
        >
            <div
                class="ep-scrim fixed inset-0"
                @click="cancelDelete"
            />
            <div
                class="ep-modal relative w-full max-w-sm p-6"
            >
                <p class="text-[15px] font-semibold tracking-[-0.015em] text-[var(--ep-text)]">Excluir integração?</p>
                <p class="mt-1.5 text-[13px] leading-[1.55] text-[var(--ep-text-3)]">
                    Deseja realmente excluir esta integração Spedy? A emissão automática de notas será desativada para os produtos vinculados.
                </p>
                <div class="mt-6 flex justify-end gap-2.5">
                    <Button variant="outline" @click="cancelDelete">
                        Cancelar
                    </Button>
                    <Button
                        variant="danger"
                        class="ep-btn-danger !h-9"
                        :disabled="deleting !== null"
                        @click="confirmRemove(spedy_integrations.find(i => i.id === confirmingDeleteId))"
                    >
                        <Loader2
                            v-if="deleting === confirmingDeleteId"
                            class="h-4 w-4 animate-spin"
                        />
                        Excluir
                    </Button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
