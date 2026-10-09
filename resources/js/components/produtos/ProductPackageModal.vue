<script setup>
import { computed, ref, watch } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import Button from '@/components/ui/Button.vue';
import { Download, Upload, Package, X } from 'lucide-vue-next';

const props = defineProps({
    open: { type: Boolean, default: false },
    mode: { type: String, default: 'import' }, // import | export
    product: { type: Object, default: null },
});

const emit = defineEmits(['update:open', 'imported']);

const busy = ref(false);
const error = ref('');
const warnings = ref([]);
const includeMedia = ref(true);
const file = ref(null);
const preview = ref(null);

const title = computed(() => (props.mode === 'export' ? 'Exportar produto' : 'Importar produto'));

watch(
    () => props.open,
    (v) => {
        if (!v) {
            busy.value = false;
            error.value = '';
            warnings.value = [];
            file.value = null;
            preview.value = null;
            includeMedia.value = true;
        }
    }
);

function close() {
    emit('update:open', false);
}

function onFileChange(e) {
    const f = e.target?.files?.[0] || null;
    file.value = f;
    preview.value = null;
    error.value = '';
    warnings.value = [];
}

async function runPreview() {
    if (!file.value) {
        error.value = 'Selecione um arquivo .getfy-product.';
        return;
    }
    busy.value = true;
    error.value = '';
    try {
        const fd = new FormData();
        fd.append('package', file.value);
        const { data } = await axios.post('/produtos/import/preview', fd, {
            headers: { Accept: 'application/json' },
        });
        if (!data?.success) {
            error.value = data?.message || 'Pacote inválido.';
            return;
        }
        preview.value = data;
        warnings.value = Array.isArray(data.warnings) ? data.warnings : [];
    } catch (err) {
        error.value = err.response?.data?.message || 'Falha ao ler o pacote.';
    } finally {
        busy.value = false;
    }
}

async function runImport() {
    if (!file.value) {
        error.value = 'Selecione um arquivo .getfy-product.';
        return;
    }
    busy.value = true;
    error.value = '';
    try {
        const fd = new FormData();
        fd.append('package', file.value);
        const { data } = await axios.post('/produtos/import', fd, {
            headers: { Accept: 'application/json' },
        });
        if (!data?.success) {
            error.value = data?.message || 'Falha na importação.';
            warnings.value = Array.isArray(data?.warnings) ? data.warnings : [];
            return;
        }
        warnings.value = Array.isArray(data.warnings) ? data.warnings : [];
        emit('imported', data);
        close();
        router.reload({ preserveScroll: true });
    } catch (err) {
        error.value = err.response?.data?.message || 'Falha na importação.';
        warnings.value = Array.isArray(err.response?.data?.warnings) ? err.response.data.warnings : [];
    } finally {
        busy.value = false;
    }
}

async function runExport() {
    if (!props.product?.id) {
        error.value = 'Produto inválido.';
        return;
    }
    busy.value = true;
    error.value = '';
    try {
        const res = await axios.post(
            `/produtos/${props.product.id}/export`,
            { include_media: includeMedia.value },
            { responseType: 'blob', headers: { Accept: 'application/zip, application/json' } }
        );

        const contentType = res.headers['content-type'] || '';
        if (contentType.includes('application/json')) {
            const text = await res.data.text();
            const json = JSON.parse(text);
            error.value = json.message || 'Falha ao exportar.';
            return;
        }

        const disposition = res.headers['content-disposition'] || '';
        let filename = `${props.product.name || 'produto'}.getfy-product`;
        const match = disposition.match(/filename="?([^"]+)"?/i);
        if (match?.[1]) {
            filename = match[1];
        }

        const url = window.URL.createObjectURL(res.data);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);
        close();
    } catch (err) {
        if (err.response?.data instanceof Blob) {
            try {
                const text = await err.response.data.text();
                const json = JSON.parse(text);
                error.value = json.message || 'Falha ao exportar.';
            } catch {
                error.value = 'Falha ao exportar.';
            }
        } else {
            error.value = err.response?.data?.message || 'Falha ao exportar.';
        }
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-[100001] flex items-center justify-center p-4"
            aria-modal="true"
            role="dialog"
            aria-labelledby="product-package-title"
        >
            <div class="ep-scrim fixed inset-0" aria-hidden="true" @click="close" />
            <div
                class="ep-modal relative w-full max-w-md p-6"
            >
                <div class="mb-5 flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-start gap-3.5">
                        <span class="ep-kpi__icon mt-0.5 h-10 w-10 shrink-0 rounded-[12px]" aria-hidden="true">
                            <Package class="h-[18px] w-[18px]" :stroke-width="1.75" />
                        </span>
                        <div class="min-w-0">
                            <h3 id="product-package-title" class="text-[16px] font-semibold tracking-[-0.02em] text-[var(--ep-text)]">{{ title }}</h3>
                            <p class="mt-1 text-[12.5px] leading-relaxed text-[var(--ep-text-3)]">
                                <template v-if="mode === 'export'">
                                    Gera um pacote <code class="rounded-[6px] border border-[var(--ep-line)] bg-[var(--ep-card-2)] px-1.5 py-px font-mono text-[11.5px] text-[var(--ep-text-2)]">.getfy-product</code>
                                    com checkout, ofertas/planos e área de membros.
                                </template>
                                <template v-else>
                                    Cria um produto novo a partir de um pacote exportado.
                                </template>
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="ep-btn-ghost ep-btn-icon -mr-2 -mt-1 !h-8 !w-8 shrink-0 !rounded-[10px] text-[var(--ep-text-3)]"
                        aria-label="Fechar"
                        @click="close"
                    >
                        <X class="h-[18px] w-[18px]" :stroke-width="1.75" />
                    </button>
                </div>

                <div v-if="mode === 'export'" class="space-y-3">
                    <div class="flex items-center justify-between gap-3 rounded-[14px] border border-[var(--ep-line)] bg-[var(--ep-card-2)] px-3.5 py-3">
                        <span class="text-[12.5px] text-[var(--ep-text-3)]">Produto</span>
                        <strong class="min-w-0 truncate text-[13px] font-medium text-[var(--ep-text)]">{{ product?.name }}</strong>
                    </div>
                    <label class="flex cursor-pointer items-start gap-3 rounded-[14px] border border-[var(--ep-line)] p-3.5 transition-colors duration-150 hover:border-[var(--ep-line-strong)] hover:bg-[var(--ep-hover)]">
                        <input v-model="includeMedia" type="checkbox" class="mt-0.5 h-4 w-4 shrink-0 cursor-pointer rounded-[5px] accent-[var(--ep-accent)]" />
                        <span class="text-[13px] text-[var(--ep-text-2)]">
                            <span class="font-medium text-[var(--ep-text)]">Incluir imagens e arquivos</span>
                            <span class="mt-0.5 block text-[12px] leading-relaxed text-[var(--ep-text-3)]">
                                Capa, banners do checkout, thumbnails, PDFs e mídias da área de membros. Vídeos externos (YouTube etc.) permanecem como link.
                            </span>
                        </span>
                    </label>
                </div>

                <div v-else class="space-y-3">
                    <div>
                        <label class="ep-label">Arquivo</label>
                        <div class="rounded-[14px] border border-dashed border-[var(--ep-line-strong)] bg-[var(--ep-card-2)] p-3">
                            <input
                                type="file"
                                accept=".zip,.getfy-product,application/zip"
                                class="block w-full cursor-pointer text-[12.5px] text-[var(--ep-text-3)] file:mr-3 file:h-8 file:cursor-pointer file:rounded-[10px] file:border file:border-solid file:border-[var(--ep-glass-border)] file:bg-[var(--ep-glass-strong)] file:px-3 file:text-[12.5px] file:font-medium file:text-[var(--ep-text)] hover:file:border-[var(--ep-line-strong)]"
                                @change="onFileChange"
                            />
                        </div>
                        <p v-if="file" class="mt-1.5 truncate text-[12px] text-[var(--ep-text-2)]">{{ file.name }}</p>
                    </div>

                    <div v-if="preview?.summary" class="rounded-[14px] border border-[var(--ep-line)] bg-[var(--ep-card-2)] px-3.5 py-3">
                        <p class="text-[13px] font-medium text-[var(--ep-text)]">
                            {{ preview.summary.name || 'Produto' }}
                        </p>
                        <p class="mt-1 text-[12px] tabular-nums text-[var(--ep-text-3)]">
                            {{ preview.summary.type }} ·
                            {{ preview.summary.sections || 0 }} seções ·
                            {{ preview.summary.modules || 0 }} módulos ·
                            {{ preview.summary.lessons || 0 }} aulas
                            <template v-if="preview.include_media">
                                · {{ preview.media_files || 0 }} arquivos de mídia
                            </template>
                        </p>
                    </div>
                </div>

                <p
                    v-if="error"
                    class="mt-4 rounded-[12px] border border-[color-mix(in_oklab,var(--ep-neg)_35%,transparent)] bg-[var(--ep-neg-bg)] px-3 py-2 text-[12.5px] text-[var(--ep-neg)]"
                >
                    {{ error }}
                </p>

                <ul
                    v-if="warnings.length"
                    class="mt-3 max-h-28 space-y-1 overflow-y-auto rounded-[12px] border border-[color-mix(in_oklab,var(--ep-warn)_35%,transparent)] bg-[var(--ep-warn-bg)] px-3 py-2 text-[12px] text-[var(--ep-warn)]"
                >
                    <li v-for="(w, i) in warnings" :key="i">{{ w }}</li>
                </ul>

                <div class="mt-6 flex flex-wrap justify-end gap-2">
                    <Button variant="outline" :disabled="busy" @click="close">Cancelar</Button>
                    <template v-if="mode === 'export'">
                        <Button variant="primary" :disabled="busy" @click="runExport">
                            <Download class="h-4 w-4" :stroke-width="1.75" />
                            {{ busy ? 'Gerando…' : 'Baixar pacote' }}
                        </Button>
                    </template>
                    <template v-else>
                        <Button variant="outline" :disabled="busy || !file" @click="runPreview">
                            <Package class="h-4 w-4" :stroke-width="1.75" />
                            Pré-visualizar
                        </Button>
                        <Button variant="primary" :disabled="busy || !file" @click="runImport">
                            <Upload class="h-4 w-4" :stroke-width="1.75" />
                            {{ busy ? 'Importando…' : 'Importar' }}
                        </Button>
                    </template>
                </div>
            </div>
        </div>
    </Teleport>
</template>
