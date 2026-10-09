<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import Button from '@/components/ui/Button.vue';
import { Mail, Settings2, Send, Plus, FileEdit, CheckCircle2, XCircle, Pause, Play, Ban, TriangleAlert, Copy } from 'lucide-vue-next';

defineOptions({ layout: LayoutInfoprodutor });

const props = defineProps({
    campaigns: { type: Array, default: () => [] },
    email_configured: { type: Boolean, default: false },
    cloud_mode: { type: Boolean, default: false },
    cron_instructions: { type: String, default: '' },
    app_url: { type: String, default: '' },
    cron_url: { type: String, default: null },
    base_path: { type: String, default: '' },
    schedule_ok: { type: Boolean, default: false },
    queue_ok: { type: Boolean, default: false },
});

const activeTab = ref('campanhas');

const statusLabel = (status) => {
    const map = {
        draft: 'Rascunho',
        sending: 'Enviando',
        paused: 'Pausada',
        sent: 'Concluída',
        cancelled: 'Cancelada',
    };
    return map[status] ?? status;
};

const statusClass = (status) => {
    const map = {
        draft: 'text-zinc-500',
        sending: 'text-blue-600 dark:text-blue-400',
        paused: 'text-amber-600 dark:text-amber-400',
        sent: 'text-emerald-600 dark:text-emerald-400',
        cancelled: 'text-red-600 dark:text-red-400',
    };
    return map[status] ?? 'text-zinc-500';
};

function confirmSend(campaign) {
    if (!props.email_configured) return;
    if (!confirm(`Disparar campanha "${campaign.name}"? Os e-mails serão enviados em lotes de até 30 por minuto (com pausa automática se o provedor limitar).`)) return;
    router.post(`/email-marketing/${campaign.id}/send`);
}

function confirmPause(campaign) {
    if (!confirm(`Pausar campanha "${campaign.name}"? Nenhum novo e-mail será enfileirado.`)) return;
    router.post(`/email-marketing/${campaign.id}/pause`);
}

function confirmResume(campaign, retryFailures = false) {
    const msg = retryFailures
        ? `Retomar campanha "${campaign.name}" e tentar reenviar os e-mails que falharam?`
        : `Retomar campanha "${campaign.name}"? O envio continuará para os destinatários pendentes.`;
    if (!confirm(msg)) return;
    router.post(`/email-marketing/${campaign.id}/resume`, { retry_failures: retryFailures });
}

function confirmCancel(campaign) {
    if (!confirm(`Cancelar campanha "${campaign.name}"? O envio será interrompido permanentemente.`)) return;
    router.post(`/email-marketing/${campaign.id}/cancel`);
}
</script>

<template>
    <div class="space-y-5">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <h1 class="ep-page-heading">E-mail Marketing</h1>
                <p class="mt-1 text-[13px] text-[var(--ep-text-3)]">Campanhas de e-mail para a sua base de compradores.</p>
            </div>
            <div class="flex gap-2">
                <Link v-if="activeTab === 'campanhas'" href="/email-marketing/create">
                    <Button variant="primary">
                        <Plus class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                        Nova campanha
                    </Button>
                </Link>
            </div>
        </header>

        <div
            v-if="!email_configured"
            class="panel-card flex items-start gap-3 !border-[color-mix(in_oklab,var(--ep-warn)_35%,transparent)] p-4"
            role="status"
        >
            <TriangleAlert class="mt-0.5 h-[18px] w-[18px] shrink-0 text-[var(--ep-warn)]" :stroke-width="1.75" aria-hidden="true" />
            <p class="text-[13px] text-[var(--ep-text-2)]">
                Configure o e-mail (SMTP, Hostinger, SendGrid etc.) em
                <Link href="/configuracoes" class="font-medium text-[var(--ep-accent)] underline-offset-4 hover:underline">Configurações &gt; E-mail</Link>
                para poder disparar campanhas.
            </p>
        </div>

        <div class="ep-tabs" aria-label="Seções de e-mail marketing">
            <button
                type="button"
                :class="['ep-tab', activeTab === 'campanhas' ? 'ep-tab--active' : '']"
                @click="activeTab = 'campanhas'"
            >
                <Mail class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                Campanhas
                <span class="ml-0.5 rounded-full bg-[var(--ep-active)] px-1.5 text-[11px] font-medium tabular-nums leading-[18px] text-[var(--ep-text-3)]">{{ campaigns.length }}</span>
            </button>
            <button
                type="button"
                :class="['ep-tab', activeTab === 'configuracao' ? 'ep-tab--active' : '']"
                @click="activeTab = 'configuracao'"
            >
                <Settings2 class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                Configuração
                <span
                    class="h-1.5 w-1.5 rounded-full"
                    :class="cloud_mode || (schedule_ok && queue_ok) ? 'bg-[var(--ep-pos)]' : 'bg-[var(--ep-warn)]'"
                    aria-hidden="true"
                />
            </button>
        </div>

        <div v-show="activeTab === 'campanhas'" class="space-y-4">
            <div
                v-if="cloud_mode"
                class="flex flex-wrap items-center gap-x-3 gap-y-1.5 text-[12.5px] text-[var(--ep-text-3)]"
            >
                <span class="ep-chip ep-chip--pos">
                    <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                    Modo cloud
                </span>
                Se você estiver usando o Getfy em modo cloud, não é necessário configurar o cron; o envio já vem
                configurado automaticamente.
            </div>

            <div v-if="campaigns.length === 0" class="panel-card ep-empty py-14">
                <Mail class="mb-2 h-6 w-6 text-[var(--ep-text-4)]" :stroke-width="1.75" aria-hidden="true" />
                <p class="ep-empty__title">Nenhuma campanha ainda.</p>
                <p class="ep-empty__text">Crie um rascunho, escolha os destinatários e dispare quando estiver pronto.</p>
                <Link href="/email-marketing/create" class="mt-4 inline-block">
                    <Button variant="primary">Criar primeira campanha</Button>
                </Link>
            </div>

            <div v-else class="space-y-4">
                <!-- Resumo das campanhas -->
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <div class="panel-card ep-glow-card ep-kpi">
                        <span class="ep-kpi__label">E-mails enviados</span>
                        <span class="ep-kpi__value !text-[28px]">{{ campaigns.reduce((a, c) => a + (c.sent_count ?? 0), 0).toLocaleString('pt-BR') }}</span>
                        <span class="ep-kpi__meta">
                            de {{ campaigns.filter((c) => c.status !== 'draft').reduce((a, c) => a + (c.total_recipients ?? 0), 0).toLocaleString('pt-BR') }} destinatários
                        </span>
                    </div>
                    <div class="panel-card ep-kpi">
                        <span class="ep-kpi__label">Campanhas</span>
                        <span class="ep-kpi__value">{{ campaigns.length.toLocaleString('pt-BR') }}</span>
                        <span class="ep-kpi__meta">{{ campaigns.filter((c) => c.status === 'draft').length }} em rascunho</span>
                    </div>
                    <div class="panel-card ep-kpi">
                        <span class="ep-kpi__label">Em andamento</span>
                        <span class="ep-kpi__value">{{ campaigns.filter((c) => c.status === 'sending' || c.status === 'paused').length }}</span>
                        <span class="ep-kpi__meta">enviando ou pausadas</span>
                    </div>
                    <div class="panel-card ep-kpi">
                        <span class="ep-kpi__label">Falhas de envio</span>
                        <span
                            class="ep-kpi__value"
                            :class="campaigns.some((c) => c.failed_count > 0) ? '!text-[var(--ep-neg)]' : ''"
                        >{{ campaigns.reduce((a, c) => a + (c.failed_count ?? 0), 0).toLocaleString('pt-BR') }}</span>
                        <span class="ep-kpi__meta">somadas em todas as campanhas</span>
                    </div>
                </div>

                <section class="panel-card ep-data overflow-hidden" aria-labelledby="em-campanhas">
                    <div class="flex items-center justify-between gap-3 px-5 pb-3 pt-5">
                        <h2 id="em-campanhas" class="ep-section-title">Campanhas</h2>
                        <span class="text-[12px] text-[var(--ep-text-4)]">Envio em lotes de até 30 por minuto</span>
                    </div>
                    <div class="overflow-x-auto border-t border-[var(--ep-line)]">
                        <table class="ep-table min-w-[860px]">
                            <thead>
                                <tr>
                                    <th>Campanha</th>
                                    <th class="w-[120px]">Status</th>
                                    <th class="w-[210px]">Enviados</th>
                                    <th class="w-[1%] text-right"><span class="sr-only">Ações</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="c in campaigns" :key="c.id">
                                    <td class="max-w-[360px]">
                                        <p class="truncate text-[13px] font-medium text-[var(--ep-text)]">{{ c.name }}</p>
                                        <p class="truncate text-[12px] text-[var(--ep-text-3)]">{{ c.subject }}</p>
                                        <p
                                            v-if="c.last_error && (c.status === 'paused' || c.status === 'sending')"
                                            class="mt-1.5 line-clamp-2 text-[11.5px] leading-4 text-[var(--ep-warn)]"
                                            :title="c.last_error"
                                        >
                                            {{ c.last_error }}
                                        </p>
                                    </td>
                                    <td>
                                        <span
                                            class="ep-chip"
                                            :class="{
                                                'ep-chip--accent': c.status === 'sending',
                                                'ep-chip--warn': c.status === 'paused',
                                                'ep-chip--pos': c.status === 'sent',
                                                'ep-chip--neg': c.status === 'cancelled',
                                            }"
                                        >
                                            <span
                                                class="h-1.5 w-1.5 rounded-full bg-current"
                                                :class="c.status === 'sending' ? 'animate-pulse' : ''"
                                                aria-hidden="true"
                                            />
                                            {{ statusLabel(c.status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <template v-if="['sending', 'paused', 'sent', 'cancelled'].includes(c.status)">
                                            <div class="flex items-baseline justify-between gap-3 tabular-nums">
                                                <span>
                                                    <span class="text-[13px] font-semibold text-[var(--ep-text)]">{{ (c.sent_count ?? 0).toLocaleString('pt-BR') }}</span>
                                                    <span class="text-[12px] text-[var(--ep-text-4)]"> / {{ (c.total_recipients ?? 0).toLocaleString('pt-BR') }}</span>
                                                </span>
                                                <span class="text-[11.5px] text-[var(--ep-text-3)]">
                                                    {{ Math.min(100, Math.round(((c.sent_count ?? 0) / Math.max(1, c.total_recipients ?? 0)) * 100)) }}%
                                                </span>
                                            </div>
                                            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-[var(--ep-active)]">
                                                <div
                                                    class="h-full rounded-full bg-gradient-to-r from-[var(--ep-accent)] to-[var(--ep-accent-2)]"
                                                    :style="{ width: `${Math.min(100, Math.max(2, ((c.sent_count ?? 0) / Math.max(1, c.total_recipients ?? 0)) * 100))}%` }"
                                                />
                                            </div>
                                            <template v-if="c.failed_count > 0">
                                                <p class="mt-1 text-[11.5px] tabular-nums text-[var(--ep-neg)]">{{ c.failed_count }} falha(s)</p>
                                            </template>
                                        </template>
                                        <span class="text-[12.5px] text-[var(--ep-text-4)]">{{ ['sending', 'paused', 'sent', 'cancelled'].includes(c.status) ? '' : '—' }}</span>
                                    </td>
                                    <td>
                                        <div class="flex flex-wrap items-center justify-end gap-1.5">
                                            <Link v-if="c.status === 'draft'" :href="`/email-marketing/${c.id}/edit`">
                                                <Button variant="outline" size="sm">
                                                    <FileEdit class="h-3.5 w-3.5" :stroke-width="1.75" aria-hidden="true" />
                                                    Editar
                                                </Button>
                                            </Link>
                                            <Button
                                                v-if="c.status === 'draft' && email_configured"
                                                variant="primary"
                                                size="sm"
                                                @click="confirmSend(c)"
                                            >
                                                <Send class="h-3.5 w-3.5" :stroke-width="1.75" aria-hidden="true" />
                                                Disparar
                                            </Button>
                                            <Button
                                                v-if="c.status === 'sending'"
                                                variant="outline"
                                                size="sm"
                                                @click="confirmPause(c)"
                                            >
                                                <Pause class="h-3.5 w-3.5" :stroke-width="1.75" aria-hidden="true" />
                                                Pausar
                                            </Button>
                                            <Button
                                                v-if="c.status === 'paused'"
                                                variant="primary"
                                                size="sm"
                                                @click="confirmResume(c, false)"
                                            >
                                                <Play class="h-3.5 w-3.5" :stroke-width="1.75" aria-hidden="true" />
                                                Retomar
                                            </Button>
                                            <Button
                                                v-if="c.status === 'paused' && c.failed_count > 0"
                                                variant="outline"
                                                size="sm"
                                                @click="confirmResume(c, true)"
                                            >
                                                <Play class="h-3.5 w-3.5" :stroke-width="1.75" aria-hidden="true" />
                                                Retomar e reenviar falhas
                                            </Button>
                                            <Button
                                                v-if="c.status === 'sending' || c.status === 'paused'"
                                                variant="destructive"
                                                size="sm"
                                                @click="confirmCancel(c)"
                                            >
                                                <Ban class="h-3.5 w-3.5" :stroke-width="1.75" aria-hidden="true" />
                                                Cancelar
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>

        <div v-show="activeTab === 'configuracao'" class="space-y-4">
            <div
                v-if="cloud_mode"
                class="panel-card flex items-start gap-3 p-5"
            >
                <span class="ep-chip ep-chip--pos mt-px shrink-0">
                    <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                    Modo cloud
                </span>
                <p class="text-[13px] text-[var(--ep-text-2)]">
                    Se você estiver usando o Getfy em modo cloud, não é necessário configurar o cron; o envio já vem
                    configurado automaticamente.
                </p>
            </div>

            <section class="panel-card p-5" aria-labelledby="em-status">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 id="em-status" class="ep-section-title">Status do envio</h2>
                        <p class="mt-0.5 text-[12.5px] text-[var(--ep-text-3)]">
                            A plataforma verifica automaticamente se o cron e a fila estão rodando (atualizado a cada minuto).
                        </p>
                    </div>
                </div>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div class="flex items-center gap-3 rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] px-4 py-3.5">
                        <CheckCircle2
                            v-if="schedule_ok"
                            class="h-[18px] w-[18px] shrink-0 text-[var(--ep-pos)]"
                            :stroke-width="1.75"
                        />
                        <XCircle
                            v-else
                            class="h-[18px] w-[18px] shrink-0 text-[var(--ep-warn)]"
                            :stroke-width="1.75"
                        />
                        <p class="min-w-0 flex-1 text-[13px] font-medium text-[var(--ep-text)]">Cron (agendador)</p>
                        <span class="ep-chip shrink-0" :class="schedule_ok ? 'ep-chip--pos' : 'ep-chip--warn'">
                            {{ schedule_ok ? 'Configurado e rodando' : 'Não detectado' }}
                        </span>
                    </div>
                    <div class="flex items-center gap-3 rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] px-4 py-3.5">
                        <CheckCircle2
                            v-if="queue_ok"
                            class="h-[18px] w-[18px] shrink-0 text-[var(--ep-pos)]"
                            :stroke-width="1.75"
                        />
                        <XCircle
                            v-else
                            class="h-[18px] w-[18px] shrink-0 text-[var(--ep-warn)]"
                            :stroke-width="1.75"
                        />
                        <p class="min-w-0 flex-1 text-[13px] font-medium text-[var(--ep-text)]">Fila (queue worker)</p>
                        <span class="ep-chip shrink-0" :class="queue_ok ? 'ep-chip--pos' : 'ep-chip--warn'">
                            {{ queue_ok ? 'Rodando normalmente' : 'Não detectada' }}
                        </span>
                    </div>
                </div>
            </section>

            <section class="panel-card p-5" aria-labelledby="em-setup">
                <h2 id="em-setup" class="ep-section-title">Como configurar</h2>
                <p class="mt-1 max-w-3xl text-[12.5px] leading-5 text-[var(--ep-text-3)]">
                    Para os e-mails serem enviados em lotes de 30 por minuto, configure o crontab para rodar o agendador a cada minuto e mantenha o worker de fila ativo. A linha abaixo deve ser adicionada ao crontab (<code class="rounded-md bg-[var(--ep-active)] px-1.5 py-px font-mono text-[11.5px] text-[var(--ep-text-2)]">crontab -e</code>), não executada no terminal.
                </p>

                <div class="mt-5 space-y-4">
                    <div>
                        <p class="ep-label flex items-center gap-2">
                            <span class="ep-chip !h-5 !px-1.5 tabular-nums">1</span>
                            Cron (uma vez por minuto):
                        </p>
                        <pre class="overflow-x-auto rounded-xl border border-[var(--ep-line)] bg-[var(--ep-input)] px-4 py-3 text-left font-mono text-[12.5px] text-[var(--ep-text-2)]">* * * * * cd {{ base_path || '/caminho/do/projeto' }} && php artisan schedule:run >> /dev/null 2>&1</pre>
                    </div>
                    <div>
                        <p class="ep-label flex items-center gap-2">
                            <span class="ep-chip !h-5 !px-1.5 tabular-nums">2</span>
                            Fila (deixe rodando em outro terminal ou com Supervisor):
                        </p>
                        <pre class="overflow-x-auto rounded-xl border border-[var(--ep-line)] bg-[var(--ep-input)] px-4 py-3 text-left font-mono text-[12.5px] text-[var(--ep-text-2)]">php artisan queue:work</pre>
                    </div>
                    <div v-if="cron_url" class="space-y-2">
                        <p class="ep-label flex items-center gap-2 !mb-0">
                            <span class="ep-chip !h-5 !px-1.5 tabular-nums">3</span>
                            URL do cron (para ferramentas externas: cron-job.org, EasyCron, UptimeRobot etc.):
                        </p>
                        <div class="flex items-stretch gap-2">
                            <code class="min-w-0 flex-1 break-all rounded-xl border border-[var(--ep-line)] bg-[var(--ep-input)] px-4 py-2.5 font-mono text-[12.5px] text-[var(--ep-text-2)]">{{ cron_url }}</code>
                            <button
                                type="button"
                                class="ep-btn-secondary !h-auto shrink-0"
                                @click="navigator.clipboard?.writeText(cron_url)"
                            >
                                <Copy class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                                Copiar
                            </button>
                        </div>
                        <p class="ep-help">
                            Configure a URL para ser chamada a cada minuto. Defina <code class="rounded-md bg-[var(--ep-active)] px-1.5 py-px font-mono text-[11.5px] text-[var(--ep-text-2)]">CRON_SECRET</code> no .env para gerar o link.
                        </p>
                    </div>
                    <p v-else class="ep-help">
                        Para usar a URL do cron, adicione <code class="rounded-md bg-[var(--ep-active)] px-1.5 py-px font-mono text-[11.5px] text-[var(--ep-text-2)]">CRON_SECRET=seu_token_secreto</code> no arquivo .env.
                    </p>
                </div>

                <div class="mt-5 flex flex-wrap items-center gap-x-2 gap-y-1 border-t border-[var(--ep-line)] pt-4 text-[12.5px] text-[var(--ep-text-3)]">
                    URL da aplicação:
                    <a :href="app_url" class="break-all font-medium text-[var(--ep-accent)] underline-offset-4 hover:underline" target="_blank" rel="noopener">{{ app_url }}</a>
                </div>
            </section>
        </div>
    </div>
</template>
