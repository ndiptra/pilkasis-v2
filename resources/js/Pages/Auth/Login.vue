<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import logoUrl from '../../../images/logo.png';

defineProps({
    status: {
        type: String,
        default: null,
    },
});

const showPassword = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};

onMounted(() => {
    const body = document.body;
    body.className = 'hold-transition login-page';
    body.removeAttribute('style');
    document.documentElement.removeAttribute('style');
});
</script>

<template>
    <Head title="Masuk" />

    <div class="login-box login-box-pilkasis">
        <div class="card card-outline card-primary login-card">
            <div class="card-header text-center login-card-header">
                <img :src="logoUrl" class="login-brand" alt="PILKASIS - Aplikasi Pemilihan Ketua OSIS">
            </div>

            <div class="card-body login-card-body">
                <h5 class="login-title text-center">Selamat Datang</h5>
                <p class="login-box-msg pb-3">Masuk untuk mengelola pemilihan</p>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-1"></i>
                    Halaman ini khusus untuk <strong>Superadmin dan Admin</strong>. Pemilih tidak masuk melalui halaman ini.
                </div>

                <div v-if="status" class="alert alert-warning">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" novalidate>
                    <div class="form-group">
                        <div class="input-group">
                            <input
                                v-model="form.email"
                                type="email"
                                class="form-control"
                                :class="{ 'is-invalid': form.errors.email }"
                                placeholder="Email"
                                autocomplete="username"
                                autofocus
                                required
                            >
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-envelope"></span>
                                </div>
                            </div>
                            <span v-if="form.errors.email" class="invalid-feedback d-block">
                                {{ form.errors.email }}
                            </span>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-group">
                            <input
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                class="form-control"
                                :class="{ 'is-invalid': form.errors.password }"
                                placeholder="Kata sandi"
                                autocomplete="current-password"
                                required
                            >
                            <div class="input-group-append">
                                <div
                                    class="input-group-text password-toggle"
                                    role="button"
                                    tabindex="0"
                                    :title="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                                    :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                                    :aria-pressed="showPassword"
                                    @click="showPassword = !showPassword"
                                    @keydown.enter.prevent="showPassword = !showPassword"
                                    @keydown.space.prevent="showPassword = !showPassword"
                                >
                                    <span class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></span>
                                </div>
                            </div>
                            <span v-if="form.errors.password" class="invalid-feedback d-block">
                                {{ form.errors.password }}
                            </span>
                        </div>
                    </div>

                    <div class="row align-items-center mt-4">
                        <div class="col-7">
                            <div class="icheck-primary">
                                <input id="remember" v-model="form.remember" type="checkbox">
                                <label for="remember">Ingat saya</label>
                            </div>
                        </div>
                        <div class="col-5">
                            <button type="submit" class="btn btn-primary btn-block" :disabled="form.processing">
                                <span v-if="form.processing" class="spinner-border spinner-border-sm mr-1"></span>
                                Masuk
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <p class="login-copyright text-center text-muted small">
            &copy; {{ new Date().getFullYear() }} PILKASIS &middot; Aplikasi Pemilihan Ketua OSIS
        </p>
    </div>
</template>