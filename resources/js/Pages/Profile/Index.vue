<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import Button from '@/components/ui/Button.vue';
import { Camera, Lock, Loader2 } from 'lucide-vue-next';

defineOptions({ layout: LayoutInfoprodutor });

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});

const avatarInputRef = ref(null);
const avatarPreview = ref(null);

const profileForm = useForm({
    name: props.user.name,
    email: props.user.email,
    username: props.user.username ?? '',
    avatar: null,
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const avatarUrl = computed(() => {
    if (avatarPreview.value) return avatarPreview.value;
    return props.user.avatar_url || null;
});

function triggerAvatarClick() {
    avatarInputRef.value?.click();
}

function onAvatarChange(event) {
    const file = event.target.files?.[0];
    if (!file) return;
    profileForm.avatar = file;
    const reader = new FileReader();
    reader.onload = (e) => { avatarPreview.value = e.target?.result; };
    reader.readAsDataURL(file);
}

function submitProfile() {
    profileForm.post('/meu-perfil', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            profileForm.avatar = null;
            avatarPreview.value = null;
        },
    });
}

function submitPassword() {
    passwordForm.put('/meu-perfil/senha', {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
}
</script>

<template>
    <div class="mx-auto max-w-3xl space-y-5">
        <header>
            <h1 class="ep-page-heading">
                Meu perfil
            </h1>
            <p class="mt-1 text-[13px] text-[var(--ep-text-3)]">
                Atualize sua foto, nome, e-mail e senha.
            </p>
        </header>

        <!-- Card: Foto e dados (herói) -->
        <section class="panel-card ep-glow-card" aria-labelledby="perfil-dados">
            <div class="grid gap-8 p-6 sm:p-8 md:grid-cols-[200px_minmax(0,1fr)]">
                <div class="flex flex-col items-center text-center md:border-r md:border-[var(--ep-line)] md:pr-8">
                    <div class="relative shrink-0">
                        <button
                            type="button"
                            aria-label="Trocar foto de perfil"
                            v-avatar="user.name" class="ep-avatar group relative !flex !h-28 !w-28 overflow-hidden !text-[36px] !font-semibold !tracking-[-0.02em] shadow-[0_0_0_6px_color-mix(in_oklab,var(--ep-accent)_10%,transparent),0_18px_40px_-16px_var(--ep-glow)] transition-[box-shadow,transform] duration-150 hover:shadow-[0_0_0_6px_color-mix(in_oklab,var(--ep-accent)_22%,transparent),0_18px_40px_-12px_var(--ep-glow)] active:scale-[0.98]"
                            @click="triggerAvatarClick"
                        >
                            <img
                                v-if="avatarUrl"
                                :src="avatarUrl"
                                alt="Foto de perfil"
                                class="h-full w-full object-cover"
                            />
                            <span
                                v-else
                                class="flex h-full w-full items-center justify-center text-[var(--ep-text)]"
                            >
                                {{ (user.name || '?').charAt(0).toUpperCase() }}
                            </span>
                            <span
                                class="absolute inset-0 flex items-center justify-center bg-[rgba(2,5,20,0.55)] opacity-0 backdrop-blur-[2px] transition-opacity duration-150 group-hover:opacity-100"
                            >
                                <Camera class="h-7 w-7 text-white" :stroke-width="1.75" />
                            </span>
                        </button>
                        <span
                            class="pointer-events-none absolute -bottom-0.5 -right-0.5 flex h-8 w-8 items-center justify-center rounded-full border border-[var(--ep-glass-border)] bg-[var(--ep-drawer)] text-[var(--ep-text-2)] shadow-[var(--ep-shadow-pop)] backdrop-blur-xl"
                            aria-hidden="true"
                        >
                            <Camera class="h-3.5 w-3.5" :stroke-width="1.75" />
                        </span>
                        <input
                            ref="avatarInputRef"
                            type="file"
                            accept="image/*"
                            class="sr-only"
                            @change="onAvatarChange"
                        />
                    </div>
                    <p class="mt-5 max-w-full truncate text-[15px] font-semibold tracking-[-0.02em] text-[var(--ep-text)]">{{ user.name }}</p>
                    <p class="mt-0.5 max-w-full truncate text-[12.5px] text-[var(--ep-text-3)]">{{ user.email }}</p>
                    <p class="mt-4 text-[12px] text-[var(--ep-text-4)]">Clique na foto para trocar.</p>
                </div>
                <form
                    class="min-w-0 space-y-4"
                    @submit.prevent="submitProfile"
                >
                    <h2 id="perfil-dados" class="ep-section-title">Dados da conta</h2>
                    <div>
                        <label
                            for="profile-name"
                            class="ep-label"
                        >
                            Nome
                        </label>
                        <input
                            id="profile-name"
                            v-model="profileForm.name"
                            type="text"
                            required
                            maxlength="255"
                            class="ep-input"
                            placeholder="Seu nome"
                        />
                        <p
                            v-if="profileForm.errors.name"
                            class="mt-1.5 text-[12px] text-[var(--ep-neg)]"
                        >
                            {{ profileForm.errors.name }}
                        </p>
                    </div>
                    <div>
                        <label
                            for="profile-email"
                            class="ep-label"
                        >
                            E-mail
                        </label>
                        <input
                            id="profile-email"
                            v-model="profileForm.email"
                            type="email"
                            required
                            autocomplete="email"
                            maxlength="255"
                            class="ep-input"
                            placeholder="seu@email.com"
                        />
                        <p
                            v-if="profileForm.errors.email"
                            class="mt-1.5 text-[12px] text-[var(--ep-neg)]"
                        >
                            {{ profileForm.errors.email }}
                        </p>
                    </div>
                    <div>
                        <label
                            for="profile-username"
                            class="ep-label"
                        >
                            Nome de usuário
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-[13.5px] text-[var(--ep-text-4)]" aria-hidden="true">@</span>
                            <input
                                id="profile-username"
                                v-model="profileForm.username"
                                type="text"
                                maxlength="64"
                                class="ep-input !pl-7"
                                placeholder="meunome"
                            />
                        </div>
                        <p class="ep-help">Usado nas conquistas compartilhadas.</p>
                        <p
                            v-if="profileForm.errors.username"
                            class="mt-1.5 text-[12px] text-[var(--ep-neg)]"
                        >
                            {{ profileForm.errors.username }}
                        </p>
                    </div>
                    <div class="flex justify-end border-t border-[var(--ep-line)] pt-5">
                        <Button
                            type="submit"
                            class="w-full sm:w-auto"
                            :disabled="profileForm.processing"
                        >
                            <Loader2
                                v-if="profileForm.processing"
                                class="h-4 w-4 animate-spin"
                                :stroke-width="1.75"
                            />
                            Salvar alterações
                        </Button>
                    </div>
                </form>
            </div>
        </section>

        <!-- Card: Alterar senha -->
        <section class="panel-card overflow-hidden" aria-labelledby="perfil-senha">
            <div class="flex items-center gap-3 border-b border-[var(--ep-line)] px-6 py-5 sm:px-8">
                <span class="ep-kpi__icon" aria-hidden="true">
                    <Lock class="h-4 w-4" :stroke-width="1.75" />
                </span>
                <div class="min-w-0">
                    <h2 id="perfil-senha" class="text-[15px] font-semibold tracking-[-0.02em] text-[var(--ep-text)]">
                        Alterar senha
                    </h2>
                    <p class="mt-0.5 text-[12.5px] text-[var(--ep-text-3)]">Mínimo de 8 caracteres.</p>
                </div>
            </div>
            <form
                class="space-y-4 p-6 sm:p-8"
                @submit.prevent="submitPassword"
            >
                <div>
                    <label
                        for="current-password"
                        class="ep-label"
                    >
                        Senha atual
                    </label>
                    <input
                        id="current-password"
                        v-model="passwordForm.current_password"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="ep-input"
                        placeholder="Digite sua senha atual"
                    />
                    <p
                        v-if="passwordForm.errors.current_password"
                        class="mt-1.5 text-[12px] text-[var(--ep-neg)]"
                    >
                        {{ passwordForm.errors.current_password }}
                    </p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label
                            for="new-password"
                            class="ep-label"
                        >
                            Nova senha
                        </label>
                        <input
                            id="new-password"
                            v-model="passwordForm.password"
                            type="password"
                            required
                            autocomplete="new-password"
                            class="ep-input"
                            placeholder="Mínimo 8 caracteres"
                        />
                        <p
                            v-if="passwordForm.errors.password"
                            class="mt-1.5 text-[12px] text-[var(--ep-neg)]"
                        >
                            {{ passwordForm.errors.password }}
                        </p>
                    </div>
                    <div>
                        <label
                            for="confirm-password"
                            class="ep-label"
                        >
                            Confirmar nova senha
                        </label>
                        <input
                            id="confirm-password"
                            v-model="passwordForm.password_confirmation"
                            type="password"
                            required
                            autocomplete="new-password"
                            class="ep-input"
                            placeholder="Repita a nova senha"
                        />
                    </div>
                </div>
                <div class="flex justify-end border-t border-[var(--ep-line)] pt-5">
                    <Button
                        type="submit"
                        variant="outline"
                        class="w-full sm:w-auto"
                        :disabled="passwordForm.processing"
                    >
                        <Loader2
                            v-if="passwordForm.processing"
                            class="h-4 w-4 animate-spin"
                            :stroke-width="1.75"
                        />
                        Alterar senha
                    </Button>
                </div>
            </form>
        </section>
    </div>
</template>
