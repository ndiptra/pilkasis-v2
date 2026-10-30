<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    profile: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    name: props.profile.name,
    email: props.profile.email,
    current_password: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.put('/profile', {
        preserveScroll: true,
        onSuccess: () => form.reset('current_password', 'password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Profil" />

    <div class="row">
        <div class="col-lg-4">
            <div class="card card-primary card-outline">
                <div class="card-body box-profile">
                    <div class="text-center mb-3">
                        <span class="img-circle elevation-2 bg-primary d-inline-flex align-items-center justify-content-center" style="width: 110px; height: 110px;">
                            <i class="fas fa-user fa-4x text-white"></i>
                        </span>
                    </div>

                    <h3 class="profile-username text-center">{{ profile.name }}</h3>
                    <p class="text-muted text-center">{{ profile.email }}</p>

                    <ul class="list-group list-group-unbordered mb-0">
                        <li class="list-group-item">
                            <b>Peran</b>
                            <span class="float-right">
                                {{ profile.role === 'superadmin' ? 'Super Admin' : 'Admin' }}
                            </span>
                        </li>
                        <li class="list-group-item">
                            <b>Terdaftar</b>
                            <span class="float-right">
                                {{ new Date(profile.created_at).toLocaleDateString('id-ID', { dateStyle: 'medium' }) }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <form @submit.prevent="submit">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Informasi Akun</h3>
                    </div>
                    <div class="card-body">
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

                        <hr>
                        <h6 class="text-muted mb-3">Ubah Kata Sandi</h6>

                        <div class="form-group">
                            <label>Kata Sandi Saat Ini</label>
                            <input v-model="form.current_password" type="password" class="form-control" autocomplete="current-password" :class="{ 'is-invalid': form.errors.current_password }">
                            <div v-if="form.errors.current_password" class="invalid-feedback">{{ form.errors.current_password }}</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Kata Sandi Baru</label>
                                    <input v-model="form.password" type="password" class="form-control" autocomplete="new-password" :class="{ 'is-invalid': form.errors.password }">
                                    <div v-if="form.errors.password" class="invalid-feedback">{{ form.errors.password }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Konfirmasi Kata Sandi Baru</label>
                                    <input v-model="form.password_confirmation" type="password" class="form-control" autocomplete="new-password">
                                </div>
                            </div>
                        </div>

                        <p class="text-muted small mb-0">
                            Kosongkan bagian kata sandi jika tidak ingin mengubahnya.
                        </p>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" :disabled="form.processing">
                            <span v-if="form.processing" class="spinner-border spinner-border-sm mr-1"></span>
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>
