<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import NoElectionAlert from '../../Components/NoElectionAlert.vue';

const props = defineProps({
    setting: {
        type: Object,
        default: null,
    },
    election: {
        type: Object,
        default: null,
    },
});

const formatDate = (value) => {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'full',
        timeStyle: 'short',
    }).format(new Date(value));
};

const statusOptions = [
    { value: 'not_started', label: 'Belum Mulai', icon: 'fa-hourglass-start', color: 'secondary' },
    { value: 'ongoing', label: 'Berlangsung', icon: 'fa-play-circle', color: 'success' },
    { value: 'paused', label: 'Jeda', icon: 'fa-pause-circle', color: 'warning' },
    { value: 'closed', label: 'Ditutup', icon: 'fa-door-closed', color: 'danger' },
    { value: 'final', label: 'Final', icon: 'fa-lock', color: 'dark' },
];

const isLocked = computed(() => props.setting?.status === 'final');

const form = useForm({
    status: props.setting?.status ?? 'not_started',
    voting_enabled: props.setting?.voting_enabled ?? false,
    results_public: props.setting?.results_public ?? false,
    announcement: props.setting?.announcement ?? '',
});

const save = () => form.put('/settings', { preserveScroll: true });

const submit = () => {
    if (form.status !== 'final' || isLocked.value || !window.Swal) {
        save();

        return;
    }

    window.Swal.fire({
        title: 'Finalkan pemilihan?',
        text: 'Setelah final, pengaturan, data pemilih, dan data paslon dikunci sampai status diubah kembali.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, finalkan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#343a40',
    }).then((result) => {
        if (result.isConfirmed) {
            save();
        }
    });
};</script>

<template>
    <Head title="Pengaturan" />

    <NoElectionAlert v-if="!election" />

    <form v-else @submit.prevent="submit">
        <div class="row">
            <div class="col-lg-8">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Pengaturan Pemilihan</h3>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-4">Nama Pemilihan</dt>
                            <dd class="col-sm-8">{{ election.name }}</dd>

                            <dt class="col-sm-4">Tahun</dt>
                            <dd class="col-sm-8">{{ election.year }}</dd>

                            <dt class="col-sm-4">Mulai</dt>
                            <dd class="col-sm-8">{{ formatDate(election.starts_at) }}</dd>

                            <dt class="col-sm-4">Selesai</dt>
                            <dd class="col-sm-8">{{ formatDate(election.ends_at) }}</dd>
                        </dl>

                        <hr>

                        <div class="form-group">
                            <label>Status Pemilihan <span class="text-danger">*</span></label>
                            <div class="btn-group btn-group-toggle status-toggle d-flex flex-wrap">
                                <label
                                    v-for="option in statusOptions"
                                    :key="option.value"
                                    class="btn"
                                    :class="form.status === option.value ? `btn-${option.color} active` : `btn-outline-${option.color}`"
                                >
                                    <input v-model="form.status" type="radio" name="status" :value="option.value">                                    <i class="fas mr-1" :class="option.icon"></i> {{ option.label }}
                                </label>
                            </div>
                            <div v-if="isLocked" class="text-dark small mt-1">
                                <i class="fas fa-lock mr-1"></i> Pemilihan sudah final. Pengaturan, data pemilih, dan data paslon dikunci sampai status diubah.
                            </div>
                            <div v-if="form.errors.status" class="invalid-feedback">{{ form.errors.status }}</div>
                        </div>

                        <div class="form-group mb-0">
                            <label>Pengumuman</label>
                            <textarea v-model="form.announcement" rows="4" :disabled="isLocked" class="form-control" placeholder="Informasi tambahan untuk pemilih..." :class="{ 'is-invalid': form.errors.announcement }"></textarea>
                            <div v-if="form.errors.announcement" class="invalid-feedback">{{ form.errors.announcement }}</div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" :disabled="form.processing">
                            <span v-if="form.processing" class="spinner-border spinner-border-sm mr-1"></span>
                            Simpan Pengaturan
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Akses Voting</h3>
                    </div>
                    <div class="card-body">
                        <div class="custom-control custom-switch mb-3">
                            <input id="voting-enabled" v-model="form.voting_enabled" type="checkbox" class="custom-control-input" :disabled="isLocked">
                            <label class="custom-control-label" for="voting-enabled">Aktifkan voting</label>
                        </div>
                        <p class="text-muted small mb-0">
                            Ketika diaktifkan, pemilih dapat memberikan suara melalui halaman voting.
                        </p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Publikasi Hasil</h3>
                    </div>
                    <div class="card-body">
                        <div class="custom-control custom-switch mb-3">
                            <input id="results-public" v-model="form.results_public" type="checkbox" class="custom-control-input">
                            <label class="custom-control-label" for="results-public">Tampilkan hasil di halaman publik</label>
                        </div>
                        <p class="text-muted small mb-0">
                            Jika aktif, perolehan suara tampil di beranda untuk umum. Aktifkan setelah pemilihan selesai.
                        </p>
                    </div>
                </div>

                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title">Catatan</h3>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted mb-0">
                            Pengaturan ini berlaku khusus untuk pemilihan yang sedang aktif. Ganti pemilihan aktif melalui menu Data Pemilihan.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</template>
