// Matiz estável por pessoa no avatar de iniciais (.ep-avatar): a mesma pessoa tem a mesma cor em todas as telas.
// Uso: <span class="ep-avatar" v-avatar="nome || email">AB</span>
// Paleta fria (azul-petróleo → rosa) fora das faixas semânticas: verde/pix, âmbar/boleto, vermelho/erro.
const HUES = [210, 234, 286, 308, 330, 354];

export const avatarHue = (name) => {
    const s = String(name ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').trim().toLowerCase();
    let h = 0x811c9dc5;
    for (const ch of s) h = Math.imul(h ^ ch.codePointAt(0), 0x01000193);
    h ^= h >>> 16;
    h = Math.imul(h, 0x85ebca6b);
    h ^= h >>> 13;
    h = Math.imul(h, 0xc2b2ae35);
    h ^= h >>> 16;
    return HUES[(h >>> 0) % HUES.length];
};

const apply = (el, { value }) => el.style.setProperty('--av-h', avatarHue(value));

export default { mounted: apply, updated: apply };
