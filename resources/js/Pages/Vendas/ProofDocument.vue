<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import { FileText, RefreshCw, ExternalLink, Download } from 'lucide-vue-next';

defineOptions({ layout: LayoutInfoprodutor });

const props = defineProps({
    order: { type: Object, required: true },
    proof_document: { type: Object, default: null },
    snapshot: { type: Object, default: () => ({}) },
});

const generating = ref(false);

const snapshotJson = computed(() => {
    try {
        return JSON.stringify(props.snapshot ?? {}, null, 2);
    } catch {
        return String(props.snapshot ?? '');
    }
});

function generate() {
    if (generating.value) return;
    generating.value = true;
    router.post(`/vendas/${props.order.id}/comprovacao/gerar`, {}, {
        preserveScroll: true,
        onFinish: () => (generating.value = false),
    });
}
</script>

<template>
    <div class="mx-auto w-full max-w-6xl px-4 py-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div class="min-w-0">
                <div class="flex items-center gap-1.5 text-[12.5px] font-medium text-[var(--ep-text-3)]">
                    <FileText class="h-4 w-4" :stroke-width="1.75" />
                    <span>Log de Atividade do Pedido</span>
                </div>
                <h1 class="ep-page-heading mt-1.5 truncate tabular-nums">
                    Pedido #{{ order.id }}
                </h1>
                <p class="mt-1 text-[13px] text-[var(--ep-text-3)]">
                    <span class="font-medium text-[var(--ep-text-2)]">{{ order?.buyer?.name ?? 'Comprador' }}</span>
                    <span v-if="order?.buyer?.email"> · {{ order.buyer.email }}</span>
                    <span v-if="order?.product?.name"> · {{ order.product.name }}</span>
                </p>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
                <button
                    class="ep-btn"
                    :disabled="generating"
                    @click="generate"
                >
                    <RefreshCw class="h-4 w-4" :class="generating ? 'animate-spin' : ''" :stroke-width="1.75" />
                    {{ generating ? 'Gerando...' : 'Gerar/atualizar dossiê' }}
                </button>

                <a
                    class="ep-btn-secondary"
                    :href="`/vendas/${order.id}/comprovacao/pdf`"
                >
                    <Download class="h-4 w-4 text-[var(--ep-text-3)]" :stroke-width="1.75" />
                    Baixar PDF
                </a>

                <a
                    v-if="proof_document?.verify_url"
                    class="ep-btn-secondary"
                    :href="proof_document.verify_url"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <ExternalLink class="h-4 w-4 text-[var(--ep-text-3)]" :stroke-width="1.75" />
                    Verificação pública
                </a>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
            <section class="panel-card p-5" aria-labelledby="proof-status">
                <div class="flex items-center justify-between gap-3">
                    <h2 id="proof-status" class="ep-section-title">Status do pedido</h2>
                    <span
                        class="ep-chip"
                        :class="{ completed: 'ep-chip--pos', paid: 'ep-chip--pos', pending: 'ep-chip--warn', disputed: 'ep-chip--neg', refunded: '', cancelled: '' }[order.status] ?? ''"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                        {{ order.status }}
                    </span>
                </div>
                <dl class="mt-3 text-[13px]">
                    <div v-if="order.gateway" class="flex items-start justify-between gap-4 border-b border-[var(--ep-line)] py-2.5 last:border-b-0">
                        <dt class="shrink-0 text-[var(--ep-text-3)]">Gateway</dt>
                        <dd class="min-w-0 text-right text-[var(--ep-text)]">{{ order.gateway }}</dd>
                    </div>
                    <div v-if="order.gateway_id" class="flex items-start justify-between gap-4 border-b border-[var(--ep-line)] py-2.5 last:border-b-0">
                        <dt class="shrink-0 text-[var(--ep-text-3)]">Transação</dt>
                        <dd class="min-w-0 break-all text-right font-mono text-[12px] text-[var(--ep-text-2)]">{{ order.gateway_id }}</dd>
                    </div>
                    <div v-if="order.customer_ip" class="flex items-start justify-between gap-4 border-b border-[var(--ep-line)] py-2.5 last:border-b-0">
                        <dt class="shrink-0 text-[var(--ep-text-3)]">IP checkout</dt>
                        <dd class="min-w-0 text-right font-mono text-[12px] text-[var(--ep-text-2)]">{{ order.customer_ip }}</dd>
                    </div>
                </dl>

                <div v-if="proof_document" class="mt-4 rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4">
                    <h3 class="ep-section-title">Documento</h3>
                    <dl class="mt-2 space-y-2 text-[13px]">
                        <div class="flex items-start justify-between gap-4">
                            <dt class="shrink-0 text-[var(--ep-text-3)]">Código</dt>
                            <dd class="min-w-0 break-all text-right font-mono text-[12.5px] font-medium text-[var(--ep-text)]">{{ proof_document.public_code }}</dd>
                        </div>
                        <div v-if="proof_document.generated_at" class="flex items-start justify-between gap-4">
                            <dt class="shrink-0 text-[var(--ep-text-3)]">Gerado em</dt>
                            <dd class="min-w-0 text-right tabular-nums text-[var(--ep-text-2)]">{{ proof_document.generated_at }}</dd>
                        </div>
                    </dl>
                </div>
            </section>

            <section class="panel-card ep-data p-5 lg:col-span-2" aria-labelledby="proof-snapshot">
                <h2 id="proof-snapshot" class="ep-section-title">Snapshot (JSON)</h2>
                <p class="ep-help mt-1">
                    Este JSON é a base do PDF e da verificação pública (com mascaramento). Gere o dossiê para fixar um código.
                </p>
                <pre class="mt-3 max-h-[70vh] overflow-auto rounded-xl border border-[var(--ep-line)] bg-[var(--ep-input)] p-4 font-mono text-[12px] leading-relaxed text-[var(--ep-text-2)]">{{ snapshotJson }}</pre>
            </section>
        </div>
    </div>
</template>
