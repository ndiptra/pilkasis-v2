<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const isSuperadmin = computed(() => usePage().props.auth?.user?.role === 'superadmin');
</script>

<template>
    <div class="alert alert-warning no-election-alert" role="alert">
        <div class="no-election-alert-body">
            <span class="no-election-alert-icon"><i class="fas fa-exclamation-triangle"></i></span>
            <div>
                <h5 class="mb-1">Belum ada pemilihan aktif</h5>
                <p v-if="isSuperadmin" class="mb-0">Aktifkan atau buat pemilihan terlebih dahulu pada menu Data Pemilihan.</p>
                <p v-else class="mb-0">Hubungi superadmin untuk mengaktifkan pemilihan.</p>
            </div>
        </div>
        <Link v-if="isSuperadmin" href="/elections" class="btn btn-dark btn-sm no-election-alert-action">
            Kelola Pemilihan <i class="fas fa-arrow-right ml-1"></i>
        </Link>
    </div>
</template>
