<script lang="ts" setup>
import { useMessageStore } from '@/stores/message';

const message = useMessageStore()
</script>

<template>
    <v-card class="mx-auto my-auto text-center py-4" rounded="md" width="400">
        <v-card-title>
            <p class="font-weight-bold text-h5 text-blue-accent-2">Sign In</p>
        </v-card-title>
        <v-card-subtitle>
            <p class="font-weight-light text-caption text-grey-darken-1">Welcome back,</p>
            <p class="font-weight-light text-caption text-grey-darken-3">WMS (Warehouse Management System)</p>
        </v-card-subtitle>
        <v-card-text class="mt-3">
            <router-view />
            <v-snackbar-queue v-model="message.messages" location="top" />
        </v-card-text>
    </v-card>
</template>

<style scoped></style>