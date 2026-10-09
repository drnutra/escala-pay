import { computed } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Paginação Inertia (LengthAwarePaginator do Laravel).
 *
 * @param {import('vue').ComputedRef<Record<string, unknown>|null|undefined>|import('vue').Ref<Record<string, unknown>|null|undefined>|(() => Record<string, unknown>|null|undefined)} paginatorSource
 */
export function useInertiaPagination(paginatorSource) {
    const paginator = computed(() => {
        const raw = typeof paginatorSource === 'function' ? paginatorSource() : paginatorSource?.value;
        return raw && typeof raw === 'object' ? raw : null;
    });

    const paginationPrev = computed(() => paginator.value?.links?.[0] ?? null);

    const paginationNext = computed(() => {
        const links = paginator.value?.links ?? [];
        return links.length > 1 ? links[links.length - 1] : null;
    });

    const paginationPages = computed(() => {
        const links = paginator.value?.links ?? [];
        if (links.length <= 2) return [];
        return links.slice(1, -1);
    });

    const paginationSummary = computed(() => {
        const current = paginator.value?.current_page ?? 1;
        const last = paginator.value?.last_page ?? 1;
        return `${current} / ${last}`;
    });

    const hasPagination = computed(() => (paginator.value?.links?.length ?? 0) > 3);

    function isEllipsisLink(link) {
        const label = String(link?.label ?? '').trim();
        return label === '...' || label === '…' || label.includes('&hellip;');
    }

    function visitPaginationPage(url) {
        if (!url || typeof url !== 'string') return;

        try {
            const target = new URL(url, window.location.origin);
            const params = {};
            target.searchParams.forEach((value, key) => {
                params[key] = value;
            });

            router.get(target.pathname, params, {
                preserveScroll: true,
                preserveState: false,
                replace: false,
            });
        } catch {
            router.visit(url, {
                preserveScroll: true,
                preserveState: false,
                replace: false,
            });
        }
    }

    function paginationLinkClass(link, { iconOnly = false } = {}) {
        return [
            'relative inline-flex shrink-0 items-center justify-center rounded-[10px] border text-[13px] font-medium tabular-nums transition-colors duration-150 touch-manipulation',
            iconOnly ? 'h-10 w-10 sm:h-9 sm:w-9' : 'min-h-10 min-w-10 px-3 py-2 sm:min-h-9 sm:min-w-9',
            link?.active
                ? 'ep-tab--active z-10 text-[var(--ep-text)]'
                : link?.url
                  ? 'border-transparent text-[var(--ep-text-3)] hover:bg-[var(--ep-hover)] hover:text-[var(--ep-text)]'
                  : 'cursor-not-allowed border-transparent text-[var(--ep-text-4)] opacity-60',
        ];
    }

    return {
        paginationPrev,
        paginationNext,
        paginationPages,
        paginationSummary,
        hasPagination,
        isEllipsisLink,
        visitPaginationPage,
        paginationLinkClass,
    };
}
