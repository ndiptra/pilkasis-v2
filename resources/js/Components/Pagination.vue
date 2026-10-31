<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    paginator: {
        type: Object,
        required: true,
    },
});

const pageLabel = (link, index) => {
    if (index === 0) {
        return '<i class="fas fa-chevron-left"></i>';
    }

    if (index === props.paginator.links.length - 1) {
        return '<i class="fas fa-chevron-right"></i>';
    }

    return link.label;
};
</script>

<template>
    <div class="d-flex flex-wrap justify-content-between align-items-center mt-2">
        <small class="text-muted mb-2 mb-sm-0">
            Menampilkan {{ paginator.from ?? 0 }} - {{ paginator.to ?? 0 }} dari {{ paginator.total }} data
        </small>

        <ul v-if="paginator.links.length > 3" class="pagination pagination-sm m-0">
            <li
                v-for="(link, index) in paginator.links"
                :key="index"
                class="page-item"
                :class="{ active: link.active, disabled: !link.url }"
            >
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="page-link"
                    preserve-scroll
                    v-html="pageLabel(link, index)"
                />
                <span v-else class="page-link" v-html="pageLabel(link, index)" />
            </li>
        </ul>
    </div>
</template>
