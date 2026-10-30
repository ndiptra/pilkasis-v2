<script setup>
import { computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import logoUrl from '../../images/logo.png';

const props = defineProps({
    school: { type: Object, default: null },
    election: { type: Object, required: true },
    status: { type: String, default: null },
    summary: { type: Object, required: true },
    results: { type: Array, default: () => [] },
});

const statusLabels = {
    not_started: 'Belum Dimulai',
    ongoing: 'Sedang Berlangsung',
    paused: 'Dijeda',
    closed: 'Ditutup',
    final: 'Final',
};

const topId = computed(() => {
    if (!props.results.length || props.summary.total_votes === 0) {
        return null;
    }

    return [...props.results].sort((a, b) => b.votes - a.votes)[0].id;
});

onMounted(() => {
    document.body.className = 'hold-transition layout-top-nav';
});
</script>

<template>
    <Head title="Hasil Pemilihan" />

    <div class="wrapper">
        <nav class="main-header navbar navbar-expand-md navbar-white navbar-light">
            <div class="container">
                <a href="/" class="navbar-brand">
                    <img :src="logoUrl" alt="PILKASIS" class="public-brand-logo">
                </a>
                <span class="navbar-text ml-auto text-muted d-none d-sm-inline">Hasil Pemilihan</span>
            </div>
        </nav>

        <div class="content-wrapper">
            <div class="content-header">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="m-0 text-dark">{{ election.name }}</h1>
                            <p class="text-muted mb-0">
                                <span v-if="school?.name">{{ school.name }} &middot; </span>Periode {{ election.year }}
                            </p>
                        </div>
                        <div class="col-md-4 text-md-right mt-2 mt-md-0">
                            <span class="badge badge-dark px-3 py-2">{{ statusLabels[status] ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content">
                <div class="container">
                    <div class="row">
                        <div class="col-md-4 col-12">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary"><i class="fas fa-vote-yea"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Suara</span>
                                    <span class="info-box-number">{{ summary.total_votes }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-12">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i class="fas fa-users"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Pemilih</span>
                                    <span class="info-box-number">{{ summary.total_voters }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-12">
                            <div class="info-box">
                                <span class="info-box-icon bg-success"><i class="fas fa-percentage"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Partisipasi</span>
                                    <span class="info-box-number">{{ summary.participation }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Perolehan Suara</h3>
                        </div>
                        <div class="card-body">
                            <div v-if="results.length === 0" class="text-center text-muted py-4">
                                Belum ada pasangan calon.
                            </div>

                            <div v-for="item in results" :key="item.id" class="public-result">
                                <div class="public-result-photo">
                                    <img v-if="item.photo_path" :src="`/storage/${item.photo_path}`" :alt="item.chairman_name">
                                    <i v-else class="fas fa-user-tie"></i>
                                </div>
                                <div class="public-result-body">
                                    <div class="d-flex justify-content-between align-items-baseline">
                                        <div>
                                            <strong>Paslon {{ item.order_number }}</strong>
                                            <span class="text-muted">
                                                &middot; {{ item.chairman_name }}<span v-if="item.vice_chairman_name"> &amp; {{ item.vice_chairman_name }}</span>
                                            </span>
                                            <span v-if="item.id === topId" class="badge badge-success ml-1">Unggul</span>
                                        </div>
                                        <div class="text-right text-nowrap pl-2">
                                            <strong>{{ item.votes }}</strong> suara
                                            <span class="text-muted">({{ item.percentage }}%)</span>
                                        </div>
                                    </div>
                                    <div class="progress mt-2">
                                        <div
                                            class="progress-bar"
                                            :class="item.id === topId ? 'bg-success' : 'bg-primary'"
                                            :style="{ width: `${item.percentage}%` }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <footer class="main-footer">
            <div class="container">
                <strong>&copy; {{ new Date().getFullYear() }} PILKASIS</strong>
                <span class="text-muted"> &middot; Aplikasi Pemilihan Ketua OSIS</span>
            </div>
        </footer>
    </div>
</template>

<style>
.public-brand-logo {
    height: 34px;
    width: auto;
}

.public-result {
    display: flex;
    gap: 1rem;
    align-items: center;
    padding: .9rem 0;
    border-bottom: 1px solid #edf0f4;
}

.public-result:last-child {
    border-bottom: 0;
}

.public-result-photo {
    flex: none;
    width: 56px;
    height: 56px;
    border-radius: 8px;
    overflow: hidden;
    display: grid;
    place-items: center;
    background: #eef2f7;
    color: #94a3b8;
}

.public-result-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.public-result-body {
    flex: 1;
    min-width: 0;
}
</style>
