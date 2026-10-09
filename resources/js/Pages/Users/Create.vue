<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import Button from '@/components/ui/Button.vue';
import { UserPlus } from 'lucide-vue-next';

defineOptions({ layout: LayoutInfoprodutor });

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});
</script>

<template>
    <div class="mx-auto max-w-2xl space-y-5">
        <header>
            <h1 class="ep-page-heading">Novo infoprodutor</h1>
            <p class="mt-1 text-[13px] text-[var(--ep-text-3)]">
                Cadastre um novo usuário com acesso ao painel como infoprodutor.
            </p>
        </header>

        <form class="panel-card p-6" @submit.prevent="form.post('/usuarios')">
            <div class="flex items-center gap-3">
                <span class="ep-kpi__icon shrink-0" aria-hidden="true">
                    <UserPlus class="h-4 w-4" :stroke-width="1.75" />
                </span>
                <div class="min-w-0">
                    <h2 class="ep-section-title">Dados de acesso</h2>
                    <p class="text-[12px] text-[var(--ep-text-4)]">O login é feito com o e-mail e a senha definidos aqui.</p>
                </div>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="name" class="ep-label">Nome</label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        autocomplete="name"
                        class="ep-input"
                    />
                    <p v-if="form.errors.name" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">{{ form.errors.name }}</p>
                </div>
                <div class="sm:col-span-2">
                    <label for="email" class="ep-label">E-mail</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autocomplete="email"
                        class="ep-input"
                    />
                    <p v-if="form.errors.email" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">{{ form.errors.email }}</p>
                </div>
                <div>
                    <label for="password" class="ep-label">Senha</label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        required
                        minlength="8"
                        autocomplete="new-password"
                        class="ep-input"
                    />
                    <p v-if="form.errors.password" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">{{ form.errors.password }}</p>
                    <p class="ep-help">Mínimo de 8 caracteres.</p>
                </div>
                <div>
                    <label for="password_confirmation" class="ep-label">Confirmar senha</label>
                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        required
                        minlength="8"
                        autocomplete="new-password"
                        class="ep-input"
                    />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2 border-t border-[var(--ep-line)] pt-5">
                <Link href="/usuarios" class="ep-btn-secondary">
                    Cancelar
                </Link>
                <Button type="submit" :disabled="form.processing">
                    Cadastrar
                </Button>
            </div>
        </form>
    </div>
</template>
