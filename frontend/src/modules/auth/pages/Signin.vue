<script lang="ts" setup>
import { ref } from 'vue'
// Import Components
import BasePasswordInput from '@/components/base/BasePasswordInput.vue'
// Import Icons
import SiLockFill from '~icons/si/lock-fill'
// Import Store
import { useAuthStore } from '@/stores/auth'

const store = useAuthStore()
const username = ref<string>('')
const password = ref<string>('')

const handleSubmit = () => {
    const credential = {
        email: username.value,
        password: password.value
    }

    store.signin(credential)
}
</script>

<template>
    <div class="d-flex flex-column ga-2">
        <v-btn variant="flat" color="blue-accent-2" text="Sign in with HRD APP" :prepend-icon="SiLockFill" block />
        <v-btn variant="flat" color="grey-lighten-3">
            Sign in as developer
            <v-dialog activator="parent" max-width="400" persistent>
                <template v-slot:default="{ isActive }">
                    <v-card :loading="store.loading" rounded="lg" class="px-4 py-2">
                        <v-card-title>
                            <p class="font-weight-bold text-h5 text-blue-accent-2">
                                Sign In as
                                <br>Developer
                            </p>
                        </v-card-title>
                        <v-card-subtitle>
                            <p class="font-weight-light text-caption text-grey-darken-3">Enter your username and password</p>
                        </v-card-subtitle>
                        <v-card-text>
                            <v-form @submit.prevent="handleSubmit">
                                <v-text-field
                                    v-model="username"
                                    label="Username"
                                    variant="outlined"
                                    density="compact"
                                    autocomplete="username"
                                />
                                <base-password-input
                                    :model-value="password"
                                    label="Password"
                                    @update:model-value="(value) => password = value"
                                />
            
                                <div class="d-flex flex-column ga-2">
                                    <v-btn :loading="store.loading" type="submit" variant="flat" color="blue-accent-2" text="Login" :append-icon="SiLockFill" block />
                                    <v-btn :loading="store.loading" variant="flat" color="grey-lighten-2" text="Cancel" block @click="isActive.value = false" />
                                </div>
                            </v-form>
                        </v-card-text>
                    </v-card>
                </template>
            </v-dialog>
        </v-btn>
    </div>
</template>

<style scoped>
.v-card-text {
    padding: 1rem !important;
}
</style>