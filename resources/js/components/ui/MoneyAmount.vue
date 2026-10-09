<script setup>
import { computed } from 'vue';

/**
 * Valor monetário com hierarquia tipográfica: símbolo e centavos rebaixados,
 * inteiro em destaque, números tabulares. Usado nos números-herói do painel.
 */
const props = defineProps({
    value: { type: Number, default: 0 },
    currency: { type: String, default: 'BRL' },
    hidden: { type: Boolean, default: false },
    size: { type: String, default: 'md' }, // hero | lg | md
});

const parts = computed(() => {
    const code = (props.currency || 'BRL').trim().toUpperCase();
    const locale = code === 'BRL' ? 'pt-BR' : code === 'EUR' ? 'de-DE' : 'en-US';
    const out = { sign: '', symbol: '', int: '', decimal: '', fraction: '' };
    for (const p of new Intl.NumberFormat(locale, { style: 'currency', currency: code }).formatToParts(props.value ?? 0)) {
        if (p.type === 'currency') out.symbol = p.value;
        else if (p.type === 'integer' || p.type === 'group') out.int += p.value;
        else if (p.type === 'decimal') out.decimal = p.value;
        else if (p.type === 'fraction') out.fraction = p.value;
        else if (p.type === 'minusSign') out.sign = '−';
    }
    return out;
});
</script>

<template>
    <span class="ep-money" :class="`ep-money--${size}`">
        <span class="ep-money__sym">{{ parts.sign }}{{ parts.symbol }}</span>
        <template v-if="hidden">
            <span class="ep-money__int" aria-label="Valor oculto">••••••</span>
        </template>
        <template v-else>
            <span class="ep-money__int">{{ parts.int }}</span><span class="ep-money__frac">{{ parts.decimal }}{{ parts.fraction }}</span>
        </template>
    </span>
</template>
