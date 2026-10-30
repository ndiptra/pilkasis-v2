<script setup>
import { onUnmounted, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import Pagination from '../../Components/Pagination.vue';
import PerPageSelect from '../../Components/PerPageSelect.vue';
import SortHeader from '../../Components/SortHeader.vue';
import { useTableQuery } from '../../Composables/useTableQuery';

const props = defineProps({
    elections: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');

const { sort, direction, toggleSort } = useTableQuery('/elections', { search, status }, props.filters);

const showModal = ref(false);
const editing = ref(null);

const toLocalInput = (value) => {
    if (!value) {
        return '';
    }

    const date = new Date(value);
    const pad = (number) => String(number).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
};

const formatDate = (value) => {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
};

const form = useForm({
    name: '',
    year: '',
    starts_at: '',
    ends_at: '',
});

const openCreate = () => {
    editing.value = null;
    form.defaults({
        name: '',
        year: String(new Date().getFullYear()),
        starts_at: '',
        ends_at: '',
    });
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (election) => {
    editing.value = election;
    form.defaults({
        name: election.name,
        year: election.year,
        starts_at: toLocalInput(election.starts_at),
        ends_at: toLocalInput(election.ends_at),
    });
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editing.value = null;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    };

    if (editing.value) {
        form.put(`/elections/${editing.value.id}`, options);
    } else {
        form.post('/elections', options);
    }
};

const confirmAction = ({ title, message, confirmText, confirmColor, icon = 'warning' }, onConfirm) => {
    if (!window.Swal) {
        if (window.confirm(message)) {
            onConfirm();
        }

        return;
    }

    window.Swal.fire({
        title,
        text: message,
        icon,
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: 'Batal',
        confirmButtonColor: confirmColor,
    }).then((result) => {
        if (result.isConfirmed) {
            onConfirm();
        }
    });
};

const activate = (election) => {
    confirmAction({
        title: 'Aktifkan pemilihan?',
        message: `Pemilihan ${election.name} akan diaktifkan dan pemilihan lain yang sedang aktif akan dinonaktifkan.`,
        confirmText: 'Ya, aktifkan',
        confirmColor: '#28a745',
        icon: 'question',
    }, () => router.post(`/elections/${election.id}/activate`, {}, { preserveScroll: true }));
};

const deactivate = (election) => {
    confirmAction({
        title: 'Nonaktifkan pemilihan?',
        message: `Pemilihan ${election.name} akan dinonaktifkan.`,
        confirmText: 'Ya, nonaktifkan',
        confirmColor: '#6c757d',
    }, () => router.post(`/elections/${election.id}/deactivate`, {}, { preserveScroll: true }));
};

const confirmDelete = (election) => {
    confirmAction({
        title: 'Hapus data pemilihan?',
        message: `Data pemilihan ${election.name} beserta seluruh pemilih, paslon, pengaturan, dan hasil terkait akan dihapus permanen.`,
        confirmText: 'Ya, hapus',
        confirmColor: '#dc3545',
    }, () => router.delete(`/elections/${election.id}`, { preserveScroll: true }));
};
</script>

<template>
    <Head title="Data Pemilihan" />

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daftar Pemilihan</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-primary btn-sm" @click="openCreate">
                    <i class="fas fa-plus mr-1"></i> Tambah Pemilihan
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3 mt-2 mt-md-0">
                    <select v-model="status" class="form-control">
                        <option value="">Semua status</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Tidak aktif</option>
                    </select>
                </div>
            </div>

            <PerPageSelect>
                <input v-model="search" type="search" class="form-control form-control-sm search-input" placeholder="Cari nama atau tahun pemilihan...">
            </PerPageSelect>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <SortHeader label="Nama Pemilihan" column="name" :sort="sort" :direction="direction" @sort="toggleSort" />
                            <SortHeader style="width: 90px;" label="Tahun" column="year" :sort="sort" :direction="direction" @sort="toggleSort" />
                            <SortHeader label="Mulai" column="starts_at" :sort="sort" :direction="direction" @sort="toggleSort" />
                            <SortHeader label="Selesai" column="ends_at" :sort="sort" :direction="direction" @sort="toggleSort" />
                            <SortHeader style="width: 130px;" label="Data" column="voters_count" :sort="sort" :direction="direction" @sort="toggleSort" />
                            <SortHeader style="width: 110px;" label="Status" column="is_active" :sort="sort" :direction="direction" @sort="toggleSort" />
                            <th class="action-column">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="elections.data.length === 0">
                            <td colspan="8" class="text-center text-muted py-4">
                                Data pemilihan tidak ditemukan.
                            </td>
                        </tr>
                        <tr v-for="(election, index) in elections.data" :key="election.id">
                            <td class="text-center">{{ (elections.from ?? 1) + index }}</td>
                            <td>
                                <strong>{{ election.name }}</strong>
                            </td>
                            <td>{{ election.year }}</td>
                            <td>{{ formatDate(election.starts_at) }}</td>
                            <td>{{ formatDate(election.ends_at) }}</td>
                            <td>
                                <span class="badge badge-info" :title="`${election.voters_count} pemilih`">
                                    <i class="fas fa-users mr-1"></i>{{ election.voters_count }}
                                </span>
                                <span class="badge badge-primary ml-1" :title="`${election.candidates_count} paslon`">
                                    <i class="fas fa-user-tie mr-1"></i>{{ election.candidates_count }}
                                </span>
                            </td>
                            <td>
                                <span class="badge" :class="election.is_active ? 'badge-success' : 'badge-secondary'">
                                    {{ election.is_active ? 'Aktif' : 'Tidak aktif' }}
                                </span>
                            </td>
                            <td class="table-actions text-center">
                                <button
                                    v-if="election.is_active"
                                    type="button"
                                    class="btn btn-sm btn-secondary"
                                    title="Nonaktifkan"
                                    @click="deactivate(election)"
                                >
                                    <i class="fas fa-power-off"></i>
                                </button>
                                <button
                                    v-else
                                    type="button"
                                    class="btn btn-sm btn-success"
                                    title="Aktifkan"
                                    @click="activate(election)"
                                >
                                    <i class="fas fa-check"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-warning ml-1" title="Ubah" @click="openEdit(election)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-danger ml-1"
                                    :disabled="election.is_active"
                                    :title="election.is_active ? 'Pemilihan aktif tidak dapat dihapus' : 'Hapus'"
                                    @click="confirmDelete(election)"
                                >
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :paginator="elections" />
        </div>
    </div>

    <template v-if="showModal">
        <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <form @submit.prevent="submit">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                {{ editing ? 'Ubah Data Pemilihan' : 'Tambah Data Pemilihan' }}
                            </h5>
                            <button type="button" class="close" @click="closeModal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label>Nama Pemilihan <span class="text-danger">*</span></label>
                                        <input
                                            v-model="form.name"
                                            type="text"
                                            class="form-control"
                                            placeholder="Contoh: Pemilihan Ketua OSIS"
                                            :class="{ 'is-invalid': form.errors.name }"
                                        >
                                        <div v-if="form.errors.name" class="invalid-feedback">{{ form.errors.name }}</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Tahun <span class="text-danger">*</span></label>
                                        <input
                                            v-model="form.year"
                                            type="text"
                                            inputmode="numeric"
                                            maxlength="4"
                                            class="form-control"
                                            placeholder="2026"
                                            :class="{ 'is-invalid': form.errors.year }"
                                            @input="form.year = form.year.replace(/\D/g, '').slice(0, 4)"
                                        >
                                        <div v-if="form.errors.year" class="invalid-feedback">{{ form.errors.year }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Tanggal &amp; Jam Mulai <span class="text-danger">*</span></label>
                                        <input
                                            v-model="form.starts_at"
                                            type="datetime-local"
                                            class="form-control"
                                            :class="{ 'is-invalid': form.errors.starts_at }"
                                        >
                                        <div v-if="form.errors.starts_at" class="invalid-feedback">{{ form.errors.starts_at }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Tanggal &amp; Jam Selesai <span class="text-danger">*</span></label>
                                        <input
                                            v-model="form.ends_at"
                                            type="datetime-local"
                                            class="form-control"
                                            :class="{ 'is-invalid': form.errors.ends_at }"
                                        >
                                        <div v-if="form.errors.ends_at" class="invalid-feedback">{{ form.errors.ends_at }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer justify-content-between">
                            <button type="button" class="btn btn-default" @click="closeModal">Batal</button>
                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                <span v-if="form.processing" class="spinner-border spinner-border-sm mr-1"></span>
                                Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    </template>
</template>
