<script setup>
import { computed } from 'vue';

const props = defineProps({
  logoUrl: { type: String, default: '' },
  subject: { type: String, default: '' },
  bodyHtml: { type: String, default: '' },
  fromName: { type: String, default: '' },
});

const SAMPLE = {
  nome_cliente: 'Maria Silva',
  nome_produto: 'Meu Curso',
  link_acesso: 'https://exemplo.com/m/xxxxx',
  email_cliente: 'maria@exemplo.com',
};

function replacePlaceholders(text) {
  if (!text || typeof text !== 'string') return '';
  return text
    .replace(/\{nome_cliente\}/g, SAMPLE.nome_cliente)
    .replace(/\{nome_produto\}/g, SAMPLE.nome_produto)
    .replace(/\{link_acesso\}/g, SAMPLE.link_acesso)
    .replace(/\{email_cliente\}/g, SAMPLE.email_cliente);
}

const previewSubject = computed(() => replacePlaceholders(props.subject));
const previewBodyHtml = computed(() => replacePlaceholders(props.bodyHtml));
</script>

<template>
  <div class="overflow-hidden rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)]">
    <div class="flex items-center gap-2 border-b border-[var(--ep-line)] px-4 py-2.5">
      <span class="flex items-center gap-1.5" aria-hidden="true">
        <span class="h-2 w-2 rounded-full bg-[var(--ep-line-strong)]" />
        <span class="h-2 w-2 rounded-full bg-[var(--ep-line-strong)]" />
        <span class="h-2 w-2 rounded-full bg-[var(--ep-line-strong)]" />
      </span>
      <span class="ml-1 text-[12px] font-medium text-[var(--ep-text-2)]">Preview</span>
      <span class="ep-chip ml-auto">dados de exemplo</span>
    </div>
    <div class="space-y-1.5 border-b border-[var(--ep-line)] px-4 py-3 text-[12.5px]">
      <div v-if="fromName" class="flex gap-2">
        <span class="w-14 shrink-0 text-[var(--ep-text-4)]">De:</span>
        <span class="truncate text-[var(--ep-text-2)]">{{ fromName }}</span>
      </div>
      <div v-if="previewSubject" class="flex gap-2">
        <span class="w-14 shrink-0 text-[var(--ep-text-4)]">Assunto:</span>
        <span class="truncate font-medium text-[var(--ep-text)]">{{ previewSubject }}</span>
      </div>
    </div>
    <div class="max-h-[520px] min-h-[200px] overflow-auto p-4">
      <div class="rounded-xl bg-white p-5 text-[#334155] shadow-[0_1px_2px_rgba(0,0,0,0.12),0_12px_32px_-16px_rgba(0,0,0,0.45)] ring-1 ring-black/5">
        <div v-if="logoUrl" class="mb-4 flex justify-center">
          <img :src="logoUrl" alt="Logo" class="max-h-10 w-auto object-contain mx-auto" @error="($e) => $e.target.style.display = 'none'" />
        </div>
        <div
          class="email-preview-body max-w-none break-words font-sans text-sm text-[#334155]"
          v-html="previewBodyHtml"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
.email-preview-body :deep(table) { width: 100%; max-width: 100%; }
.email-preview-body :deep(a) { color: #0ea5e9; text-decoration: none; }
.email-preview-body :deep(p) { margin: 0 0 0.75em; line-height: 1.5; }
.email-preview-body :deep(strong) { font-weight: 600; }
</style>
