<script setup>
import { computed, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import DataLockedAlert from '../../Components/DataLockedAlert.vue';
import { useAccess } from '../../Composables/useAccess';

const { canModifyData } = useAccess();

const props = defineProps({
    profile: {
        type: Object,
        default: null,
    },
});

const form = useForm({
    name: props.profile?.name ?? '',
    address: props.profile?.address ?? '',
    phone: props.profile?.phone ?? '',
    email: props.profile?.email ?? '',
    website: props.profile?.website ?? '',
    headmaster_name: props.profile?.headmaster_name ?? '',
    headmaster_nip: props.profile?.headmaster_nip ?? '',
    logo: null,
});

const logoPreview = ref(null);

const logoUrl = computed(() => {
    if (logoPreview.value) {
        return logoPreview.value;
    }

    return props.profile?.logo_path ? `/storage/${props.profile.logo_path}` : null;
});

const onNipInput = (event) => {
    const digitsOnly = event.target.value.replace(/\D/g, '').slice(0, 30);

    form.headmaster_nip = digitsOnly;
    event.target.value = digitsOnly;
};

const onLogoChange = (event) => {
    const file = event.target.files[0] ?? null;
    form.logo = file;

    if (logoPreview.value) {
        URL.revokeObjectURL(logoPreview.value);
        logoPreview.value = null;
    }

    if (file) {
        logoPreview.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    form.put('/school-profile', {
        forceFormData: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Identitas Sekolah" />

    <DataLockedAlert />

    <form @submit.prevent="submit">
        <fieldset :disabled="!canModifyData">
        <div class="row">
            <div class="col-lg-8">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Informasi Sekolah</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Nama Sekolah <span class="text-danger">*</span></label>
                            <input v-model="form.name" type="text" class="form-control" :class="{ 'is-invalid': form.errors.name }">
                            <div v-if="form.errors.name" class="invalid-feedback">{{ form.errors.name }}</div>
                        </div>

                        <div class="row">
                            <div class="col-md-7">
                                <div class="form-group">
                                    <label>Nama Kepala Sekolah <span class="text-danger">*</span></label>
                                    <input v-model="form.headmaster_name" type="text" class="form-control" :class="{ 'is-invalid': form.errors.headmaster_name }">
                                    <div v-if="form.errors.headmaster_name" class="invalid-feedback">{{ form.errors.headmaster_name }}</div>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label>NIP</label>
                                    <input :value="form.headmaster_nip" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="30" autocomplete="off" class="form-control" :class="{ 'is-invalid': form.errors.headmaster_nip }" @input="onNipInput">
                                    <div v-if="form.errors.headmaster_nip" class="invalid-feedback">{{ form.errors.headmaster_nip }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Alamat <span class="text-danger">*</span></label>
                            <textarea v-model="form.address" rows="3" class="form-control" :class="{ 'is-invalid': form.errors.address }"></textarea>
                            <div v-if="form.errors.address" class="invalid-feedback">{{ form.errors.address }}</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Telepon</label>
                                    <input v-model="form.phone" type="text" class="form-control" :class="{ 'is-invalid': form.errors.phone }">
                                    <div v-if="form.errors.phone" class="invalid-feedback">{{ form.errors.phone }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input v-model="form.email" type="email" class="form-control" :class="{ 'is-invalid': form.errors.email }">
                                    <div v-if="form.errors.email" class="invalid-feedback">{{ form.errors.email }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Website</label>
                            <input v-model="form.website" type="text" class="form-control" placeholder="https://" :class="{ 'is-invalid': form.errors.website }">
                            <div v-if="form.errors.website" class="invalid-feedback">{{ form.errors.website }}</div>
                        </div>
                    </div>
                    <div v-if="canModifyData" class="card-footer">
                        <button type="submit" class="btn btn-primary" :disabled="form.processing">
                            <span v-if="form.processing" class="spinner-border spinner-border-sm mr-1"></span>
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Logo Sekolah</h3>
                    </div>
                    <div class="card-body text-center">
                        <img v-if="logoUrl" :src="logoUrl" alt="Logo sekolah" class="img-fluid img-thumbnail mb-3" style="max-height: 180px;">
                        <div v-else class="d-flex align-items-center justify-content-center bg-light mb-3" style="height: 180px;">
                            <i class="fas fa-school fa-4x text-muted"></i>
                        </div>

                        <div class="custom-file text-left">
                            <input id="logo" type="file" class="custom-file-input" accept="image/*" @change="onLogoChange">
                            <label for="logo" class="custom-file-label">{{ form.logo ? form.logo.name : 'Pilih file logo' }}</label>
                        </div>
                        <small class="text-muted d-block mt-2">Format JPG, PNG, atau WEBP. Maksimal 2 MB.</small>
                        <div v-if="form.errors.logo" class="invalid-feedback d-block">{{ form.errors.logo }}</div>
                    </div>
                </div>
            </div>
        </div>
        </fieldset>
    </form>
</template>
