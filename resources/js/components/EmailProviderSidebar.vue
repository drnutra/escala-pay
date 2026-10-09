<script setup>
import { ref, watch, computed } from 'vue';
import { X, Loader2, Lock } from 'lucide-vue-next';

const props = defineProps({
  open: { type: Boolean, default: false },
  provider: { type: Object, default: null },
  form: { type: Object, required: true },
  connectionResult: { type: Object, default: () => ({ status: null, message: '' }) },
  sendResult: { type: Object, default: () => ({ status: null, message: '' }) },
  connectionTesting: { type: Boolean, default: false },
  sendTestSending: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'test-connection', 'send-test', 'save']);

const testEmail = ref('');

watch(() => props.open, (newVal) => {
  if (!newVal) {
    testEmail.value = '';
  }
});

function handleTestConnection() {
  emit('test-connection');
}

function handleSendTest() {
  if (!testEmail.value) return;
  emit('send-test', testEmail.value);
}

const hasFixedDefaults = computed(() => !!props.provider?.defaults);
const isSendGrid = computed(() => props.provider?.id === 'sendgrid');

const inputClass =
    'block w-full rounded-xl border-2 border-zinc-200 bg-white px-4 py-2.5 text-zinc-900 placeholder-zinc-400 transition focus:border-[var(--color-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-zinc-500';
const selectClass =
    'block w-full rounded-xl border-2 border-zinc-200 bg-white px-4 py-2.5 text-zinc-900 transition focus:border-[var(--color-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white';
const fixedValueClass =
    'block w-full rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-2.5 text-zinc-600 dark:border-zinc-600 dark:bg-zinc-800/50 dark:text-zinc-400';
</script>

<template>
  <!-- Overlay -->
  <Transition
    enter-active-class="transition-opacity duration-200"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition-opacity duration-200"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div
      v-if="open"
      class="ep-scrim fixed inset-0 z-[100000]"
      @click="$emit('close')"
    />
  </Transition>

  <!-- Sidebar -->
  <Transition
    enter-active-class="transition-transform duration-300"
    enter-from-class="translate-x-full"
    enter-to-class="translate-x-0"
    leave-active-class="transition-transform duration-300"
    leave-from-class="translate-x-0"
    leave-to-class="translate-x-full"
  >
    <div
      v-if="open"
      class="ep-drawer fixed top-0 right-0 z-[100001] flex h-full w-full flex-col sm:w-[480px]"
      role="dialog"
      aria-modal="true"
    >
      <!-- Header -->
      <div class="flex shrink-0 items-start justify-between gap-4 border-b border-[var(--ep-line)] px-6 py-5">
        <div class="flex min-w-0 items-center gap-3">
          <span
            v-if="provider?.logo"
            class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-[var(--ep-glass-border)] bg-white/90 p-1.5"
          >
            <img :src="provider.logo" alt="" class="max-h-full w-auto object-contain" />
          </span>
          <div class="min-w-0">
            <h2 class="truncate text-[16px] font-semibold tracking-[-0.02em] text-[var(--ep-text)]">{{ provider?.title || 'Configurar E-mail' }}</h2>
            <p class="truncate text-[12.5px] text-[var(--ep-text-3)]">{{ provider?.description || '' }}</p>
          </div>
        </div>
        <button
          type="button"
          class="ep-btn-ghost ep-btn-icon -mr-2 shrink-0"
          aria-label="Fechar"
          @click="$emit('close')"
        >
          <X class="h-4 w-4" :stroke-width="1.75" aria-hidden="true" />
        </button>
      </div>

      <!-- Content -->
      <div class="min-h-0 flex-1 space-y-6 overflow-y-auto px-6 py-6">
        <!-- SendGrid: API Key + Remetente -->
        <template v-if="isSendGrid">
          <section class="space-y-4">
            <div>
              <h3 class="ep-section-title">Configuração SendGrid</h3>
              <p class="mt-1 text-[12.5px] leading-5 text-[var(--ep-text-3)]">
                Crie uma API Key em <a href="https://app.sendgrid.com/settings/api_keys" target="_blank" rel="noopener noreferrer" class="font-medium text-[var(--ep-accent)] underline-offset-4 hover:underline">SendGrid &gt; Settings &gt; API Keys</a>. Deixe em branco para manter a atual.
              </p>
            </div>
            <div class="space-y-4">
              <div>
                <label class="ep-label">API Key (SendGrid)</label>
                <input v-model="form.sendgrid_api_key" type="password" autocomplete="new-password" class="ep-input font-mono" placeholder="SG.xxx..." />
              </div>
              <div>
                <label class="ep-label">E-mail do remetente</label>
                <input v-model="form.sendgrid_mail_from_address" type="email" class="ep-input" placeholder="remetente@seudominio.com" />
                <p class="ep-help">O remetente deve estar verificado no SendGrid (Single Sender ou Domain Authentication).</p>
              </div>
              <div>
                <label class="ep-label">Nome do remetente</label>
                <input v-model="form.sendgrid_mail_from_name" type="text" class="ep-input" placeholder="Ex: Minha Loja" />
              </div>
            </div>
          </section>
        </template>

        <!-- SMTP Configuration (Hostinger ou SMTP genérico) -->
        <section v-else class="space-y-4">
          <h3 class="ep-section-title">Configurações SMTP</h3>

          <div class="grid gap-4 sm:grid-cols-2">
            <!-- Host, Porta, Criptografia: fixos quando o provedor tem defaults (ex.: Hostinger) -->
            <template v-if="hasFixedDefaults">
              <div>
                <label class="ep-label">Host</label>
                <div class="ep-input flex items-center justify-between gap-2 !bg-[var(--ep-card-2)] font-mono !text-[12.5px] !text-[var(--ep-text-3)]">
                  smtp.hostinger.com
                  <Lock class="h-3.5 w-3.5 shrink-0 text-[var(--ep-text-4)]" :stroke-width="1.75" aria-hidden="true" />
                </div>
              </div>
              <div>
                <label class="ep-label">Porta</label>
                <div class="ep-input flex items-center justify-between gap-2 !bg-[var(--ep-card-2)] font-mono !text-[12.5px] tabular-nums !text-[var(--ep-text-3)]">
                  465
                  <Lock class="h-3.5 w-3.5 shrink-0 text-[var(--ep-text-4)]" :stroke-width="1.75" aria-hidden="true" />
                </div>
              </div>
              <div class="sm:col-span-2">
                <label class="ep-label">Criptografia</label>
                <div class="ep-input flex items-center justify-between gap-2 !bg-[var(--ep-card-2)] font-mono !text-[12.5px] !text-[var(--ep-text-3)]">
                  SSL
                  <Lock class="h-3.5 w-3.5 shrink-0 text-[var(--ep-text-4)]" :stroke-width="1.75" aria-hidden="true" />
                </div>
              </div>
              <div class="sm:col-span-2">
                <label class="ep-label">Usuário</label>
                <input v-model="form.hostinger_smtp_username" type="text" class="ep-input" />
              </div>
              <div class="sm:col-span-2">
                <label class="ep-label">Senha</label>
                <input v-model="form.hostinger_smtp_password" type="password" autocomplete="new-password" class="ep-input" />
                <p class="ep-help">Deixe em branco para manter.</p>
              </div>
            </template>
            <template v-else>
              <div>
                <label class="ep-label">Host</label>
                <input v-model="form.smtp_host" type="text" class="ep-input font-mono !text-[13px]" />
              </div>
              <div>
                <label class="ep-label">Porta</label>
                <input v-model="form.smtp_port" type="text" inputmode="numeric" class="ep-input font-mono !text-[13px] tabular-nums" />
              </div>
              <div class="sm:col-span-2">
                <label class="ep-label">Criptografia</label>
                <select v-model="form.smtp_encryption" class="ep-input">
                  <option value="tls">TLS</option>
                  <option value="ssl">SSL</option>
                </select>
              </div>
              <div class="sm:col-span-2">
                <label class="ep-label">Usuário</label>
                <input v-model="form.smtp_username" type="text" class="ep-input" />
              </div>
              <div class="sm:col-span-2">
                <label class="ep-label">Senha</label>
                <input v-model="form.smtp_password" type="password" autocomplete="new-password" class="ep-input" />
                <p class="ep-help">Deixe em branco para manter.</p>
              </div>
            </template>
          </div>
        </section>

        <!-- Remetente: Hostinger usa o e-mail do usuário SMTP; SMTP genérico permite e-mail separado -->
        <section v-if="!isSendGrid && hasFixedDefaults" class="space-y-4 border-t border-[var(--ep-line)] pt-6">
          <div>
            <h3 class="ep-section-title">Remetente (nome)</h3>
            <p class="mt-1 text-[12.5px] leading-5 text-[var(--ep-text-3)]">O e-mail do remetente é o mesmo do usuário SMTP acima. Defina apenas o nome exibido:</p>
          </div>
          <div>
            <label class="ep-label">Nome do remetente</label>
            <input v-model="form.hostinger_mail_from_name" type="text" class="ep-input" placeholder="Ex: Minha Loja" />
          </div>
        </section>

        <section v-if="!isSendGrid && !hasFixedDefaults" class="space-y-4 border-t border-[var(--ep-line)] pt-6">
          <h3 class="ep-section-title">Remetente</h3>
          <div class="space-y-4">
            <div>
              <label class="ep-label">E-mail do remetente</label>
              <input v-model="form.mail_from_address" type="email" class="ep-input" placeholder="noreply@seudominio.com" />
              <p class="ep-help">Pode ser diferente do usuário SMTP, se o servidor permitir.</p>
            </div>
            <div>
              <label class="ep-label">Nome do remetente</label>
              <input v-model="form.mail_from_name" type="text" class="ep-input" placeholder="Ex: Minha Loja" />
            </div>
          </div>
        </section>

        <!-- Test Connection -->
        <section class="space-y-4 rounded-2xl border border-[var(--ep-line)] bg-[var(--ep-card-2)] p-4">
          <h3 class="ep-section-title">Testar configuração</h3>

          <button
            type="button"
            class="ep-btn-secondary w-full"
            :disabled="connectionTesting"
            @click="handleTestConnection"
          >
            <Loader2 v-if="connectionTesting" class="h-4 w-4 animate-spin shrink-0" :stroke-width="1.75" />
            {{ connectionTesting ? 'Testando...' : 'Testar conexão' }}
          </button>
          <p v-if="connectionResult.status === 'success'" class="flex items-start gap-2 rounded-xl border border-[color-mix(in_oklab,var(--ep-pos)_35%,transparent)] bg-[var(--ep-pos-bg)] px-3 py-2 text-[12.5px] text-[var(--ep-pos)]">
            <span class="mt-[7px] h-1.5 w-1.5 shrink-0 rounded-full bg-current" aria-hidden="true" />
            {{ connectionResult.message || 'Conexão estabelecida com sucesso.' }}
          </p>
          <p v-if="connectionResult.status === 'error'" class="flex items-start gap-2 rounded-xl border border-[color-mix(in_oklab,var(--ep-neg)_35%,transparent)] bg-[var(--ep-neg-bg)] px-3 py-2 text-[12.5px] text-[var(--ep-neg)]">
            <span class="mt-[7px] h-1.5 w-1.5 shrink-0 rounded-full bg-current" aria-hidden="true" />
            {{ connectionResult.message || 'Erro ao testar conexão.' }}
          </p>

          <div class="space-y-3 border-t border-[var(--ep-line)] pt-4">
            <label class="ep-label !mb-0">Enviar e-mail de teste</label>
            <div class="flex flex-col gap-2 sm:flex-row">
              <input
                v-model="testEmail"
                type="email"
                placeholder="destino@exemplo.com"
                class="ep-input min-w-0 flex-1"
              />
              <button
                type="button"
                class="ep-btn-secondary shrink-0"
                :disabled="!testEmail || sendTestSending"
                @click="handleSendTest"
              >
                <Loader2 v-if="sendTestSending" class="h-4 w-4 animate-spin shrink-0" :stroke-width="1.75" />
                {{ sendTestSending ? 'Enviando...' : 'Enviar e-mail de teste' }}
              </button>
            </div>
            <p v-if="sendResult.status === 'success'" class="flex items-start gap-2 rounded-xl border border-[color-mix(in_oklab,var(--ep-pos)_35%,transparent)] bg-[var(--ep-pos-bg)] px-3 py-2 text-[12.5px] text-[var(--ep-pos)]">
              <span class="mt-[7px] h-1.5 w-1.5 shrink-0 rounded-full bg-current" aria-hidden="true" />
              {{ sendResult.message || 'E-mail de teste enviado com sucesso.' }}
            </p>
            <p v-if="sendResult.status === 'error'" class="flex items-start gap-2 rounded-xl border border-[color-mix(in_oklab,var(--ep-neg)_35%,transparent)] bg-[var(--ep-neg-bg)] px-3 py-2 text-[12.5px] text-[var(--ep-neg)]">
              <span class="mt-[7px] h-1.5 w-1.5 shrink-0 rounded-full bg-current" aria-hidden="true" />
              {{ sendResult.message || 'Erro ao enviar e-mail de teste.' }}
            </p>
          </div>
        </section>
      </div>

      <!-- Salvar -->
      <div class="shrink-0 border-t border-[var(--ep-line)] px-6 py-4">
        <button
          type="button"
          class="ep-btn w-full !h-10"
          :disabled="form.processing"
          @click="$emit('save')"
        >
          Salvar configurações
        </button>
      </div>
    </div>
  </Transition>
</template>
