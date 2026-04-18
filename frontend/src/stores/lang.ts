import { defineStore } from 'pinia'
import { nextTick } from 'vue'
import { i18n } from '@/plugins/i18n'

const MIN_LOADING_MS = 500
const SUPPORTED_LOCALES = ['id', 'en'] as const
type Locale = typeof SUPPORTED_LOCALES[number]

/**
 * Deteksi bahasa sistem: gunakan bahasa Indonesia jika OS/browser set 'id',
 * selain itu fallback ke English.
 */
const detectSystemLocale = (): Locale => {
    if (typeof navigator === 'undefined') return 'en'

    const candidates = [
        ...(navigator.languages ?? []),
        navigator.language,
    ].filter(Boolean) as string[]

    for (const lang of candidates) {
        const code = lang.toLowerCase().split('-')[0]
        if (code === 'id') return 'id'
    }
    return 'en'
}

export const useLangStore = defineStore('lang', {
    state: () => ({
        // 'system' = ikuti bahasa OS/browser, 'id'/'en' = override eksplisit dari user.
        locale: 'system' as Locale | 'system',
        loading: false
    }),
    getters: {
        // Locale aktual yang harus dipakai i18n.
        resolvedLocale: (state): Locale =>
            state.locale === 'system' ? detectSystemLocale() : state.locale
    },
    actions: {
        async setLang(locale: Locale | 'system') {
            if (this.locale === locale || this.loading) return

            this.loading = true
            const startedAt = Date.now()

            try {
                this.locale = locale
                i18n.global.locale.value = this.resolvedLocale

                // Tunggu dua siklus render agar semua komponen selesai re-translate.
                await nextTick()
                await nextTick()

                // Pastikan overlay terlihat minimal beberapa ratus ms (anti-flicker UX).
                const elapsed = Date.now() - startedAt
                if (elapsed < MIN_LOADING_MS) {
                    await new Promise(resolve => setTimeout(resolve, MIN_LOADING_MS - elapsed))
                }
            } finally {
                this.loading = false
            }
        },
        useSystemLang() {
            this.setLang('system')
        }
    },
    persist: {
        key: 'lang',
        storage: localStorage,
        pick: ['locale']
    }
})