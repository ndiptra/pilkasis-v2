import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useAccess() {
    const page = usePage();

    return {
        isSuperadmin: computed(() => page.props.auth?.user?.role === 'superadmin'),
        canModifyData: computed(() => page.props.access?.canModifyData ?? false),
        canViewResults: computed(() => page.props.access?.canViewResults ?? false),
    };
}
