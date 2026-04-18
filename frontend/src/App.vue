<script setup lang="ts">
import { onBeforeUnmount, onMounted, watch } from 'vue'
// import Plugins
import { useTheme } from 'vuetify'
import { i18n } from '@/plugins/i18n'
// Import Store
import { useThemeStore } from '@/stores/theme'
import { useLangStore } from '@/stores/lang'
// Import Components
import LanguageSwitchOverlay from '@/components/common/LanguageSwitchOverlay.vue'

const langStore = useLangStore()
// Apply locale awal — dari preferensi user tersimpan, atau fallback ke bahasa sistem.
i18n.global.locale.value = langStore.resolvedLocale

const themeStore = useThemeStore()
const theme = useTheme()

const applyResolvedTheme = () => {
    theme.change(themeStore.resolvedTheme)
}

/**
 * Ganti tema dengan animasi slide (View Transitions API).
 * Arah slide: ke dark → slide dari kanan; ke light → slide dari kiri.
 */
const applyThemeWithTransition = (toTheme: 'light' | 'dark') => {
    // Fallback kalau browser belum support View Transitions API
    if (typeof (document as any).startViewTransition !== 'function') {
        applyResolvedTheme()
        return
    }

    document.documentElement.dataset.themeDirection = toTheme === 'dark' ? 'right' : 'left'
    const transition = (document as any).startViewTransition(() => {
        applyResolvedTheme()
    })

    transition.finished?.finally(() => {
        delete document.documentElement.dataset.themeDirection
    })
}

// Apply tema awal sesuai preferensi tersimpan (atau system) — tanpa animasi.
applyResolvedTheme()

// Re-apply dengan animasi saat user mengubah preferensi.
watch(
    () => themeStore.resolvedTheme,
    (newVal) => applyThemeWithTransition(newVal)
)

// Listen perubahan tema OS — hanya berdampak ketika user belum pilih override.
const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')

const onSystemThemeChange = () => {
    if (themeStore.theme === 'system') applyResolvedTheme()
}

onMounted(() => {
    mediaQuery.addEventListener('change', onSystemThemeChange)
})

onBeforeUnmount(() => {
    mediaQuery.removeEventListener('change', onSystemThemeChange)
})
</script>

<template>
    <v-app>
        <router-view />
        <LanguageSwitchOverlay />
    </v-app>
</template>

<style scoped></style>
