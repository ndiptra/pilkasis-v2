<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import logoUrl from '../../../images/logo.png';

defineProps({
    name: { type: String, required: true },
    school: { type: Object, default: null },
});

const DURATION_MS = 5000;
const progress = ref(100);
let timer = null;

onMounted(() => {
    document.body.className = 'hold-transition login-page';
    document.body.removeAttribute('style');

    requestAnimationFrame(() => requestAnimationFrame(() => {
        progress.value = 0;
    }));

    timer = setTimeout(() => {
        window.location.href = '/voter/login';
    }, DURATION_MS);
});

onBeforeUnmount(() => clearTimeout(timer));
</script>

<template>
    <Head title="Terima Kasih" />

    <div class="login-box login-box-pilkasis">
        <div class="card card-outline card-success login-card">
            <div class="card-header text-center login-card-header">
                <img :src="logoUrl" class="login-brand" alt="PILKASIS">
            </div>
            <div class="card-body text-center py-4">
                <div class="thanks-icon"><i class="fas fa-check-circle"></i></div>
                <h4 class="font-weight-bold mt-3">Terima Kasih, {{ name }}!</h4>
                <p class="text-muted mb-4">Suaramu sudah tersimpan. Terima kasih telah menggunakan hak pilihmu.</p>
                <div class="progress thanks-progress">
                    <div class="progress-bar bg-success thanks-progress-bar" :style="{ width: progress + '%' }"></div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
.thanks-icon {
    font-size: 4.5rem;
    color: #28a745;
    line-height: 1;
}

.thanks-progress {
    height: 6px;
    border-radius: 3px;
}

.thanks-progress-bar {
    transition: width 5s linear;
}
</style>
