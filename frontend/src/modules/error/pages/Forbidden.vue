<template>
    <v-container class="fill-height d-flex flex-column justify-center align-center">
        <v-icon color="error" size="96">mdi-alert-circle-outline</v-icon>
        <h1 class="text-h2 text-error mb-2">403 Forbidden</h1>
        <p class="text-body-1 mb-4">Anda tidak memiliki akses ke halaman ini.</p>
        <v-btn color="primary" @click="$router.push('/')">Kembali ke Beranda</v-btn>
    </v-container>
</template>

<script setup lang="ts">
// Tidak perlu script tambahan
</script>

<style scoped>
.fill-height {
    min-height: 80vh;
}
</style>
