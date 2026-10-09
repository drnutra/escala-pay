<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import Button from '@/components/ui/Button.vue';
import ProductPartnersTable from '@/components/produtos/ProductPartnersTable.vue';
import { Link2, ArrowUpRight } from 'lucide-vue-next';

const props = defineProps({
    productId: { type: String, required: true },
});

const loading = ref(true);
const saving = ref(false);
const program = ref(null);
const affiliates = ref([]);
const message = ref('');

const programForm = ref({
    enabled: false,
    default_commission_percent: 10,
    manual_approval: true,
    share_buyer_data: false,
    public_slug: '',
    support_email: '',
    description: '',
    settlement_days_pix: 0,
    settlement_days_card: 30,
    settlement_days_boleto: 2,
});

async function load() {
    loading.value = true;
    try {
        const { data } = await axios.get(`/produtos/${props.productId}/affiliate-program`);
        program.value = data.program;
        affiliates.value = data.affiliates ?? [];
        syncProgramForm(data.program ?? {});
    } finally {
        loading.value = false;
    }
}

function syncProgramForm(source) {
    programForm.value = {
        enabled: Boolean(source.enabled),
        default_commission_percent: Number(source.default_commission_percent ?? 10),
        manual_approval: source.manual_approval !== false,
        share_buyer_data: Boolean(source.share_buyer_data),
        public_slug: source.public_slug ?? '',
        support_email: source.support_email ?? '',
        description: source.description ?? '',
        settlement_days_pix: Number(source.settlement_days_pix ?? 0),
        settlement_days_card: Number(source.settlement_days_card ?? 30),
        settlement_days_boleto: Number(source.settlement_days_boleto ?? 2),
    };
}

function buildProgramPayload() {
    const f = programForm.value;

    return {
        enabled: Boolean(f.enabled),
        default_commission_percent: Number(f.default_commission_percent),
        manual_approval: Boolean(f.manual_approval),
        share_buyer_data: Boolean(f.share_buyer_data),
        public_slug: f.public_slug?.trim() || null,
        support_email: f.support_email?.trim() || null,
        description: f.description?.trim() || null,
        settlement_days_pix: Number(f.settlement_days_pix ?? 0),
        settlement_days_card: Number(f.settlement_days_card ?? 30),
        settlement_days_boleto: Number(f.settlement_days_boleto ?? 2),
    };
}

async function saveProgram() {
    saving.value = true;
    message.value = '';
    try {
        const payload = buildProgramPayload();
        let data;
        try {
            ({ data } = await axios.put(`/produtos/${props.productId}/affiliate-program`, payload));
        } catch (putError) {
            if (putError.response?.status === 405 || putError.response?.status === 501) {
                ({ data } = await axios.post(`/produtos/${props.productId}/affiliate-program`, payload));
            } else {
                throw putError;
            }
        }
        program.value = data.program;
        syncProgramForm(data.program ?? {});
        message.value = data.program?.enabled
            ? 'Programa salvo e afiliação ativada.'
            : 'Programa salvo.';
    } catch (e) {
        const errors = e.response?.data?.errors;
        message.value = errors
            ? Object.values(errors).flat().join(' ')
            : e.response?.data?.message || 'Erro ao salvar.';
    } finally {
        saving.value = false;
    }
}

async function updateAffiliate(affiliate, patch) {
    await axios.put(`/produtos/${props.productId}/affiliates/${affiliate.id}`, patch);
    await load();
}

function copyLink(url) {
    navigator.clipboard?.writeText(url);
    message.value = 'Link copiado.';
}

const publicPageUrl = computed(() => {
    if (!programForm.value.enabled) {
        return '';
    }

    return program.value?.public_page_url
        || (programForm.value.public_slug ? `/afiliar/${programForm.value.public_slug}` : '');
});

const affiliateRows = computed(() =>
    affiliates.value.map((a) => ({
        id: a.id,
        created_at: a.created_at,
        name: a.user?.name ?? null,
        email: a.user?.email ?? null,
        product_name: a.product_name,
        commission_percent:
            a.commission_percent ?? programForm.value.default_commission_percent ?? null,
        status: a.status,
        _raw: a,
    }))
);

function affiliateById(id) {
    return affiliates.value.find((a) => a.id === id);
}

onMounted(load);
</script>

<template>
    <div class="space-y-4">
        <section class="panel-card p-6" aria-labelledby="afiliados-programa">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 id="afiliados-programa" class="text-[15px] font-semibold tracking-[-0.015em] text-[var(--ep-text)]">Programa de afiliados</h2>
                    <p class="mt-1 text-[12.5px] text-[var(--ep-text-3)]">Defina comissão, aprovação e prazos de liberação para quem divulga este produto.</p>
                </div>
                <span class="ep-chip shrink-0" :class="program?.enabled ? 'ep-chip--pos' : ''">
                    <span
                        class="h-1.5 w-1.5 rounded-full bg-current"
                        :class="program?.enabled ? '' : 'opacity-60'"
                        aria-hidden="true"
                    />
                    {{ program?.enabled ? 'Afiliação ativa' : 'Afiliação desativada' }}
                </span>
            </div>

            <form class="mt-6 space-y-6" @submit.prevent="saveProgram">
                <label
                    class="flex cursor-pointer items-start gap-3 rounded-2xl border p-4 transition-colors duration-150"
                    :class="programForm.enabled
                        ? 'border-[color-mix(in_oklab,var(--ep-accent)_40%,transparent)] bg-[color-mix(in_oklab,var(--ep-accent)_9%,transparent)]'
                        : 'border-[var(--ep-line)] bg-[var(--ep-card-2)] hover:border-[var(--ep-line-strong)]'"
                >
                    <input v-model="programForm.enabled" type="checkbox" class="mt-0.5 h-4 w-4 shrink-0 cursor-pointer accent-[var(--ep-accent)]" />
                    <span class="min-w-0">
                        <span class="block text-[13.5px] font-medium text-[var(--ep-text)]">Ativar afiliação para este produto</span>
                        <span class="mt-0.5 block text-[12px] text-[var(--ep-text-4)]">Libera a página pública de cadastro e o link de divulgação de cada afiliado.</span>
                    </span>
                </label>

                <div class="grid gap-x-4 gap-y-5 md:grid-cols-2">
                    <div>
                        <label class="ep-label">Comissão padrão (%)</label>
                        <div class="relative">
                            <input
                                v-model.number="programForm.default_commission_percent"
                                type="number"
                                min="0"
                                max="100"
                                class="ep-input pr-9 tabular-nums"
                            />
                            <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[12.5px] text-[var(--ep-text-4)]" aria-hidden="true">%</span>
                        </div>
                        <p class="ep-help">Vale para quem não tiver comissão própria.</p>
                    </div>
                    <div>
                        <label class="ep-label">Slug da página pública</label>
                        <div class="flex">
                            <span class="inline-flex h-[38px] shrink-0 items-center rounded-l-xl border border-r-0 border-[var(--ep-input-border)] bg-[var(--ep-card-2)] px-3 font-mono text-[12px] text-[var(--ep-text-4)]" aria-hidden="true">/afiliar/</span>
                            <input v-model="programForm.public_slug" type="text" class="ep-input !rounded-l-none" />
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <label class="ep-label">E-mail de suporte</label>
                        <input v-model="programForm.support_email" type="email" class="ep-input" />
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)]">
                    <label class="flex cursor-pointer items-start gap-3 px-4 py-3.5 transition-colors duration-150 hover:bg-[var(--ep-hover)]">
                        <input v-model="programForm.manual_approval" type="checkbox" class="mt-0.5 h-4 w-4 shrink-0 cursor-pointer accent-[var(--ep-accent)]" />
                        <span class="text-[13px] font-medium text-[var(--ep-text)]">Aprovar afiliados manualmente</span>
                    </label>
                    <div class="ep-divider" />
                    <label class="flex cursor-pointer items-start gap-3 px-4 py-3.5 transition-colors duration-150 hover:bg-[var(--ep-hover)]">
                        <input v-model="programForm.share_buyer_data" type="checkbox" class="mt-0.5 h-4 w-4 shrink-0 cursor-pointer accent-[var(--ep-accent)]" />
                        <span class="min-w-0">
                            <span class="block text-[13px] font-medium text-[var(--ep-text)]">Compartilhar dados do comprador com afiliados (nome, e-mail e telefone nas vendas)</span>
                            <span class="mt-0.5 block text-[12px] text-[var(--ep-text-4)]">
                                Se desativado, afiliados veem os dados mascarados na listagem de vendas.
                            </span>
                        </span>
                    </label>
                </div>

                <div>
                    <label class="ep-label">Descrição (página de afiliação)</label>
                    <textarea v-model="programForm.description" rows="3" class="ep-input" />
                </div>

                <div>
                    <div class="mb-3 flex items-baseline justify-between gap-3">
                        <h3 class="ep-section-title">Liberação da comissão</h3>
                        <span class="text-[12px] text-[var(--ep-text-4)]">em dias, por forma de pagamento</span>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-3.5">
                            <label class="mb-2 flex items-center gap-2 text-[12.5px] font-medium text-[var(--ep-text-2)]">
                                <span class="h-2 w-2 rounded-full bg-[var(--ep-pix)]" aria-hidden="true" />
                                Pix
                            </label>
                            <div class="relative">
                                <input v-model.number="programForm.settlement_days_pix" type="number" min="0" class="ep-input pr-12 tabular-nums" />
                                <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[12px] text-[var(--ep-text-4)]" aria-hidden="true">dias</span>
                            </div>
                        </div>
                        <div class="rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-3.5">
                            <label class="mb-2 flex items-center gap-2 text-[12.5px] font-medium text-[var(--ep-text-2)]">
                                <span class="h-2 w-2 rounded-full bg-[var(--ep-cartao)]" aria-hidden="true" />
                                Cartão
                            </label>
                            <div class="relative">
                                <input v-model.number="programForm.settlement_days_card" type="number" min="0" class="ep-input pr-12 tabular-nums" />
                                <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[12px] text-[var(--ep-text-4)]" aria-hidden="true">dias</span>
                            </div>
                        </div>
                        <div class="rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-3.5">
                            <label class="mb-2 flex items-center gap-2 text-[12.5px] font-medium text-[var(--ep-text-2)]">
                                <span class="h-2 w-2 rounded-full bg-[var(--ep-boleto)]" aria-hidden="true" />
                                Boleto
                            </label>
                            <div class="relative">
                                <input v-model.number="programForm.settlement_days_boleto" type="number" min="0" class="ep-input pr-12 tabular-nums" />
                                <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[12px] text-[var(--ep-text-4)]" aria-hidden="true">dias</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="publicPageUrl" class="flex items-start gap-3 rounded-2xl border border-[color-mix(in_oklab,var(--ep-accent)_30%,transparent)] bg-[color-mix(in_oklab,var(--ep-accent)_8%,transparent)] p-4">
                    <span class="ep-kpi__icon !h-8 !w-8 shrink-0" aria-hidden="true">
                        <Link2 class="h-4 w-4" stroke-width="1.75" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-[13px] font-medium text-[var(--ep-text)]">Página de cadastro de afiliados</p>
                        <a :href="publicPageUrl" class="mt-0.5 inline-flex items-center gap-1 break-all font-mono text-[12.5px] text-[var(--ep-accent)] hover:underline" target="_blank" rel="noopener">
                            {{ publicPageUrl }}
                            <ArrowUpRight class="h-3.5 w-3.5 shrink-0" stroke-width="1.75" aria-hidden="true" />
                        </a>
                    </div>
                </div>
                <p
                    v-else-if="programForm.public_slug"
                    class="flex items-start gap-2.5 rounded-2xl border border-[color-mix(in_oklab,var(--ep-warn)_35%,transparent)] bg-[var(--ep-warn-bg)] px-4 py-3 text-[12.5px] leading-relaxed text-[var(--ep-text-2)]"
                >
                    <span class="mt-[7px] h-1.5 w-1.5 shrink-0 rounded-full bg-[var(--ep-warn)]" aria-hidden="true" />
                    <span>
                        Marque <strong class="font-medium text-[var(--ep-text)]">Ativar afiliação para este produto</strong> e salve para liberar o link de cadastro
                        (<code class="rounded-md bg-[var(--ep-active)] px-1.5 py-0.5 font-mono text-[11.5px] text-[var(--ep-text)]">/afiliar/{{ programForm.public_slug }}</code>).
                    </span>
                </p>

                <div class="flex flex-wrap items-center gap-3 border-t border-[var(--ep-line)] pt-5">
                    <Button type="submit" :disabled="saving">{{ saving ? 'Salvando…' : 'Salvar programa' }}</Button>
                    <p v-if="message" class="text-[12.5px] text-[var(--ep-text-3)]" role="status">{{ message }}</p>
                </div>
            </form>
        </section>

        <section class="space-y-3" aria-labelledby="afiliados-lista">
            <div class="flex items-baseline justify-between gap-3 px-1">
                <h3 id="afiliados-lista" class="ep-section-title">Afiliados</h3>
                <span class="text-[12px] tabular-nums text-[var(--ep-text-4)]">{{ affiliates.length }} {{ affiliates.length === 1 ? 'afiliado' : 'afiliados' }}</span>
            </div>
            <div v-if="loading" class="panel-card ep-data flex items-center justify-center gap-2 px-5 py-10 text-[12.5px] text-[var(--ep-text-3)]">
                <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-[var(--ep-accent)]" aria-hidden="true" />
                Carregando…
            </div>
            <ProductPartnersTable
                v-else
                :rows="affiliateRows"
                :show-product-column="false"
                empty-label="Nenhum afiliado cadastrado."
            >
                <template #menu="{ row, close }">
                    <template v-if="row">
                        <button
                            v-if="affiliateById(row.id)?.affiliate_link"
                            type="button"
                            class="flex w-full items-center px-3 py-2 text-left text-[13px] text-[var(--ep-text-2)] transition-colors duration-150 hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)]"
                            @click="copyLink(affiliateById(row.id).affiliate_link); close()"
                        >
                            Copiar link
                        </button>
                        <button
                            v-if="row.status === 'pending'"
                            type="button"
                            class="flex w-full items-center px-3 py-2 text-left text-[13px] text-[var(--ep-pos)] transition-colors duration-150 hover:bg-[var(--ep-pos-bg)]"
                            @click="updateAffiliate(affiliateById(row.id), { status: 'approved' }); close()"
                        >
                            Aprovar
                        </button>
                        <button
                            v-if="row.status === 'pending'"
                            type="button"
                            class="flex w-full items-center px-3 py-2 text-left text-[13px] text-[var(--ep-text-2)] transition-colors duration-150 hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)]"
                            @click="updateAffiliate(affiliateById(row.id), { status: 'rejected' }); close()"
                        >
                            Rejeitar
                        </button>
                        <button
                            v-if="row.status === 'approved'"
                            type="button"
                            class="flex w-full items-center px-3 py-2 text-left text-[13px] text-[var(--ep-neg)] transition-colors duration-150 hover:bg-[var(--ep-neg-bg)]"
                            @click="updateAffiliate(affiliateById(row.id), { status: 'removed' }); close()"
                        >
                            Remover
                        </button>
                    </template>
                </template>
            </ProductPartnersTable>
        </section>
    </div>
</template>
