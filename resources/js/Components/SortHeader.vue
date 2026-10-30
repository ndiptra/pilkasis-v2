<script setup>
defineProps({
    label: {
        type: String,
        required: true,
    },
    column: {
        type: String,
        required: true,
    },
    sort: {
        type: String,
        default: '',
    },
    direction: {
        type: String,
        default: 'asc',
    },
});

defineEmits(['sort']);
</script>

<template>
    <th class="sortable" @click="$emit('sort', column)">
        {{ label }}
        <i
            class="fas sort-icon"
            :class="sort === column ? (direction === 'asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort text-muted'"
        ></i>
    </th>
</template>
