<script setup>
import { computed, onMounted, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import logoUrl from '../../images/logo.png';
import logoIconUrl from '../../images/logo-icon.png';
import { useAccess } from '../Composables/useAccess';

const page = usePage();

const user = computed(() => page.props.auth?.user);
const { isSuperadmin, canViewResults } = useAccess();
const routeName = computed(() => page.props.routeName || '');
const title = computed(() => page.props.title || 'Dasbor');
const subtitle = computed(() => page.props.subtitle || '');
const appName = computed(() => page.props.appName || 'Pilkasis');
const isActive = (prefix) => routeName.value === prefix || routeName.value.startsWith(`${prefix}.`);

const breadcrumbGroups = {
    'Data Master': ['school-profile', 'elections', 'voters', 'candidates'],
    Pemilihan: ['settings', 'results'],
    Pengguna: ['users', 'profile'],
};

const breadcrumbGroup = computed(() => Object.keys(breadcrumbGroups)
    .find((group) => breadcrumbGroups[group].some((prefix) => isActive(prefix))) ?? null);

const logout = () => {
    router.post('/logout');
};

onMounted(() => {
    const body = document.body;
    body.classList.add('hold-transition', 'layout-fixed');
    body.classList.remove('login-page', 'layout-top-nav');
});

watch(
    () => page.props.flash,
    (flash) => {
        if (!window.toastr) {
            return;
        }

        if (flash?.success) {
            window.toastr.success(flash.success);
        }

        if (flash?.error) {
            window.toastr.error(flash.error);
        }
    },
    { deep: true, immediate: true },
);
</script>

<template>
    <div class="wrapper">
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                        <i class="fas fa-bars"></i>
                    </a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <Link href="/" class="nav-link">Beranda</Link>
                </li>
            </ul>

            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                        <i class="fas fa-expand-arrows-alt"></i>
                    </a>
                </li>
            </ul>
        </nav>

        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <Link href="/" class="brand-link brand-pilkasis" aria-label="PILKASIS">
                <span class="brand-logo-panel">
                    <img :src="logoUrl" alt="PILKASIS" class="brand-logo-full">
                    <img :src="logoIconUrl" alt="PILKASIS" class="brand-logo-icon">
                </span>
            </Link>

            <div class="sidebar">
                <nav class="mt-3">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                        <li class="nav-item">
                            <Link href="/" class="nav-link" :class="{ active: routeName === 'dashboard' }">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dasbor</p>
                            </Link>
                        </li>

                        <li class="nav-header">DATA MASTER</li>
                        <li v-if="isSuperadmin" class="nav-item">
                            <Link href="/school-profile" class="nav-link" :class="{ active: isActive('school-profile') }">
                                <i class="nav-icon fas fa-school"></i>
                                <p>Identitas Sekolah</p>
                            </Link>
                        </li>
                        <li v-if="isSuperadmin" class="nav-item">
                            <Link href="/elections" class="nav-link" :class="{ active: isActive('elections') }">
                                <i class="nav-icon fas fa-calendar-alt"></i>
                                <p>Data Pemilihan</p>
                            </Link>
                        </li>
                        <li class="nav-item">
                            <Link href="/voters" class="nav-link" :class="{ active: isActive('voters') }">
                                <i class="nav-icon fas fa-users"></i>
                                <p>Data Pemilih</p>
                            </Link>
                        </li>
                        <li class="nav-item">
                            <Link href="/candidates" class="nav-link" :class="{ active: isActive('candidates') }">
                                <i class="nav-icon fas fa-user-tie"></i>
                                <p>Data Paslon</p>
                            </Link>
                        </li>

                        <li v-if="isSuperadmin || canViewResults" class="nav-header">PEMILIHAN</li>
                        <li v-if="isSuperadmin" class="nav-item">
                            <Link href="/settings" class="nav-link" :class="{ active: isActive('settings') }">
                                <i class="nav-icon fas fa-cogs"></i>
                                <p>Pengaturan</p>
                            </Link>
                        </li>
                        <li v-if="canViewResults" class="nav-item">
                            <Link href="/results" class="nav-link" :class="{ active: isActive('results') }">
                                <i class="nav-icon fas fa-chart-bar"></i>
                                <p>Hasil</p>
                            </Link>
                        </li>

                        <li class="nav-header">PENGGUNA</li>
                        <li v-if="isSuperadmin" class="nav-item">
                            <Link href="/users" class="nav-link" :class="{ active: isActive('users') }">
                                <i class="nav-icon fas fa-user-cog"></i>
                                <p>Data Pengguna</p>
                            </Link>
                        </li>
                        <li class="nav-item">
                            <Link href="/profile" class="nav-link" :class="{ active: isActive('profile') }">
                                <i class="nav-icon fas fa-id-card"></i>
                                <p>Profil</p>
                            </Link>
                        </li>
                        <li class="nav-item">
                            <a href="/logout" class="nav-link" @click.prevent="logout">
                                <i class="nav-icon fas fa-sign-out-alt"></i>
                                <p>Keluar</p>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-7">
                            <h1 class="mb-1">{{ title }}</h1>
                            <p v-if="subtitle" class="text-muted mb-0">{{ subtitle }}</p>
                        </div>
                        <div class="col-sm-5">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><Link href="/">Dasbor</Link></li>
                                <li v-if="breadcrumbGroup" class="breadcrumb-item">{{ breadcrumbGroup }}</li>
                                <li class="breadcrumb-item active">{{ title }}</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid">
                    <slot />
                </div>
            </section>
        </div>

        <footer class="main-footer">
            <div class="float-right d-none d-sm-inline">Aplikasi Pemilihan Ketua OSIS</div>
            <strong>&copy; {{ new Date().getFullYear() }} PILKASIS</strong>
        </footer>
    </div>
</template>
