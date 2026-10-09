<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const dropdownOpen = ref(false);

const panelNavPrefetch = ['hover', 'click'];
const dropdownRef = ref(null);

const user = computed(() => page.props.auth?.user ?? null);

const initials = computed(() => {
    if (!user.value?.name) return '?';
    const parts = user.value.name.trim().split(/\s+/);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    return (parts[0][0] || '?').toUpperCase();
});

function toggleDropdown() {
    dropdownOpen.value = !dropdownOpen.value;
}

function closeDropdown() {
    dropdownOpen.value = false;
}

function handleClickOutside(event) {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        closeDropdown();
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div v-if="user" ref="dropdownRef" class="relative">
        <button
            type="button"
            class="flex h-8 items-center gap-2 rounded-lg py-1 pl-1 pr-1.5 text-left text-[13px] transition-colors duration-150 hover:bg-[var(--ep-hover)]"
            @click.prevent="toggleDropdown"
        >
            <span
                class="flex h-6 w-6 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[var(--color-primary)] text-[10.5px] font-semibold text-white ring-1 ring-[var(--ep-line)]"
            >
                <img
                    v-if="user.avatar_url"
                    :src="user.avatar_url"
                    :alt="user.name"
                    class="h-full w-full object-cover"
                />
                <span v-else>{{ initials }}</span>
            </span>
            <span class="hidden max-w-[120px] truncate font-medium text-[var(--ep-text-2)] sm:block">
                {{ user.name }}
            </span>
            <svg
                class="h-3.5 w-3.5 shrink-0 text-[var(--ep-text-4)] transition-transform duration-150"
                :class="{ 'rotate-180': dropdownOpen }"
                viewBox="0 0 20 20"
                fill="currentColor"
                aria-hidden="true"
            >
                <path
                    fill-rule="evenodd"
                    d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                    clip-rule="evenodd"
                />
            </svg>
        </button>

        <div
            v-if="dropdownOpen"
            class="absolute right-0 z-50 mt-2 flex w-60 origin-top-right flex-col rounded-xl border border-[var(--ep-line)] bg-[var(--ep-card)] p-1.5 shadow-[var(--ep-shadow-pop)]"
        >
            <div class="mb-1 border-b border-[var(--ep-line)] px-2.5 pb-2.5 pt-1.5">
                <p class="truncate text-[13px] font-medium text-[var(--ep-text)]">
                    {{ user.name }}
                </p>
                <p class="mt-0.5 truncate text-[12px] text-[var(--ep-text-3)]">
                    {{ user.email }}
                </p>
            </div>
            <Link
                v-if="user.role === 'infoprodutor' || user.role === 'admin'"
                href="/meu-perfil"
                :prefetch="panelNavPrefetch"
                class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left text-[13px] font-medium text-[var(--ep-text-2)] transition-colors duration-150 hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)]"
                @click="closeDropdown"
            >
                Meu perfil
            </Link>
            <Link
                href="/logout"
                method="post"
                as="button"
                class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left text-[13px] font-medium text-[var(--ep-text-2)] transition-colors duration-150 hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)]"
                @click="closeDropdown"
            >
                Sair
            </Link>
        </div>
    </div>
</template>
