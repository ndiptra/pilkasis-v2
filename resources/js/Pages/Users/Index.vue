<script setup>
import { computed, onUnmounted, ref, watch } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import Pagination from '../../Components/Pagination.vue';
import PerPageSelect from '../../Components/PerPageSelect.vue';
import SortHeader from '../../Components/SortHeader.vue';
import { useTableQuery } from '../../Composables/useTableQuery';

const props = defineProps({
    users: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const currentUserId = computed(() => page.props.auth?.user?.id);
const search = ref(props.filters.search ?? '');
const showModal = ref(false);
const editing = ref(null);

const form = useForm({
    name: '',
    email: '',
    role: 'admin',
    is_active: true,
    password: '',
    password_confirmation: '',
});

const { sort, direction, toggleSort } = useTableQuery('/users', { search }, props.filters);

const isSelf = (user) => user.id === currentUserId.value;

const openCreate = () => {
    editing.value = null;
    form.defaults({
        name: '',
        email: '',
        role: 'admin',
        is_active: true,
        password: '',
        password_confirmation: '',
    });
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (user) => {
    editing.value = user;
    form.defaults({
        name: user.name,
        email: user.email,
        role: user.role,
        is_active: user.is_active,
        password: '',
        password_confirmation: '',
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
        form.put(`/users/${editing.value.id}`, options);
    } else {
        form.post('/users', options);
    }
};

const confirmDelete = (user) => {
    if (isSelf(user)) {
        return;
    }

    const message = `Akun ${user.name} (${user.email}) akan dihapus permanen.`;

    if (!window.Swal) {
        if (window.confirm(message)) {
            router.delete(`/users/${user.id}`, { preserveScroll: true });
        }

        return;
    }

    window.Swal.fire({
        title: 'Hapus data pengguna?',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#dc3545',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/users/${user.id}`, { preserveScroll: true });
        }
    });
};
</script>

<template>
    <Head title="Data Pengguna" />

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daftar Pengguna</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-primary btn-sm" @click="openCreate">
                    <i class="fas fa-plus mr-1"></i> Tambah Pengguna
                </button>
            </div>
        </div>
        <div class="card-body">
            <PerPageSelect>
                <input v-model="search" type="search" class="form-control form-control-sm search-input" placeholder="Cari nama atau email...">
            </PerPageSelect>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <SortHeader label="Nama" column="name" :sort="sort" :direction="direction" @sort="toggleSort" />
                            <SortHeader label="Email" column="email" :sort="sort" :direction="direction" @sort="toggleSort" />
                            <SortHeader label="Peran" column="role" :sort="sort" :direction="direction" @sort="toggleSort" />
                            <SortHeader label="Status" column="is_active" :sort="sort" :direction="direction" @sort="toggleSort" />
                            <SortHeader label="Terdaftar" column="created_at" :sort="sort" :direction="direction" @sort="toggleSort" />
                            <th class="action-column">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="users.data.length === 0">
                            <td colspan="7" class="text-center text-muted py-4">
                                Belum ada data pengguna yang sesuai.
                            </td>
                        </tr>
                        <tr v-for="(user, index) in users.data" :key="user.id">
                            <td class="text-center">{{ users.from + index }}</td>
                            <td>
                                {{ user.name }}
                                <span v-if="isSelf(user)" class="badge badge-info ml-1">Anda</span>
                            </td>
                            <td>{{ user.email }}</td>
                            <td>
                                <span class="badge" :class="user.role === 'superadmin' ? 'badge-primary' : 'badge-secondary'">
                                    {{ user.role === 'superadmin' ? 'Super Admin' : 'Admin' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge" :class="user.is_active ? 'badge-success' : 'badge-danger'">
                                    {{ user.is_active ? 'Aktif' : 'Tidak aktif' }}
                                </span>
                            </td>
                            <td>
                                {{ new Date(user.created_at).toLocaleDateString('id-ID', { dateStyle: 'medium' }) }}
                            </td>
                            <td class="table-actions text-center">
                                <button type="button" class="btn btn-sm btn-warning" title="Ubah" @click="openEdit(user)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-danger ml-1"
                                    title="Hapus"
                                    :disabled="isSelf(user)"
                                    @click="confirmDelete(user)"
                                >
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :paginator="users" />
        </div>
    </div>

    <template v-if="showModal">
        <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form @submit.prevent="submit">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                {{ editing ? 'Ubah Data Pengguna' : 'Tambah Data Pengguna' }}
                            </h5>
                            <button type="button" class="close" @click="closeModal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Nama Lengkap <span class="text-danger">*</span></label>
                                <input v-model="form.name" type="text" class="form-control" :class="{ 'is-invalid': form.errors.name }">
                                <div v-if="form.errors.name" class="invalid-feedback">{{ form.errors.name }}</div>
                            </div>

                            <div class="form-group">
                                <label>Email <span class="text-danger">*</span></label>
                                <input v-model="form.email" type="email" class="form-control" :class="{ 'is-invalid': form.errors.email }">
                                <div v-if="form.errors.email" class="invalid-feedback">{{ form.errors.email }}</div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Peran <span class="text-danger">*</span></label>
                                        <select v-model="form.role" class="form-control" :disabled="editing && isSelf(editing)" :class="{ 'is-invalid': form.errors.role }">
                                            <option value="admin">Admin</option>
                                            <option value="superadmin">Super Admin</option>
                                        </select>
                                        <div v-if="form.errors.role" class="invalid-feedback">{{ form.errors.role }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Status</label>
                                        <div class="custom-control custom-switch mt-2">
                                            <input id="user-active" v-model="form.is_active" type="checkbox" class="custom-control-input" :disabled="editing && isSelf(editing)">
                                            <label class="custom-control-label" for="user-active">Akun aktif</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <div class="form-group">
                                <label>Kata Sandi {{ editing ? '(kosongkan jika tidak diubah)' : '*' }}</label>
                                <input v-model="form.password" type="password" class="form-control" :class="{ 'is-invalid': form.errors.password }" autocomplete="new-password">
                                <div v-if="form.errors.password" class="invalid-feedback">{{ form.errors.password }}</div>
                            </div>

                            <div class="form-group mb-0">
                                <label>Konfirmasi Kata Sandi</label>
                                <input v-model="form.password_confirmation" type="password" class="form-control" autocomplete="new-password">
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
