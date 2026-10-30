import { onUnmounted, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Keeps search, filters, and sorting in sync with the server through query strings.
 *
 * @param {string} url
 * @param {Record<string, import('vue').Ref>} filters
 * @param {{ sort?: string, direction?: string }} initial
 */
export function useTableQuery(url, filters, initial = {}) {
    const sort = ref(initial.sort ?? '');
    const direction = ref(initial.direction ?? 'asc');

    let timer = null;

    const visit = () => {
        const query = {};

        Object.entries(filters).forEach(([key, state]) => {
            query[key] = state.value || undefined;
        });

        query.sort = sort.value || undefined;
        query.direction = sort.value ? direction.value : undefined;
        query.per_page = new URLSearchParams(window.location.search).get('per_page') || undefined;

        router.get(url, query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    watch(Object.values(filters), () => {
        clearTimeout(timer);
        timer = setTimeout(visit, 300);
    });

    const toggleSort = (column) => {
        if (sort.value === column) {
            direction.value = direction.value === 'asc' ? 'desc' : 'asc';
        } else {
            sort.value = column;
            direction.value = 'asc';
        }

        visit();
    };

    onUnmounted(() => clearTimeout(timer));

    return { sort, direction, toggleSort };
}
