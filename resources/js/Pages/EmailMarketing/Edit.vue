<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import Button from '@/components/ui/Button.vue';
import { ChevronLeft, TriangleAlert, Users, Send } from 'lucide-vue-next';
import axios from 'axios';

defineOptions({ layout: LayoutInfoprodutor });

const props = defineProps({
    campaign: {
        type: Object,
        required: true,
    },
    email_configured: { type: Boolean, default: false },
    products: { type: Array, default: () => [] },
    default_body_html: { type: String, default: '' },
});

const form = useForm({
    name: props.campaign.name,
    subject: props.campaign.subject,
    body_html: props.campaign.body_html || '',
    filter_config: props.campaign.filter_config || { all_customers: true, product_ids: [] },
});

const recipientCount = ref(null);
const recipientSample = ref([]);
const loadingRecipients = ref(false);

function useDefaultTemplate() {
    form.body_html = props.default_body_html || '';
}

async function previewRecipients() {
    loadingRecipients.value = true;
    recipientCount.value = null;
    recipientSample.value = [];
    try {
        const res = await axios.post(
            '/email-marketing/preview-recipients',
            { filter_config: form.filter_config },
            { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } }
        );
        if (res && res.data) {
            recipientCount.value = res.data.count;
            recipientSample.value = res.data.sample || [];
        }
    } catch (_) {
        recipientCount.value = 0;
    } finally {
        loadingRecipients.value = false;
    }
}

function confirmSend() {
    if (!props.email_configured) return;
    if (!confirm('Disparar esta campanha? Os e-mails serão enviados em lotes de até 30 por minuto (com pausa automática se o provedor limitar).')) return;
    router.post(`/email-marketing/${props.campaign.id}/send`);
}
</script>

<template>
    <div class="mx-auto max-w-6xl space-y-5">
        <header class="min-w-0">
            <Link
                href="/email-marketing"
                class="inline-flex items-center gap-1 text-[12.5px] font-medium text-[var(--ep-text-3)] transition-colors duration-150 hover:text-[var(--ep-text)]"
            >
                <ChevronLeft class="h-3.5 w-3.5" :stroke-width="1.75" aria-hidden="true" />
                Voltar
            </Link>
            <div class="mt-2 flex flex-wrap items-center gap-2.5">
                <h1 class="ep-page-heading">Editar campanha</h1>
                <span class="ep-chip max-w-full truncate">{{ campaign.name }}</span>
            </div>
            <p class="mt-1 text-[13px] text-[var(--ep-text-3)]">Ajuste o conteúdo e os destinatários antes do disparo.</p>
        </header>

        <div
            v-if="!email_configured"
            class="panel-card flex items-start gap-3 !border-[color-mix(in_oklab,var(--ep-warn)_35%,transparent)] p-4"
            role="status"
        >
            <TriangleAlert class="mt-0.5 h-[18px] w-[18px] shrink-0 text-[var(--ep-warn)]" :stroke-width="1.75" aria-hidden="true" />
            <p class="text-[13px] text-[var(--ep-text-2)]">
                Configure o e-mail em <Link href="/configuracoes" class="font-medium text-[var(--ep-accent)] underline-offset-4 hover:underline">Configurações &gt; E-mail</Link> antes
                de disparar.
            </p>
        </div>

        <form
            class="grid items-start gap-4 lg:grid-cols-12"
            @submit.prevent="form.put(`/email-marketing/${campaign.id}`)"
        >
            <!-- Conteúdo -->
            <section class="panel-card space-y-5 p-6 lg:col-span-8" aria-labelledby="em-conteudo">
                <h2 id="em-conteudo" class="ep-section-title">Conteúdo</h2>
                <div>
                    <label class="ep-label">Nome da campanha</label>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        class="ep-input"
                    />
                    <p v-if="form.errors.name" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">{{ form.errors.name }}</p>
                </div>
                <div>
                    <label class="ep-label">Assunto do e-mail</label>
                    <input
                        v-model="form.subject"
                        type="text"
                        required
                        class="ep-input"
                    />
                    <p v-if="form.errors.subject" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">{{ form.errors.subject }}</p>
                </div>
                <div>
                    <div class="mb-1.5 flex items-end justify-between gap-3">
                        <div class="min-w-0">
                            <label class="ep-label !mb-0">Corpo do e-mail (HTML)</label>
                            <p class="mt-0.5 text-[12px] text-[var(--ep-text-4)]">
                                Use <code class="rounded-md bg-[var(--ep-active)] px-1 font-mono text-[11.5px] text-[var(--ep-text-2)]">{nome}</code>
                                e <code class="rounded-md bg-[var(--ep-active)] px-1 font-mono text-[11.5px] text-[var(--ep-text-2)]">{email}</code> para personalizar.
                            </p>
                        </div>
                        <Button type="button" variant="secondary" size="sm" class="shrink-0" @click="useDefaultTemplate">Usar template padrão</Button>
                    </div>
                    <textarea
                        v-model="form.body_html"
                        rows="14"
                        required
                        spellcheck="false"
                        class="ep-input !h-auto min-h-[320px] resize-y py-3 font-mono !text-[12.5px] leading-5"
                    />
                    <p v-if="form.errors.body_html" class="mt-1.5 text-[12px] text-[var(--ep-neg)]">{{ form.errors.body_html }}</p>
                </div>
            </section>

            <!-- Destinatários + ações -->
            <aside class="space-y-4 lg:sticky lg:top-24 lg:col-span-4">
                <section class="panel-card p-5" aria-labelledby="em-destinatarios">
                    <h2 id="em-destinatarios" class="ep-section-title">Destinatários</h2>
                    <div class="mt-3 space-y-2">
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] px-3.5 py-3 transition-colors duration-150 hover:border-[var(--ep-line-strong)] has-[:checked]:border-[color-mix(in_oklab,var(--ep-accent)_55%,transparent)] has-[:checked]:bg-[color-mix(in_oklab,var(--ep-accent)_10%,transparent)]">
                            <input v-model="form.filter_config.all_customers" type="radio" :value="true" class="h-4 w-4 shrink-0 accent-[var(--ep-accent)]" />
                            <span class="text-[13px] font-medium text-[var(--ep-text)]">Todos os compradores</span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] px-3.5 py-3 transition-colors duration-150 hover:border-[var(--ep-line-strong)] has-[:checked]:border-[color-mix(in_oklab,var(--ep-accent)_55%,transparent)] has-[:checked]:bg-[color-mix(in_oklab,var(--ep-accent)_10%,transparent)]">
                            <input v-model="form.filter_config.all_customers" type="radio" :value="false" class="h-4 w-4 shrink-0 accent-[var(--ep-accent)]" />
                            <span class="text-[13px] font-medium text-[var(--ep-text)]">Compradores de produto(s) específico(s)</span>
                        </label>
                        <div v-if="form.filter_config.all_customers === false" class="pt-1">
                            <select
                                v-model="form.filter_config.product_ids"
                                multiple
                                class="ep-input !h-auto min-h-[132px] py-2 !text-[13px]"
                            >
                                <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                            <p class="ep-help">Segure Ctrl (ou ⌘) para selecionar mais de um.</p>
                        </div>
                    </div>
                    <div class="mt-4 border-t border-[var(--ep-line)] pt-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <Button type="button" variant="secondary" size="sm" :disabled="loadingRecipients" @click="previewRecipients">
                                <Users class="h-3.5 w-3.5" :stroke-width="1.75" aria-hidden="true" />
                                {{ loadingRecipients ? 'Carregando...' : 'Ver destinatários' }}
                            </Button>
                            <span v-if="recipientCount !== null" class="text-[12.5px] text-[var(--ep-text-3)]">
                                <span class="text-[20px] font-semibold tabular-nums tracking-[-0.03em] text-[var(--ep-text)]">{{ recipientCount }}</span>
                                destinatário(s)
                            </span>
                        </div>
                        <ul v-if="recipientSample.length" class="-mx-2 mt-3 space-y-0.5">
                            <li v-for="(r, i) in recipientSample" :key="i" class="flex items-center gap-2.5 rounded-lg px-2 py-1.5 transition-colors duration-150 hover:bg-[var(--ep-hover)]">
                                <span v-avatar="r.name || r.email" class="ep-avatar !h-6 !w-6 shrink-0 !text-[10px]" aria-hidden="true">{{ (r.name || r.email || '?').charAt(0).toUpperCase() }}</span>
                                <div class="min-w-0">
                                    <p class="truncate text-[12.5px] text-[var(--ep-text)]">{{ r.email }}</p>
                                    <p class="truncate text-[11.5px] text-[var(--ep-text-4)]">{{ r.name }}</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </section>

                <div class="panel-card space-y-2 p-5">
                    <Button type="submit" variant="primary" class="w-full" :disabled="form.processing">Salvar</Button>
                    <Button
                        v-if="email_configured"
                        type="button"
                        variant="outline"
                        class="w-full"
                        :disabled="form.processing"
                        @click="confirmSend"
                    >
                        <Send class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
                        Disparar campanha
                    </Button>
                    <Link href="/email-marketing" class="block">
                        <Button type="button" variant="ghost" class="w-full">Cancelar</Button>
                    </Link>
                    <p class="ep-help text-center">Envio em lotes de até 30 e-mails por minuto.</p>
                </div>
            </aside>
        </form>
    </div>
</template>
