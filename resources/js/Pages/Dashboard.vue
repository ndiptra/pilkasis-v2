<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import NoElectionAlert from '../Components/NoElectionAlert.vue';
import { useAccess } from '../Composables/useAccess';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    setting: {
        type: Object,
        default: null,
    },
    election: {
        type: Object,
        default: null,
    },
    school: {
        type: Object,
        default: null,
    },
    candidates: {
        type: Array,
        default: () => [],
    },
    recentVoters: {
        type: Array,
        default: () => [],
    },
});

const { isSuperadmin, canViewResults, canModifyData } = useAccess();

const statusLabel = computed(() => {
    const labels = {
        not_started: 'Belum Mulai',
        ongoing: 'Berlangsung',
        paused: 'Jeda',
        closed: 'Ditutup',
        final: 'Final',
    };

    return labels[props.setting?.status] ?? 'Belum diatur';
});

const statusClass = computed(() => {
    const classes = {
        not_started: 'badge-secondary',
        ongoing: 'badge-success',
        paused: 'badge-warning',
        closed: 'badge-danger',
        final: 'badge-dark',
    };

    return classes[props.setting?.status] ?? 'badge-secondary';
});

const allStatBoxes = computed(() => [
    { label: 'Total Pemilih', value: props.stats.total_voters, color: 'bg-info', icon: 'fa-users', href: '/voters', footer: 'Kelola data' },
    { label: 'Pasangan Calon', value: props.stats.total_candidates, color: 'bg-success', icon: 'fa-user-tie', href: '/candidates', footer: 'Kelola data' },
    { label: 'Suara Masuk', value: props.stats.total_votes, color: 'bg-warning', icon: 'fa-vote-yea', href: '/results', footer: 'Lihat hasil' },
    { label: 'Partisipasi', value: `${props.stats.participation}%`, color: 'bg-danger', icon: 'fa-chart-pie', href: '/results', footer: 'Lihat hasil' },
]);

const restrictedStatBoxes = computed(() => [
    allStatBoxes.value[0],
    allStatBoxes.value[1],
    { label: 'Status Pemilihan', value: statusLabel.value, color: 'bg-warning', icon: 'fa-flag', href: null, footer: 'Hasil tampil saat Final' },
    {
        label: 'Akses Data',
        value: canModifyData.value ? 'Terbuka' : 'Terkunci',
        color: canModifyData.value ? 'bg-primary' : 'bg-secondary',
        icon: canModifyData.value ? 'fa-lock-open' : 'fa-lock',
        href: null,
        footer: canModifyData.value ? 'Data dapat diubah' : 'Data tidak dapat diubah',
    },
]);

const statBoxes = computed(() => (canViewResults.value ? allStatBoxes.value : restrictedStatBoxes.value));

const formatDate = (value) => {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
};

const votePercentage = (candidate) => {
    if (!props.stats.total_votes) {
        return 0;
    }

    return Math.round((candidate.votes_count / props.stats.total_votes) * 100);
};
</script>

<template>
    <Head title="Dasbor" />

    <NoElectionAlert v-if="!election" />

    <div class="row">
        <div v-for="box in statBoxes" :key="box.label" class="col-lg-3 col-6">
            <div class="small-box" :class="box.color">
                <div class="inner">
                    <h3>{{ box.value }}</h3>
                    <p>{{ box.label }}</p>
                </div>
                <div class="icon">
                    <i class="fas" :class="box.icon"></i>
                </div>
                <Link v-if="box.href" :href="box.href" class="small-box-footer">
                    {{ box.footer }} <i class="fas fa-arrow-circle-right"></i>
                </Link>
                <span v-else class="small-box-footer small-box-note">{{ box.footer }}</span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div v-if="canViewResults" class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Perolehan Suara Sementara</h3>
                    <div class="card-tools">
                        <Link v-if="canViewResults" href="/results" class="btn btn-tool">Lihat hasil lengkap</Link>
                    </div>
                </div>
                <div class="card-body">
                    <div v-if="candidates.length === 0" class="text-center text-muted py-4">
                        Belum ada pasangan calon. Tambahkan data paslon terlebih dahulu.
                    </div>
                    <div
                        v-for="candidate in candidates"
                        :key="candidate.id"
                        class="d-flex align-items-center dashboard-candidate"
                    >
                        <div class="dashboard-candidate-photo">
                            <img v-if="candidate.photo_path" :src="`/storage/${candidate.photo_path}`" :alt="candidate.chairman_name">
                            <span v-else class="text-muted">{{ candidate.order_number }}</span>
                        </div>
                        <div class="flex-grow-1 min-width-0">
                            <div class="d-flex justify-content-between flex-wrap">
                                <strong>
                                    <span class="badge badge-primary mr-1">{{ candidate.order_number }}</span>
                                    {{ candidate.chairman_name }} &amp; {{ candidate.vice_chairman_name }}
                                </strong>
                                <span class="text-muted">
                                    {{ candidate.votes_count }} suara
                                    <strong class="text-dark">({{ votePercentage(candidate) }}%)</strong>
                                </span>
                            </div>
                            <div class="progress mt-2">
                                <div
                                    class="progress-bar bg-primary"
                                    role="progressbar"
                                    :style="{ width: `${votePercentage(candidate)}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Pasangan Calon</h3>
                    <div class="card-tools">
                        <Link href="/candidates" class="btn btn-tool">Kelola paslon</Link>
                    </div>
                </div>
                <div class="card-body">
                    <div v-if="candidates.length === 0" class="text-center text-muted py-4">
                        Belum ada pasangan calon. Tambahkan data paslon terlebih dahulu.
                    </div>
                    <div
                        v-for="candidate in candidates"
                        :key="candidate.id"
                        class="d-flex align-items-center dashboard-candidate"
                    >
                        <div class="dashboard-candidate-photo">
                            <img v-if="candidate.photo_path" :src="`/storage/${candidate.photo_path}`" :alt="candidate.chairman_name">
                            <span v-else class="text-muted">{{ candidate.order_number }}</span>
                        </div>
                        <div class="min-width-0">
                            <span class="badge badge-primary mb-1">Paslon {{ candidate.order_number }}</span>
                            <div><strong>{{ candidate.chairman_name }}</strong></div>
                            <div class="text-muted">{{ candidate.vice_chairman_name }}</div>
                        </div>
                    </div>
                    <div class="callout callout-info mt-3 mb-0">
                        <p class="mb-0">Perolehan suara baru dapat dilihat setelah status pemilihan <strong>Final</strong>.</p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Pemilih Terbaru</h3>
                    <div class="card-tools">
                        <Link href="/voters" class="btn btn-tool">Lihat semua</Link>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>NISN</th>
                                <th>Kelas</th>
                                <th v-if="canViewResults" class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="recentVoters.length === 0">
                                <td :colspan="canViewResults ? 4 : 3" class="text-center text-muted py-4">Belum ada data pemilih.</td>
                            </tr>
                            <tr v-for="voter in recentVoters" :key="voter.id">
                                <td>{{ voter.name }}</td>
                                <td>{{ voter.nisn }}</td>
                                <td>{{ voter.grade }}</td>
                                <td v-if="canViewResults" class="text-center">
                                    <span class="badge" :class="voter.has_voted ? 'badge-success' : 'badge-secondary'">
                                        {{ voter.has_voted ? 'Sudah memilih' : 'Belum memilih' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Informasi Pemilihan</h3>
                </div>
                <div class="card-body">
                    <strong>{{ school?.name || 'Identitas sekolah belum diatur' }}</strong>
                    <p class="text-muted mb-3">{{ school?.address || 'Lengkapi pada menu Profil Sekolah.' }}</p>
                    <hr class="mt-0">

                    <dl class="mb-0 dashboard-info">
                        <dt>Pemilihan</dt>
                        <dd>{{ election?.name || 'Belum diatur' }}<span v-if="election?.year" class="text-muted"> ({{ election.year }})</span></dd>

                        <dt>Status</dt>
                        <dd><span class="badge" :class="statusClass">{{ statusLabel }}</span></dd>

                        <dt>Voting</dt>
                        <dd>
                            <span class="badge" :class="setting?.voting_enabled ? 'badge-success' : 'badge-secondary'">
                                {{ setting?.voting_enabled ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </dd>

                        <dt>Mulai</dt>
                        <dd>{{ formatDate(election?.starts_at) }}</dd>

                        <dt>Selesai</dt>
                        <dd class="mb-0">{{ formatDate(election?.ends_at) }}</dd>
                    </dl>
                </div>
                <div v-if="isSuperadmin" class="card-footer">
                    <Link href="/settings" class="btn btn-primary btn-block">Ubah Pengaturan</Link>
                </div>
            </div>

            <div v-if="canViewResults" class="card">
                <div class="card-header">
                    <h3 class="card-title">Partisipasi Pemilih</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Sudah memilih</span>
                        <strong>{{ stats.total_votes }} / {{ stats.total_voters }}</strong>
                    </div>
                    <div class="progress mb-3">
                        <div class="progress-bar bg-success" :style="{ width: `${stats.participation}%` }"></div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Belum memilih</span>
                        <span class="text-muted">{{ Math.max(stats.total_voters - stats.total_votes, 0) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
