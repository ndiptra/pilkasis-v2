<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const options = [
    { value: '10', label: '10' },
    { value: '50', label: '50' },
    { value: '100', label: '100' },
    { value: 'all', label: 'Semua' },
];

const requested = new URLSearchParams(window.location.search).get('per_page');
const current = ref(options.some((option) => option.value === requested) ? requested : '10');

const change = () => {
    const params = Object.fromEntries(new URLSearchParams(window.location.search));

    delete params.page;
    params.per_page = current.value;

    router.get(window.location.pathname, params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};
</script>

<template>
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-2">
        <div class="d-flex align-items-center mr-3">
        <small class="text-muted mr-2">Tampilkan</small>
        <select v-model="current" class="custom-select custom-select-sm per-page-select" @change="change">
            <option v-for="option in options" :key="option.value" :value="option.value">
                {{ option.label }}
            </option>
        </select>
        <small class="text-muted ml-2">data</small>
        </div>
        <slot />
    </div>
</template>
