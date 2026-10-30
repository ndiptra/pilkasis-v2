<script setup>
import { useAccess } from '../Composables/useAccess';

const { canModifyData } = useAccess();
</script>

<template>
    <div v-if="!canModifyData" class="alert alert-secondary data-locked-alert" role="alert">
        <i class="fas fa-lock mr-2"></i>
        Data terkunci. Perubahan hanya dapat dilakukan saat status pemilihan <strong>Belum Mulai</strong> atau <strong>Jeda</strong>.
    </div>
</template>
