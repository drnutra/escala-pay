<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import Button from '@/components/ui/Button.vue';
import { Plus, Pencil, Trash2, KeyRound, ExternalLink, HelpCircle } from 'lucide-vue-next';
import { Lock, Webhook, CalendarDays, CheckCircle2, Activity } from 'lucide-vue-next';

defineOptions({ layout: LayoutInfoprodutor });

const props = defineProps({
    applications: { type: Array, default: () => [] },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success ?? null);
const deletingId = ref(null);
const helpModalOpen = ref(false);

function formatDate(value) {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
}

function confirmDelete(app) {
    if (!window.confirm(`Excluir a aplicação "${app.name}"? A API key deixará de funcionar.`)) return;
    deletingId.value = app.id;
    router.delete(`/aplicacoes-api/${app.id}`, {
        preserveScroll: true,
        onFinish: () => { deletingId.value = null; },
    });
}
</script>

<template>
    <div class="space-y-5">
        <!-- Cabeçalho do console -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <div class="flex items-center gap-1.5">
                    <span class="ep-chip ep-chip--accent">Desenvolvedores</span>
                    <button
                        type="button"
                        class="ep-btn-ghost ep-btn-icon !h-7 !w-7 text-[var(--ep-text-4)]"
                        title="O que é a API de Pagamentos?"
                        aria-label="Ajuda"
                        @click="helpModalOpen = true"
                    >
                        <HelpCircle class="h-4 w-4" :stroke-width="1.75" />
                    </button>
                </div>
                <p class="mt-1 max-w-xl text-[13px] leading-relaxed text-[var(--ep-text-3)]">
                    Crie aplicações para integrar plataformas externas e processar pagamentos com seus gateways.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="/docs/api-pagamentos" target="_blank" rel="noopener noreferrer" class="ep-btn-secondary">
                    <ExternalLink class="h-4 w-4" :stroke-width="1.75" />
                    Abrir documentação
                </a>
                <a href="/aplicacoes-api/create" class="ep-btn">
                    <Plus class="h-4 w-4" :stroke-width="1.75" />
                    Nova aplicação
                </a>
            </div>
        </div>

        <!-- Modal: O que é a API de Pagamentos -->
        <div
            v-show="helpModalOpen"
            class="fixed inset-0 z-[100000] flex items-center justify-center p-4"
            aria-modal="true"
            role="dialog"
        >
            <div class="ep-scrim fixed inset-0" aria-hidden="true" @click="helpModalOpen = false" />
            <div class="ep-modal relative w-full max-w-md p-6">
                <div class="flex items-center gap-3">
                    <span class="ep-kpi__icon shrink-0">
                        <HelpCircle class="h-4 w-4" :stroke-width="1.75" />
                    </span>
                    <h2 class="text-[16px] font-semibold tracking-[-0.015em] text-[var(--ep-text)]">Para que serve a API de Pagamentos?</h2>
                </div>
                <p class="mt-4 text-[13px] leading-relaxed text-[var(--ep-text-3)]">
                    Ela serve para <strong class="font-medium text-[var(--ep-text)]">conectar com plataformas externas</strong> ou com seus próprios sistemas e SaaS. Assim você processa pagamentos usando os gateways já configurados no Getfy, sem precisar integrar cada plataforma diretamente com cada método de pagamento.
                </p>
                <div class="ep-divider my-4" />
                <ul class="space-y-3 text-[13px] leading-relaxed text-[var(--ep-text-3)]">
                    <li class="flex items-start gap-2.5">
                        <span class="mt-[7px] h-1.5 w-1.5 shrink-0 rounded-full bg-[var(--ep-accent)]" />
                        <span><strong class="font-medium text-[var(--ep-text)]">Gateway centralizado:</strong> um único roteador de pagamentos; você integra uma vez e usa em várias aplicações.</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <span class="mt-[7px] h-1.5 w-1.5 shrink-0 rounded-full bg-[var(--ep-accent)]" />
                        <span><strong class="font-medium text-[var(--ep-text)]">Integração única:</strong> não precisa integrar com vários métodos (PIX, cartão, boleto) em cada sistema - tudo passa pela API.</span>
                    </li>
                </ul>
                <div class="mt-6 flex justify-end">
                    <button type="button" class="ep-btn-secondary" @click="helpModalOpen = false">Entendi</button>
                </div>
            </div>
        </div>

        <div
            v-if="flashSuccess"
            role="status"
            class="flex items-center gap-2.5 rounded-2xl border border-[color-mix(in_oklab,var(--ep-pos)_35%,transparent)] bg-[var(--ep-pos-bg)] px-4 py-3 text-[13px] text-[var(--ep-pos)]"
        >
            <CheckCircle2 class="h-4 w-4 shrink-0" :stroke-width="1.75" />
            <span>{{ flashSuccess }}</span>
        </div>

        <!-- Visão geral -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="panel-card ep-glow-card ep-kpi">
                <div class="flex items-center justify-between gap-3">
                    <span class="ep-kpi__label">Aplicações</span>
                    <span class="ep-kpi__icon"><KeyRound class="h-4 w-4" :stroke-width="1.75" /></span>
                </div>
                <span class="ep-kpi__value">{{ applications.length }}</span>
                <span class="ep-kpi__meta">Cada uma com a sua API key</span>
            </div>
            <div class="panel-card ep-kpi">
                <div class="flex items-center justify-between gap-3">
                    <span class="ep-kpi__label">Ativas</span>
                    <span class="ep-kpi__icon"><Activity class="h-4 w-4" :stroke-width="1.75" /></span>
                </div>
                <span class="ep-kpi__value">{{ applications.filter((a) => a.is_active).length }}</span>
                <span class="ep-kpi__meta">de {{ applications.length }} no total</span>
            </div>
            <div class="panel-card ep-kpi">
                <div class="flex items-center justify-between gap-3">
                    <span class="ep-kpi__label">Com webhook</span>
                    <span class="ep-kpi__icon"><Webhook class="h-4 w-4" :stroke-width="1.75" /></span>
                </div>
                <span class="ep-kpi__value">{{ applications.filter((a) => a.webhook_url).length }}</span>
                <span class="ep-kpi__meta">Recebem notificações de pagamento</span>
            </div>
        </div>

        <!-- Aplicações -->
        <section class="space-y-3">
            <div class="flex items-center justify-between px-1">
                <h2 class="ep-section-title">Aplicações</h2>
                <span class="text-[12px] tabular-nums text-[var(--ep-text-4)]">{{ applications.length }} {{ applications.length === 1 ? 'aplicação' : 'aplicações' }}</span>
            </div>

            <ul v-if="applications.length" class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <li
                    v-for="app in applications"
                    :key="app.id"
                    class="panel-card flex min-w-0 flex-col p-5"
                >
                    <div class="flex items-start gap-3">
                        <span class="ep-kpi__icon shrink-0">
                            <KeyRound class="h-4 w-4" :stroke-width="1.75" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="min-w-0 truncate text-[14.5px] font-semibold tracking-[-0.01em] text-[var(--ep-text)]">{{ app.name }}</p>
                                <span class="ep-chip ep-chip--pos" :class="app.is_active ? '' : 'hidden'">
                                    <span class="h-1.5 w-1.5 rounded-full bg-current" />
                                    Ativa
                                </span>
                                <span
                                    v-if="!app.is_active"
                                    class="ep-chip ep-chip--warn"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-current" />
                                    Inativa
                                </span>
                            </div>
                            <p class="mt-1 flex items-center gap-1.5 text-[12px] text-[var(--ep-text-4)]">
                                <CalendarDays class="h-3.5 w-3.5 shrink-0" :stroke-width="1.75" />
                                Criada em <span class="tabular-nums">{{ formatDate(app.created_at) }}</span>
                            </p>
                        </div>
                    </div>

                    <dl class="mt-4 space-y-3">
                        <div>
                            <dt class="mb-1.5 text-[12px] font-medium text-[var(--ep-text-3)]">Slug</dt>
                            <dd class="flex h-9 min-w-0 items-center rounded-[10px] border border-[var(--ep-input-border)] bg-[var(--ep-input)] px-3 font-mono text-[12.5px] text-[var(--ep-text-2)]">
                                <span class="truncate">{{ app.slug }}</span>
                            </dd>
                        </div>
                        <div>
                            <dt class="mb-1.5 flex items-center justify-between gap-2 text-[12px] font-medium text-[var(--ep-text-3)]">
                                API key
                                <span class="font-normal text-[var(--ep-text-4)]">Exibida só ao ser gerada</span>
                            </dt>
                            <dd class="flex h-9 min-w-0 items-center gap-2 rounded-[10px] border border-[var(--ep-input-border)] bg-[var(--ep-input)] px-3 font-mono text-[12.5px] text-[var(--ep-text-4)]">
                                <Lock class="h-3.5 w-3.5 shrink-0" :stroke-width="1.75" aria-hidden="true" />
                                <span class="truncate tracking-[0.2em]" aria-label="API key oculta">••••••••••••••••••••••••</span>
                            </dd>
                        </div>
                    </dl>

                    <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-[var(--ep-line)] pt-4">
                        <span v-if="app.webhook_url" class="ep-chip ep-chip--accent">
                            <Webhook class="h-3 w-3" :stroke-width="1.75" />
                            Webhook configurado
                        </span>
                        <div class="ml-auto flex items-center gap-1.5">
                            <a
                                :href="`/aplicacoes-api/${app.id}/edit`"
                                class="ep-btn-secondary !h-8 !px-3 !text-[12.5px]"
                            >
                                <Pencil class="h-3.5 w-3.5" :stroke-width="1.75" />
                                Editar
                            </a>
                            <button
                                type="button"
                                class="ep-btn-ghost ep-btn-icon !h-8 !w-8 text-[var(--ep-neg)] hover:bg-[var(--ep-neg-bg)] hover:text-[var(--ep-neg)]"
                                title="Excluir"
                                aria-label="Excluir"
                                :disabled="deletingId === app.id"
                                @click="confirmDelete(app)"
                            >
                                <Trash2 class="h-4 w-4" :stroke-width="1.75" />
                                <span class="sr-only">Excluir</span>
                            </button>
                        </div>
                    </div>
                </li>
            </ul>
            <div v-else class="panel-card">
                <div class="ep-empty py-14">
                    <span class="ep-kpi__icon mb-2">
                        <KeyRound class="h-4 w-4" :stroke-width="1.75" />
                    </span>
                    <p class="ep-empty__title">Nenhuma aplicação</p>
                    <p class="ep-empty__text">Crie uma aplicação para obter uma API key e integrar com plataformas externas.</p>
                    <a href="/aplicacoes-api/create" class="ep-btn mt-3">
                        <Plus class="h-4 w-4" :stroke-width="1.75" />
                        Nova aplicação
                    </a>
                </div>
            </div>
        </section>
    </div>
</template>
