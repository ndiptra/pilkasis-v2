<script setup>
import { computed, onMounted, ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import logoUrl from '../../../images/logo.png';

const props = defineProps({
    school: { type: Object, default: null },
    election: { type: Object, default: null },
    status: { type: String, default: null },
});

const page = usePage();
const flashError = computed(() => page.props.flash?.error);
const showPassword = ref(false);

const form = useForm({
    nisn: '',
    password: '',
});

const canLogin = computed(() => Boolean(props.election) && props.status === 'ongoing');

const notice = computed(() => {
    if (!props.election) {
        return 'Belum ada pemilihan yang aktif.';
    }

    return {
        not_started: 'Pemilihan belum dimulai.',
        paused: 'Pemilihan sedang dijeda.',
        closed: 'Pemilihan sudah ditutup.',
        final: 'Pemilihan sudah ditutup.',
    }[props.status] ?? (canLogin.value ? null : 'Pemilihan belum dimulai.');
});

const submit = () => {
    form.post('/voter/login', {
        onFinish: () => form.reset('password'),
    });
};

onMounted(() => {
    document.body.className = 'hold-transition login-page';
    document.body.removeAttribute('style');
    document.documentElement.removeAttribute('style');
});
</script>

<template>
    <Head title="Akses Pemilih" />

    <div class="login-box login-box-pilkasis">
        <div class="card card-outline card-primary login-card">
            <div class="card-header text-center login-card-header">
                <img :src="logoUrl" class="login-brand" alt="PILKASIS">
            </div>

            <div class="card-body login-card-body">
                <h5 class="login-title text-center">Akses Pemilih</h5>
                <p class="login-box-msg pb-3">
                    {{ election ? election.name : 'Pemilihan Ketua OSIS' }}
                </p>

                <div v-if="flashError" class="alert alert-warning">{{ flashError }}</div>
                <div v-else-if="notice" class="alert alert-info mb-0">{{ notice }}</div>

                <form v-if="canLogin" novalidate @submit.prevent="submit">
                    <div class="form-group">
                        <div class="input-group">
                            <input
                                v-model="form.nisn"
                                type="text"
                                inputmode="numeric"
                                maxlength="10"
                                class="form-control"
                                :class="{ 'is-invalid': form.errors.nisn }"
                                placeholder="NISN atau kode 4 digit"
                                autocomplete="off"
                                autofocus
                                @input="form.nisn = form.nisn.replace(/\D/g, '')"
                            >
                            <div class="input-group-append">
                                <div class="input-group-text"><span class="fas fa-id-card"></span></div>
                            </div>
                            <span v-if="form.errors.nisn" class="invalid-feedback d-block">{{ form.errors.nisn }}</span>
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
                                autocomplete="off"
                            >
                            <div class="input-group-append">
                                <div
                                    class="input-group-text password-toggle"
                                    role="button"
                                    tabindex="0"
                                    :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                                    @click="showPassword = !showPassword"
                                    @keydown.enter.prevent="showPassword = !showPassword"
                                    @keydown.space.prevent="showPassword = !showPassword"
                                >
                                    <span class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></span>
                                </div>
                            </div>
                            <span v-if="form.errors.password" class="invalid-feedback d-block">{{ form.errors.password }}</span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block mt-4" :disabled="form.processing">
                        <span v-if="form.processing" class="spinner-border spinner-border-sm mr-1"></span>
                        Masuk
                    </button>
                </form>
            </div>
        </div>

        <p class="login-copyright text-center text-muted small">
            &copy; {{ new Date().getFullYear() }} PILKASIS &middot; Aplikasi Pemilihan Ketua OSIS
        </p>
    </div>
</template>
