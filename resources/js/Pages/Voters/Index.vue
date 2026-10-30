<script setup>
import { computed, onUnmounted, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import Pagination from '../../Components/Pagination.vue';
import PerPageSelect from '../../Components/PerPageSelect.vue';
import Select2 from '../../Components/Select2.vue';
import SortHeader from '../../Components/SortHeader.vue';
import { useTableQuery } from '../../Composables/useTableQuery';
import NoElectionAlert from '../../Components/NoElectionAlert.vue';
import DataLockedAlert from '../../Components/DataLockedAlert.vue';
import { useAccess } from '../../Composables/useAccess';

const { canModifyData } = useAccess();

const props = defineProps({
    voters: {
        type: Object,
        required: true,
    },
    elections: {
        type: Array,
        default: () => [],
    },
    election: {
        type: Object,
        default: null,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const search = ref(props.filters.search ?? '');
const electionId = ref(props.filters.election ?? '');
const status = ref(props.filters.status ?? '');
const showModal = ref(false);
const editing = ref(null);

const electionOptions = computed(() => props.elections.map((item) => ({
    value: item.id,
    label: `${item.name} (${item.year})`,
})));

const form = useForm({
    election_id: '',
    nisn: '',
    name: '',
    grade: '',
    is_active: true,
});

const importForm = useForm({
    election_id: '',
    file: null,
});
const showImport = ref(false);
const importInput = ref(null);

const openImport = () => {
    importForm.reset();
    importForm.clearErrors();
    importForm.election_id = props.election.id;
    showImport.value = true;
};

const closeImport = () => {
    showImport.value = false;
    importForm.reset();
    importForm.clearErrors();
};

const submitImport = () => {
    importForm.post('/voters/import', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => closeImport(),
    });
};

const exportUrl = computed(() => `/voters/export${props.election ? `?election=${props.election.id}` : ''}`);

const { sort, direction, toggleSort } = useTableQuery(
    '/voters',
    { search, election: electionId, status },
    props.filters,
);

const openCreate = () => {
    editing.value = null;
    form.defaults({
        election_id: props.election.id,
        nisn: '',
        name: '',
        grade: '',
        is_active: true,
    });
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (voter) => {
    editing.value = voter;
    form.defaults({
        election_id: voter.election_id,
        nisn: voter.nisn,
        name: voter.name,
        grade: voter.grade,
        is_active: voter.is_active,
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
        form.put(`/voters/${editing.value.id}`, options);
    } else {
        form.post('/voters', options);
    }
};

const confirmDelete = (voter) => {
    if (!window.Swal) {
        if (window.confirm(`Hapus data pemilih ${voter.name}?`)) {
            router.delete(`/voters/${voter.id}`, { preserveScroll: true });
        }

        return;
    }

    window.Swal.fire({
        title: 'Hapus data pemilih?',
        text: `Data ${voter.name} akan dihapus permanen.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#dc3545',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/voters/${voter.id}`, { preserveScroll: true });
        }
    });
};
</script>

<template>
    <Head title="Data Pemilih" />

    <NoElectionAlert v-if="!election" />

    <template v-else>
        <DataLockedAlert />

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Daftar Pemilih</h3>
                <div class="card-tools">
                    <a v-if="canModifyData" href="/voters/template" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-file-download mr-1"></i> Template
                    </a>
                    <button v-if="election && canModifyData" type="button" class="btn btn-success btn-sm" @click="openImport">
                        <i class="fas fa-file-import mr-1"></i> Impor
                    </button>
                    <a v-if="election" :href="exportUrl" class="btn btn-info btn-sm">
                        <i class="fas fa-file-export mr-1"></i> Ekspor
                    </a>
                    <button v-if="election && canModifyData" type="button" class="btn btn-primary btn-sm" @click="openCreate">
                        <i class="fas fa-plus mr-1"></i> Tambah Pemilih
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4 mt-2 mt-md-0">
                        <Select2 v-model="electionId" :options="electionOptions" placeholder="Pilih periode pemilihan" />
                    </div>
                    <div class="col-md-3 mt-2 mt-md-0">
                        <select v-model="status" class="form-control">
                            <option value="">Semua status</option>
                            <option value="active">Aktif</option>
                            <option value="inactive">Tidak aktif</option>
                            <option value="voted">Sudah memilih</option>
                            <option value="not_voted">Belum memilih</option>
                        </select>
                    </div>
                </div>

                <PerPageSelect>
                    <input v-model="search" type="search" class="form-control form-control-sm search-input" placeholder="Cari nama, NISN, atau kelas...">
                </PerPageSelect>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <SortHeader label="NISN" column="nisn" :sort="sort" :direction="direction" @sort="toggleSort" />
                                <SortHeader label="Nama" column="name" :sort="sort" :direction="direction" @sort="toggleSort" />
                                <SortHeader label="Kelas" column="grade" :sort="sort" :direction="direction" @sort="toggleSort" />
                                <SortHeader label="Kata Sandi" column="password" :sort="sort" :direction="direction" @sort="toggleSort" />
                                <SortHeader label="Status" column="is_active" :sort="sort" :direction="direction" @sort="toggleSort" />
                                <SortHeader label="Memilih" column="has_voted" :sort="sort" :direction="direction" @sort="toggleSort" />
                                <th v-if="canModifyData" class="action-column">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="voters.data.length === 0">
                                <td :colspan="canModifyData ? 8 : 7" class="text-center text-muted py-4">
                                    Belum ada data pemilih yang sesuai.
                                </td>
                            </tr>
                            <tr v-for="(voter, index) in voters.data" :key="voter.id">
                                <td class="text-center">{{ voters.from + index }}</td>
                                <td>{{ voter.nisn }}</td>
                                <td>{{ voter.name }}</td>
                                <td>{{ voter.grade }}</td>
                                <td><code>{{ voter.password }}</code></td>
                                <td>
                                    <span class="badge" :class="voter.is_active ? 'badge-success' : 'badge-secondary'">
                                        {{ voter.is_active ? 'Aktif' : 'Tidak aktif' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge" :class="voter.has_voted ? 'badge-primary' : 'badge-light'">
                                        {{ voter.has_voted ? 'Sudah' : 'Belum' }}
                                    </span>
                                </td>
                                <td v-if="canModifyData" class="table-actions text-center">
                                    <button type="button" class="btn btn-sm btn-warning" title="Ubah" @click="openEdit(voter)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger ml-1" title="Hapus" @click="confirmDelete(voter)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <Pagination :paginator="voters" />
            </div>
        </div>

        <template v-if="showModal">
            <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <form @submit.prevent="submit">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    {{ editing ? 'Ubah Data Pemilih' : 'Tambah Data Pemilih' }}
                                </h5>
                                <button type="button" class="close" @click="closeModal">
                                    <span>&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label>NISN / Kode <span class="text-danger">*</span></label>
                                    <input
                                        v-model="form.nisn"
                                        type="text"
                                        inputmode="numeric"
                                        maxlength="10"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.nisn }"
                                        @input="form.nisn = form.nisn.replace(/\D/g, '')"
                                    >
                                    <div v-if="form.errors.nisn" class="invalid-feedback">{{ form.errors.nisn }}</div>
                                </div>

                                <div class="form-group">
                                    <label>Nama Lengkap <span class="text-danger">*</span></label>
                                    <input v-model="form.name" type="text" class="form-control" :class="{ 'is-invalid': form.errors.name }">
                                    <div v-if="form.errors.name" class="invalid-feedback">{{ form.errors.name }}</div>
                                </div>

                                <div class="form-group">
                                    <label>Kelas <span class="text-danger">*</span></label>
                                    <input v-model="form.grade" type="text" class="form-control" placeholder="Contoh: XII IPA 1" :class="{ 'is-invalid': form.errors.grade }">
                                    <div v-if="form.errors.grade" class="invalid-feedback">{{ form.errors.grade }}</div>
                                </div>
                                <div v-if="form.errors.election_id" class="text-danger small mb-2">{{ form.errors.election_id }}</div>

                                <div class="custom-control custom-switch">
                                    <input id="voter-active" v-model="form.is_active" type="checkbox" class="custom-control-input">
                                    <label class="custom-control-label" for="voter-active">Pemilih aktif</label>
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

        <template v-if="showImport">
            <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <form @submit.prevent="submitImport">
                            <div class="modal-header">
                                <h5 class="modal-title">Impor Data Pemilih</h5>
                                <button type="button" class="close" @click="closeImport"><span>&times;</span></button>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted small">
                                    Gunakan berkas .xlsx sesuai template (kolom No, NISN, Nama, Kelas). Non-siswa memakai kode 4 digit pada kolom NISN.
                                    Jika ada baris yang tidak valid, seluruh impor dibatalkan.
                                </p>
                                <div class="form-group">
                                    <label>Berkas Excel <span class="text-danger">*</span></label>
                                    <input
                                        ref="importInput"
                                        type="file"
                                        accept=".xlsx"
                                        class="form-control-file"
                                        @input="importForm.file = $event.target.files[0]"
                                    >
                                    <div v-if="importForm.errors.file" class="text-danger small mt-1">{{ importForm.errors.file }}</div>
                                    <div v-if="importForm.errors.election_id" class="text-danger small mt-1">{{ importForm.errors.election_id }}</div>
                                </div>
                            </div>
                            <div class="modal-footer justify-content-between">
                                <button type="button" class="btn btn-default" @click="closeImport">Batal</button>
                                <button type="submit" class="btn btn-success" :disabled="importForm.processing || !importForm.file">
                                    <span v-if="importForm.processing" class="spinner-border spinner-border-sm mr-1"></span>
                                    Impor
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-backdrop fade show"></div>
        </template>
    </template>
</template>
