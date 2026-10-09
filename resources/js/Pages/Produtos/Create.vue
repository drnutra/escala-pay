<script setup>
import { useForm } from '@inertiajs/vue3';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import Button from '@/components/ui/Button.vue';

defineOptions({ layout: LayoutInfoprodutor });

const form = useForm({
    name: '',
    slug: '',
    description: '',
    type: 'area_membros',
    price: 0,
    is_active: true,
});
</script>

<template>
    <div class="space-y-4">
            <form class="panel-card max-w-xl space-y-5 p-6" @submit.prevent="form.post('/produtos')">
                <div>
                    <h2 class="text-[16px] font-semibold tracking-[-0.02em] text-[var(--ep-text)]">Novo produto</h2>
                    <p class="mt-1 text-[12.5px] text-[var(--ep-text-3)]">Preencha os dados básicos; o restante é configurado na edição do produto.</p>
                </div>
                <div class="ep-divider" aria-hidden="true" />
                <div>
                    <label class="ep-label">Nome</label>
                    <input v-model="form.name" type="text" required class="ep-input" />
                    <p v-if="form.errors.name" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">{{ form.errors.name }}</p>
                </div>
                <div>
                    <label class="ep-label">Slug (opcional)</label>
                    <input v-model="form.slug" type="text" class="ep-input" />
                </div>
                <div>
                    <label class="ep-label">Descrição</label>
                    <textarea v-model="form.description" rows="3" class="ep-input"></textarea>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="ep-label">Tipo</label>
                        <select v-model="form.type" class="ep-input">
                            <option value="area_membros">Área de membros</option>
                            <option value="area_membros_externa">Área de membros externa</option>
                            <option value="link">Link</option>
                            <option value="link_pagamento">Somente link de pagamento</option>
                        </select>
                    </div>
                    <div>
                        <label class="ep-label">Preço (R$)</label>
                        <input v-model="form.price" type="number" step="0.01" min="0" required class="ep-input tabular-nums" />
                        <p v-if="form.errors.price" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">{{ form.errors.price }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2.5">
                    <input v-model="form.is_active" type="checkbox" id="is_active" class="h-4 w-4 rounded-[5px] accent-[var(--ep-accent)]" />
                    <label for="is_active" class="text-[13px] text-[var(--ep-text-2)]">Ativo</label>
                </div>
                <div class="flex gap-2 border-t border-[var(--ep-line)] pt-5">
                    <Button type="submit" :disabled="form.processing">Criar</Button>
                    <Link href="/produtos" class="ep-btn-secondary">Cancelar</Link>
                </div>
            </form>
        </div>
</template>
