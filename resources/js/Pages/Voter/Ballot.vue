<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import logoUrl from '../../../images/logo.png';

const props = defineProps({
    voter: { type: Object, required: true },
    election: { type: Object, required: true },
    school: { type: Object, default: null },
    idleSeconds: { type: Number, default: 300 },
    candidates: { type: Array, default: () => [] },
});

const remaining = ref(props.idleSeconds);
const submitting = ref(false);

let deadline = Date.now() + props.idleSeconds * 1000;
let ticker = null;
let pinger = null;
let dirty = false;

const clock = computed(() => {
    const minutes = String(Math.floor(remaining.value / 60)).padStart(2, '0');
    const seconds = String(remaining.value % 60).padStart(2, '0');

    return `${minutes}:${seconds}`;
});

const expire = () => {
    clearInterval(ticker);
    clearInterval(pinger);
    router.post('/voter/logout', { expired: true });
};

const tick = () => {
    remaining.value = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));

    if (remaining.value === 0) {
        expire();
    }
};

const resetTimer = () => {
    if (submitting.value) {
        return;
    }

    deadline = Date.now() + props.idleSeconds * 1000;
    remaining.value = props.idleSeconds;
    dirty = true;
};

const syncSession = () => {
    if (!dirty || remaining.value === 0) {
        return;
    }

    dirty = false;
    router.reload({ only: ['idleSeconds'], preserveScroll: true, preserveState: true });
};

const vote = (candidate) => {
    const text = `${candidate.chairman_name}${candidate.vice_chairman_name ? ` & ${candidate.vice_chairman_name}` : ''}`;

    const send = () => {
        submitting.value = true;
        router.post('/vote', { candidate_id: candidate.id }, {
            onFinish: () => {
                submitting.value = false;
            },
        });
    };

    if (!window.Swal) {
        if (window.confirm(`Yakin memilih Paslon ${candidate.order_number}?`)) {
            send();
        }

        return;
    }

    window.Swal.fire({
        title: `Yakin memilih Paslon ${candidate.order_number}?`,
        text: `${text}. Pilihan tidak dapat diubah setelah dikonfirmasi.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, saya yakin',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#007bff',
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            send();
        }
    });
};

const events = ['click', 'keydown', 'touchstart'];

onMounted(() => {
    document.body.className = 'hold-transition layout-top-nav';
    document.body.removeAttribute('style');

    events.forEach((name) => window.addEventListener(name, resetTimer, true));
    ticker = setInterval(tick, 1000);
    pinger = setInterval(syncSession, 15000);
});

onBeforeUnmount(() => {
    events.forEach((name) => window.removeEventListener(name, resetTimer, true));
    clearInterval(ticker);
    clearInterval(pinger);
});
</script>

<template>
    <Head title="Memilih" />

    <div class="wrapper">
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <div class="container">
                <a class="navbar-brand" href="#" @click.prevent>
                    <img :src="logoUrl" alt="PILKASIS" class="voter-brand-logo">
                </a>

                <ul class="navbar-nav ml-auto align-items-center">
                    <li class="nav-item d-none d-sm-block mr-3 text-right">
                        <div class="font-weight-bold">{{ voter.name }}</div>
                        <small class="text-muted">{{ voter.grade }}</small>
                    </li>
                    <li class="nav-item">
                        <span class="badge voter-timer" :class="remaining <= 60 ? 'badge-danger' : 'badge-primary'">
                            <i class="far fa-clock mr-1"></i> {{ clock }}
                        </span>
                    </li>
                </ul>
            </div>
        </nav>

        <div class="content-wrapper">
            <div class="content-header">
                <div class="container">
                    <h1 class="m-0 text-dark">Pilih Pasangan Calon</h1>
                    <p class="text-muted mb-0">
                        {{ election.name }}<span v-if="school?.name"> &middot; {{ school.name }}</span>.
                        Waktu kembali ke 5 menit setiap kamu menyentuh halaman ini.
                    </p>
                </div>
            </div>

            <div class="content">
                <div class="container">
                    <div v-if="candidates.length === 0" class="card">
                        <div class="card-body text-center text-muted py-5">Belum ada pasangan calon.</div>
                    </div>

                    <div v-else class="row">
                        <div v-for="candidate in candidates" :key="candidate.id" class="col-12 col-md-6 col-lg-4 d-flex">
                            <div class="card card-primary card-outline voter-candidate w-100">
                                <div class="voter-candidate-photo">
                                    <img
                                        v-if="candidate.photo_path"
                                        :src="`/storage/${candidate.photo_path}`"
                                        :alt="candidate.chairman_name"
                                    >
                                    <i v-else class="fas fa-user-tie"></i>
                                    <span class="voter-candidate-number">{{ candidate.order_number }}</span>
                                </div>

                                <div class="card-body">
                                    <h5 class="font-weight-bold mb-0">{{ candidate.chairman_name }}</h5>
                                    <p v-if="candidate.vice_chairman_name" class="text-muted mb-3">&amp; {{ candidate.vice_chairman_name }}</p>

                                    <h6 class="text-primary mb-1">Visi</h6>
                                    <div v-if="candidate.vision" class="candidate-richtext mb-3" v-html="candidate.vision"></div>
                                    <p v-else class="small text-muted mb-3">-</p>

                                    <h6 class="text-primary mb-1">Misi</h6>
                                    <div v-if="candidate.mission" class="candidate-richtext" v-html="candidate.mission"></div>
                                    <p v-else class="small text-muted mb-0">-</p>
                                </div>

                                <div class="card-footer bg-white">
                                    <button type="button" class="btn btn-primary btn-block btn-lg" :disabled="submitting" @click="vote(candidate)">
                                        <i class="fas fa-check-circle mr-1"></i> Pilih Paslon {{ candidate.order_number }}
                                    </button>
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
.voter-brand-logo {
    height: 34px;
    width: auto;
}

.voter-timer {
    font-size: 1rem;
    padding: .5rem .85rem;
    font-variant-numeric: tabular-nums;
}

.voter-candidate-photo {
    position: relative;
    aspect-ratio: 4 / 3;
    display: grid;
    place-items: center;
    background: #eef2f7;
    color: #94a3b8;
    font-size: 4rem;
}

.voter-candidate-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.voter-candidate-number {
    position: absolute;
    top: 12px;
    left: 12px;
    width: 42px;
    height: 42px;
    border-radius: 8px;
    display: grid;
    place-items: center;
    background: #007bff;
    color: #fff;
    font-size: 1.2rem;
    font-weight: 700;
}
</style>
