import { defineStore } from 'pinia'

const SYSTEM_QUERY = '(prefers-color-scheme: dark)'

const getSystemTheme = (): 'light' | 'dark' =>
    typeof window !== 'undefined' && window.matchMedia(SYSTEM_QUERY).matches ? 'dark' : 'light'

export const useThemeStore = defineStore('theme', {
    state: () => ({
        // 'system' = ikuti OS, 'light' / 'dark' = override eksplisit dari user.
        theme: 'system' as ThemePreferences,
        loading: false
    }),
    getters: {
        // Tema aktif yang harus diterapkan ke Vuetify.
        resolvedTheme: (state): 'light' | 'dark' =>
            state.theme === 'system' ? getSystemTheme() : (state.theme as 'light' | 'dark')
    },
    actions: {
        setTheme(theme: ThemePreferences) {
            this.loading = true
            this.theme = theme

            setTimeout(() => {
                this.loading = false
            }, 300)
        },
        useSystemTheme() {
            this.setTheme('system')
        }
    },
    persist: {
        key: 'theme',
        storage: localStorage,
        pick: ['theme']
    }
})