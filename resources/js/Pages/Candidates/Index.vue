<script setup>
import { onUnmounted, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import RichTextEditor from '../../Components/RichTextEditor.vue';
import NoElectionAlert from '../../Components/NoElectionAlert.vue';
import DataLockedAlert from '../../Components/DataLockedAlert.vue';
import { useAccess } from '../../Composables/useAccess';

const { canModifyData } = useAccess();

defineProps({
    candidates: {
        type: Array,
        default: () => [],
    },
    election: {
        type: Object,
        default: null,
    },
});

const showModal = ref(false);
const editing = ref(null);
const photoPreview = ref(null);

const form = useForm({
    order_number: '',
    chairman_name: '',
    vice_chairman_name: '',
    vision: '',
    mission: '',
    photo: null,
});

const resetForm = () => {
    if (photoPreview.value) {
        URL.revokeObjectURL(photoPreview.value);
        photoPreview.value = null;
    }

    form.reset();
    form.clearErrors();
};

const openCreate = () => {
    editing.value = null;
    form.defaults({
        order_number: '',
        chairman_name: '',
        vice_chairman_name: '',
        vision: '',
        mission: '',
        photo: null,
    });
    resetForm();
    showModal.value = true;
};

const openEdit = (candidate) => {
    editing.value = candidate;
    form.defaults({
        order_number: candidate.order_number,
        chairman_name: candidate.chairman_name,
        vice_chairman_name: candidate.vice_chairman_name ?? '',
        vision: candidate.vision ?? '',
        mission: candidate.mission ?? '',
        photo: null,
    });
    resetForm();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editing.value = null;
    resetForm();
};

const onPhotoChange = (event) => {
    const file = event.target.files[0] ?? null;
    form.photo = file;

    if (photoPreview.value) {
        URL.revokeObjectURL(photoPreview.value);
        photoPreview.value = null;
    }

    if (file) {
        photoPreview.value = URL.createObjectURL(file);
    }
};

const previewUrl = () => {
    if (photoPreview.value) {
        return photoPreview.value;
    }

    return editing.value?.photo_path ? `/storage/${editing.value.photo_path}` : null;
};

const submit = () => {
    const options = {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => closeModal(),
    };

    if (editing.value) {
        form.put(`/candidates/${editing.value.id}`, options);
    } else {
        form.post('/candidates', options);
    }
};

const confirmDelete = (candidate) => {
    const message = `Paslon ${candidate.order_number} (${candidate.chairman_name}) akan dihapus permanen.`;

    if (!window.Swal) {
        if (window.confirm(message)) {
            router.delete(`/candidates/${candidate.id}`, { preserveScroll: true });
        }

        return;
    }

    window.Swal.fire({
        title: 'Hapus data paslon?',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#dc3545',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/candidates/${candidate.id}`, { preserveScroll: true });
        }
    });
};

onUnmounted(() => {
    if (photoPreview.value) {
        URL.revokeObjectURL(photoPreview.value);
    }
});
</script>

<template>
    <Head title="Data Paslon" />

    <NoElectionAlert v-if="!election" />

    <template v-else>
        <DataLockedAlert />

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Daftar Pasangan Calon</h3>
                <div class="card-tools">
                    <button v-if="election && canModifyData" type="button" class="btn btn-primary btn-sm" @click="openCreate">
                        <i class="fas fa-plus mr-1"></i> Tambah Paslon
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div v-if="candidates.length === 0" class="text-center text-muted py-5">
                    <i class="fas fa-user-tie fa-3x mb-3 d-block"></i>
                    Belum ada pasangan calon. Klik "Tambah Paslon" untuk memulai.
                </div>

                <div v-else class="row">
                    <div v-for="candidate in candidates" :key="candidate.id" class="col-12 col-md-6 col-xl-4 d-flex">
                        <div class="card card-outline card-primary candidate-card w-100">
                            <div class="candidate-photo-wrapper">
                                <img
                                    v-if="candidate.photo_path"
                                    :src="`/storage/${candidate.photo_path}`"
                                    :alt="candidate.chairman_name"
                                    class="candidate-photo"
                                >
                                <div v-else class="candidate-photo-placeholder">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <span class="candidate-number">{{ candidate.order_number }}</span>
                            </div>

                            <div class="card-body">
                                <h5 class="mb-0 font-weight-bold">{{ candidate.chairman_name }}</h5>
                                <p class="text-muted mb-3">
                                    {{ candidate.vice_chairman_name ? `& ${candidate.vice_chairman_name}` : 'Calon wakil belum diisi' }}
                                </p>

                                <h6 class="text-primary mb-1">Visi</h6>
                                <div v-if="candidate.vision" class="candidate-richtext mb-3" v-html="candidate.vision"></div>
                                <p v-else class="small text-muted mb-3">Visi belum diisi.</p>

                                <h6 class="text-primary mb-1">Misi</h6>
                                <div v-if="candidate.mission" class="candidate-richtext" v-html="candidate.mission"></div>
                                <p v-else class="small text-muted mb-0">Misi belum diisi.</p>
                            </div>

                            <div v-if="canModifyData" class="card-footer">
                                <div class="row no-gutters">
                                    <div class="col-6 pr-1">
                                        <button type="button" class="btn btn-sm btn-warning btn-block" @click="openEdit(candidate)">
                                            <i class="fas fa-edit mr-1"></i> Ubah
                                        </button>
                                    </div>
                                    <div class="col-6 pl-1">
                                        <button type="button" class="btn btn-sm btn-danger btn-block" @click="confirmDelete(candidate)">
                                            <i class="fas fa-trash mr-1"></i> Hapus
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <template v-if="showModal">
            <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <form @submit.prevent="submit">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    {{ editing ? 'Ubah Data Paslon' : 'Tambah Data Paslon' }}
                                </h5>
                                <button type="button" class="close" @click="closeModal">
                                    <span>&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-lg-5">
                                        <div class="form-group">
                                            <label>Foto Paslon</label>
                                            <div class="candidate-photo-wrapper mb-2">
                                                <img v-if="previewUrl()" :src="previewUrl()" alt="Foto paslon" class="candidate-photo">
                                                <div v-else class="candidate-photo-placeholder">
                                                    <i class="fas fa-camera"></i>
                                                </div>
                                            </div>
                                            <div class="custom-file">
                                                <input id="photo" type="file" class="custom-file-input" accept="image/*" @change="onPhotoChange">
                                                <label for="photo" class="custom-file-label">{{ form.photo ? form.photo.name : 'Pilih foto' }}</label>
                                            </div>
                                            <small class="text-muted d-block mt-1">JPG, PNG, atau WEBP. Maksimal 2 MB. Foto dipotong otomatis menjadi 1200x900 px.</small>
                                            <div v-if="form.errors.photo" class="text-danger small mt-1">{{ form.errors.photo }}</div>
                                        </div>

                                        <div class="form-group mb-0">
                                            <label>Nomor Urut <span class="text-danger">*</span></label>
                                            <input v-model="form.order_number" type="number" min="1" max="255" class="form-control" :class="{ 'is-invalid': form.errors.order_number }">
                                            <div v-if="form.errors.order_number" class="invalid-feedback">{{ form.errors.order_number }}</div>
                                        </div>
                                    </div>

                                    <div class="col-lg-7 mt-3 mt-lg-0">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Nama Ketua <span class="text-danger">*</span></label>
                                                    <input v-model="form.chairman_name" type="text" class="form-control" :class="{ 'is-invalid': form.errors.chairman_name }">
                                                    <div v-if="form.errors.chairman_name" class="invalid-feedback">{{ form.errors.chairman_name }}</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Nama Wakil</label>
                                                    <input v-model="form.vice_chairman_name" type="text" class="form-control" :class="{ 'is-invalid': form.errors.vice_chairman_name }">
                                                    <div v-if="form.errors.vice_chairman_name" class="invalid-feedback">{{ form.errors.vice_chairman_name }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>Visi</label>
                                            <RichTextEditor v-model="form.vision" :height="140" />
                                            <div v-if="form.errors.vision" class="text-danger small mt-1">{{ form.errors.vision }}</div>
                                        </div>

                                        <div class="form-group mb-0">
                                            <label>Misi</label>
                                            <RichTextEditor v-model="form.mission" :height="200" />
                                            <div v-if="form.errors.mission" class="text-danger small mt-1">{{ form.errors.mission }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>                        <div class="modal-footer justify-content-between">
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
</template>
