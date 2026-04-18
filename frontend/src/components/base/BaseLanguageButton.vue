<script lang="ts" setup>
import { computed } from 'vue'
import { useDisplay } from 'vuetify'
// Import Stores
import { useLangStore } from '@/stores/lang'

const langStore = useLangStore()
const { smAndDown } = useDisplay()

const selected = computed({
    // Reflect locale aktual (resolved dari 'system' bila belum ada override user).
    get: () => langStore.resolvedLocale,
    set: (value: string) => {
        langStore.setLang(value as 'id' | 'en')
    }
})

const toggleLang = () => {
    const next = langStore.resolvedLocale === 'id' ? 'en' : 'id'
    langStore.setLang(next)
}
</script>

<template>
    <!-- Mobile: satu tombol toggle -->
    <v-btn
        v-if="smAndDown"
        :loading="langStore.loading"
        :disabled="langStore.loading"
        variant="outlined"
        rounded="lg"
        density="compact"
        size="small"
        color="blue-accent-2"
        min-width="44"
        class="font-weight-bold"
        @click="toggleLang"
    >
        {{ langStore.resolvedLocale.toUpperCase() }}
    </v-btn>

    <!-- Desktop: toggle group dua tombol -->
    <v-btn-toggle
        v-else
        v-model="selected"
        color="blue-accent-2"
        density="compact"
        variant="outlined"
        rounded="lg"
        mandatory
        :disabled="langStore.loading"
    >
        <v-btn :loading="langStore.loading" value="id">ID</v-btn>
        <v-btn :loading="langStore.loading" value="en">EN</v-btn>
    </v-btn-toggle>
</template>

<style scoped></style>