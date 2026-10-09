<script setup>
import { ref } from 'vue';
import axios from 'axios';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import { Download, Filter } from 'lucide-vue-next';

defineOptions({ layout: LayoutInfoprodutor });

const props = defineProps({
    products: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const downloading = ref(false);
const error = ref(null);

const form = ref({
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
    product_id: props.filters?.product_id || '',
    payment_method: props.filters?.payment_method || '',
    status: props.filters?.status || 'completed',
});

function maskDateBr(raw) {
    const digits = String(raw || '').replace(/\D/g, '').slice(0, 8);
    const dd = digits.slice(0, 2);
    const mm = digits.slice(2, 4);
    const yyyy = digits.slice(4, 8);
    if (digits.length <= 2) return dd;
    if (digits.length <= 4) return `${dd}/${mm}`;
    return `${dd}/${mm}/${yyyy}`;
}

function toIsoDateBr(value) {
    const v = String(value || '').trim();
    if (!v) return null;
    // Accept dd/mm/yyyy
    const m = v.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    if (!m) return null;
    const dd = Number(m[1]);
    const mm = Number(m[2]);
    const yyyy = Number(m[3]);
    if (mm < 1 || mm > 12 || dd < 1 || dd > 31 || yyyy < 1900) return null;
    return `${String(yyyy).padStart(4, '0')}-${String(mm).padStart(2, '0')}-${String(dd).padStart(2, '0')}`;
}

async function openPdf() {
    error.value = null;
    if (downloading.value) return;
    if (!form.value.date_from || !form.value.date_to) {
        error.value = 'Informe data inicial e data final.';
        return;
    }
    const isoFrom = toIsoDateBr(form.value.date_from);
    const isoTo = toIsoDateBr(form.value.date_to);
    if (!isoFrom || !isoTo) {
        error.value = 'Formato de data inválido. Use dd/mm/aaaa.';
        return;
    }
    const payload = {
        ...form.value,
        date_from: isoFrom,
        date_to: isoTo,
    };

    downloading.value = true;
    try {
        const res = await axios.post('/vendas/comprovacao/exportar/pdf', payload, {
            responseType: 'blob',
        });

        const blob = new Blob([res.data], { type: 'application/pdf' });
        const url = window.URL.createObjectURL(blob);
        window.open(url, '_blank', 'noopener,noreferrer');
        // revoke later (let browser load it)
        setTimeout(() => window.URL.revokeObjectURL(url), 30_000);
    } catch (e) {
        error.value = e?.response?.data?.message || 'Falha ao gerar PDF. Tente novamente.';
    } finally {
        downloading.value = false;
    }
}
</script>

<template>
    <div class="mx-auto w-full max-w-4xl space-y-5 px-4 py-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <div class="flex items-center gap-1.5 text-[12.5px] font-medium text-[var(--ep-text-3)]">
                    <Filter class="h-4 w-4" :stroke-width="1.75" />
                    <span>Comprovação</span>
                    <span
                        class="inline-flex h-4 w-4 items-center justify-center rounded-full border border-[var(--ep-line-strong)] text-[10px] text-[var(--ep-text-4)]"
                        title="Exporta um PDF com dossiês de comprovação (dados do comprador + evidências de entrega/atividade). Ideal para anexar em gateways em caso de MED/chargeback/auditoria."
                    >
                        ?
                    </span>
                </div>
                <h1 class="ep-page-heading mt-1.5">Exportar comprovações (PDF)</h1>
                <p class="mt-1 max-w-xl text-[13px] text-[var(--ep-text-3)]">
                    Gera um PDF com comprovações (1 página por pedido) para pedidos filtrados (máximo 200 por exportação).
                </p>
            </div>

            <button
                class="ep-btn shrink-0"
                :disabled="downloading"
                @click="openPdf"
            >
                <Download class="h-4 w-4" :stroke-width="1.75" />
                {{ downloading ? 'Gerando...' : 'Abrir PDF' }}
            </button>
        </div>

        <div v-if="error" class="flex items-start gap-2.5 rounded-[14px] border border-[color-mix(in_oklab,var(--ep-neg)_35%,transparent)] bg-[var(--ep-neg-bg)] px-4 py-3 text-[13px] text-[var(--ep-text)]" role="alert">
            <span class="mt-[6px] h-1.5 w-1.5 shrink-0 rounded-full bg-[var(--ep-neg)]" aria-hidden="true" />
            {{ error }}
        </div>

        <section class="panel-card grid grid-cols-1 gap-4 p-6 sm:grid-cols-2" aria-label="Filtros da exportação">
            <div>
                <label class="ep-label">Data de</label>
                <input
                    :value="form.date_from"
                    type="text"
                    inputmode="numeric"
                    placeholder="dd/mm/aaaa"
                    class="ep-input tabular-nums"
                    @input="(e) => (form.date_from = maskDateBr(e.target.value))"
                />
            </div>
            <div>
                <label class="ep-label">Data até</label>
                <input
                    :value="form.date_to"
                    type="text"
                    inputmode="numeric"
                    placeholder="dd/mm/aaaa"
                    class="ep-input tabular-nums"
                    @input="(e) => (form.date_to = maskDateBr(e.target.value))"
                />
            </div>

            <div>
                <label class="ep-label">Produto <span class="font-normal text-[var(--ep-text-4)]">(opcional)</span></label>
                <select
                    v-model="form.product_id"
                    class="ep-input"
                >
                    <option value="">Todos</option>
                    <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
            </div>

            <div>
                <label class="ep-label">Forma de pagamento <span class="font-normal text-[var(--ep-text-4)]">(opcional)</span></label>
                <select
                    v-model="form.payment_method"
                    class="ep-input"
                >
                    <option value="">Todas</option>
                    <option value="pix">PIX</option>
                    <option value="card">Cartão</option>
                    <option value="boleto">Boleto</option>
                </select>
            </div>

            <div class="sm:col-span-2">
                <label class="ep-label">Status</label>
                <select
                    v-model="form.status"
                    class="ep-input"
                >
                    <option value="completed">Pago</option>
                    <option value="pending">Pendente</option>
                    <option value="disputed">MED</option>
                    <option value="cancelled">Cancelado</option>
                    <option value="refunded">Reembolsado</option>
                    <option value="all">Todos</option>
                </select>
                <p class="ep-help">Período em dd/mm/aaaa. O PDF abre em uma nova aba.</p>
            </div>
        </section>
    </div>
</template>
