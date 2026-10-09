<script setup>
import { ref, watch } from 'vue';
import { X, Pencil, Trash2, Package, Loader2 } from 'lucide-vue-next';
import axios from 'axios';
import Button from '@/components/ui/Button.vue';
import Checkbox from '@/components/ui/Checkbox.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    aluno: { type: Object, default: null },
    produtos: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'updated', 'deleted']);

const editing = ref(false);
const saving = ref(false);
const form = ref({
    name: '',
    email: '',
    password: '',
    product_ids: [],
});
const removingProductId = ref(null);
const deleting = ref(false);
const toast = ref({ message: null, type: null });

watch(
    () => props.aluno,
    (a) => {
        if (a) {
            form.value = {
                name: a.name ?? '',
                email: a.email ?? '',
                password: '',
                product_ids: (a.products ?? []).map((p) => p.id),
            };
        }
        editing.value = false;
    },
    { immediate: true }
);

function close() {
    emit('close');
}

function startEdit() {
    editing.value = true;
}

function cancelEdit() {
    editing.value = false;
    if (props.aluno) {
        form.value = {
            name: props.aluno.name ?? '',
            email: props.aluno.email ?? '',
            password: '',
            product_ids: (props.aluno.products ?? []).map((p) => p.id),
        };
    }
}

async function save() {
    if (!props.aluno) return;
    saving.value = true;
    try {
        const { data } = await axios.put(`/produtos/alunos/${props.aluno.id}`, {
            name: form.value.name,
            email: form.value.email,
            password: form.value.password || undefined,
            product_ids: form.value.product_ids,
        });
        showToast(data.message ?? 'Aluno atualizado.', 'success');
        editing.value = false;
        emit('updated', data.aluno);
    } catch (err) {
        showToast(
            err.response?.data?.message ?? 'Erro ao atualizar. Tente novamente.',
            'error'
        );
    } finally {
        saving.value = false;
    }
}

async function removeProduct(produtoId) {
    if (!props.aluno) return;
    removingProductId.value = produtoId;
    try {
        const { data } = await axios.delete(
            `/produtos/alunos/${props.aluno.id}/produtos/${produtoId}`
        );
        showToast(data.message ?? 'Acesso removido.', 'success');
        emit('updated', {
            ...props.aluno,
            products_count: data.products_count ?? 0,
            products: (props.aluno.products ?? []).filter((p) => p.id !== produtoId),
        });
    } catch (err) {
        showToast(
            err.response?.data?.message ?? 'Erro ao remover acesso.',
            'error'
        );
    } finally {
        removingProductId.value = null;
    }
}

async function deleteAluno() {
    if (!props.aluno) return;
    if (!window.confirm('Tem certeza que deseja excluir este aluno? Esta ação não pode ser desfeita.')) {
        return;
    }
    deleting.value = true;
    try {
        await axios.delete(`/produtos/alunos/${props.aluno.id}`);
        showToast('Aluno excluído com sucesso.', 'success');
        close();
        emit('deleted', props.aluno.id);
    } catch (err) {
        showToast(
            err.response?.data?.message ?? 'Erro ao excluir.',
            'error'
        );
    } finally {
        deleting.value = false;
    }
}

function showToast(message, type) {
    toast.value = { message, type };
    setTimeout(() => {
        toast.value = { message: null, type: null };
    }, 4000);
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
                class="ep-drawer relative flex h-full w-full max-w-md flex-col sm:rounded-l-[22px]"
            >
                <div class="flex items-center justify-between gap-3 border-b border-[var(--ep-line)] px-5 py-4">
                    <h2 class="text-[15px] font-semibold tracking-[-0.015em] text-[var(--ep-text)]">
                        {{ editing ? 'Editar aluno' : 'Detalhes do aluno' }}
                    </h2>
                    <button
                        type="button"
                        class="ep-btn-ghost ep-btn-icon !h-8 !w-8"
                        aria-label="Fechar"
                        @click="close"
                    >
                        <X class="h-4 w-4" :stroke-width="1.75" />
                    </button>
                </div>

                <div v-if="!aluno" class="ep-empty flex-1">
                    <p class="ep-empty__title">Nenhum aluno selecionado.</p>
                    <p class="ep-empty__text">Escolha um aluno na lista para ver os detalhes.</p>
                </div>

                <div v-else class="flex flex-1 flex-col overflow-hidden">
                    <div class="flex-1 overflow-y-auto p-5">
                        <div v-if="!editing" class="space-y-5">
                            <div class="panel-card ep-glow-card p-5">
                                <div class="flex items-center gap-3.5">
                                    <span v-avatar="aluno.name" class="ep-avatar !h-11 !w-11 shrink-0 !text-[15px]" aria-hidden="true">{{ (aluno.name || '?').trim().charAt(0).toUpperCase() }}</span>
                                    <dl class="min-w-0 flex-1 space-y-2.5">
                                        <div>
                                            <dt class="text-[11.5px] text-[var(--ep-text-3)]">
                                                Nome
                                            </dt>
                                            <dd class="break-words text-[14px] font-medium text-[var(--ep-text)]">{{ aluno.name }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-[11.5px] text-[var(--ep-text-3)]">
                                                E-mail
                                            </dt>
                                            <dd class="break-all text-[13px] text-[var(--ep-text-2)]">{{ aluno.email }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <p class="ep-section-title flex items-center justify-between">
                                    Produtos com acesso
                                    <span class="ep-chip tabular-nums">{{ (aluno.products ?? []).length }}</span>
                                </p>
                                <div
                                    v-for="p in (aluno.products ?? [])"
                                    :key="p.id"
                                    class="flex items-center justify-between gap-3 rounded-xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] py-2 pl-3 pr-2 shadow-[var(--ep-glass-highlight)]"
                                >
                                    <span class="flex min-w-0 items-center gap-2.5 text-[13px] text-[var(--ep-text)]">
                                        <Package class="h-4 w-4 shrink-0 text-[var(--ep-accent)]" :stroke-width="1.75" />
                                        <span class="truncate">{{ p.name }}</span>
                                    </span>
                                    <button
                                        type="button"
                                        class="shrink-0 rounded-lg px-2 py-1 text-[12px] font-medium text-[var(--ep-neg)] transition-colors duration-150 hover:bg-[var(--ep-neg-bg)] disabled:opacity-50"
                                        :disabled="removingProductId === p.id"
                                        @click="removeProduct(p.id)"
                                    >
                                        {{ removingProductId === p.id ? 'Removendo...' : 'Remover' }}
                                    </button>
                                </div>
                                <p v-if="!aluno.products?.length" class="rounded-xl border border-dashed border-[var(--ep-line-strong)] px-3 py-4 text-center text-[12.5px] text-[var(--ep-text-4)]">
                                    Nenhum produto
                                </p>
                            </div>
                            <div class="flex flex-col gap-2 border-t border-[var(--ep-line)] pt-5">
                                <Button variant="outline" class="w-full justify-start" @click="startEdit">
                                    <Pencil class="h-4 w-4" :stroke-width="1.75" />
                                    Editar
                                </Button>
                                <Button
                                    variant="destructive"
                                    class="w-full justify-start"
                                    :disabled="deleting"
                                    @click="deleteAluno"
                                >
                                    <Loader2 v-if="deleting" class="h-4 w-4 animate-spin" :stroke-width="1.75" />
                                    <Trash2 v-else class="h-4 w-4" :stroke-width="1.75" />
                                    Excluir aluno
                                </Button>
                            </div>
                        </div>

                        <div v-else class="space-y-4">
                            <div>
                                <label class="ep-label">
                                    Nome
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    class="ep-input"
                                    placeholder="Nome do aluno"
                                />
                            </div>
                            <div>
                                <label class="ep-label">
                                    E-mail
                                </label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    class="ep-input"
                                    placeholder="email@exemplo.com"
                                />
                            </div>
                            <div>
                                <label class="ep-label">
                                    Nova senha <span class="font-normal text-[var(--ep-text-4)]">(deixe em branco para manter)</span>
                                </label>
                                <input
                                    v-model="form.password"
                                    type="password"
                                    class="ep-input"
                                    placeholder="••••••••"
                                />
                            </div>
                            <div>
                                <p class="ep-label">
                                    Produtos com acesso
                                </p>
                                <div class="space-y-0.5 rounded-xl border border-[var(--ep-input-border)] bg-[var(--ep-input)] p-1.5">
                                    <label
                                        v-for="p in produtos"
                                        :key="p.id"
                                        class="flex cursor-pointer items-center gap-2.5 rounded-[10px] px-2 py-1.5 text-left transition-colors duration-150 hover:bg-[var(--ep-hover)]"
                                    >
                                        <span class="shrink-0 w-fit">
                                            <Checkbox
                                                :model-value="form.product_ids.includes(p.id)"
                                                @update:model-value="(v) => { if (v) form.product_ids = [...form.product_ids, p.id]; else form.product_ids = form.product_ids.filter(x => x !== p.id); }"
                                            />
                                        </span>
                                        <span class="flex-1 truncate text-left text-[13px] text-[var(--ep-text)]">{{ p.name }}</span>
                                    </label>
                                    <p v-if="!produtos.length" class="px-2 py-1.5 text-[12.5px] text-[var(--ep-text-4)]">
                                        Nenhum produto disponível
                                    </p>
                                </div>
                            </div>
                            <div class="flex gap-2 border-t border-[var(--ep-line)] pt-5">
                                <Button
                                    variant="primary"
                                    class="flex-1"
                                    :disabled="saving"
                                    @click="save"
                                >
                                    <Loader2 v-if="saving" class="h-4 w-4 animate-spin" :stroke-width="1.75" />
                                    Salvar
                                </Button>
                                <Button variant="outline" :disabled="saving" @click="cancelEdit">
                                    Cancelar
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Toast -->
                    <Transition
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="translate-y-2 opacity-0"
                        enter-to-class="translate-y-0 opacity-100"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="translate-y-0 opacity-100"
                        leave-to-class="translate-y-2 opacity-0"
                    >
                        <div
                            v-if="toast.message"
                            role="alert"
                            :class="[
                                'mx-5 mb-5 flex items-start gap-2.5 rounded-xl border px-4 py-3 text-[13px] font-medium',
                                toast.type === 'error'
                                    ? 'border-[color-mix(in_oklab,var(--ep-neg)_35%,transparent)] bg-[var(--ep-neg-bg)] text-[var(--ep-neg)]'
                                    : 'border-[color-mix(in_oklab,var(--ep-pos)_35%,transparent)] bg-[var(--ep-pos-bg)] text-[var(--ep-pos)]',
                            ]"
                        >
                            {{ toast.message }}
                        </div>
                    </Transition>
                </div>
            </aside>
        </div>
    </Teleport>
</template>
