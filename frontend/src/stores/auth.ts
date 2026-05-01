import { router } from './../routes/index'
import { defineStore } from 'pinia'
import { me, signin, signout } from '@/api/auth'
import { initCsrf } from '@/api/api'
import { useMessageStore } from './message'
import { updateAbility } from '@/composables/updateAbility'
import { clearIdleTimer, resetIdleTimer } from '@/composables/useIdleTimeout'

/**
 * Auth store — SPA stateful (cookie session).
 *
 * Tidak menyimpan token apapun di state / storage. Browser otomatis
 * membawa cookie `laravel_session` (HttpOnly) ke setiap request via
 * `withCredentials: true` di axios. JavaScript tidak bisa baca cookie ini
 * → defense terhadap XSS.
 *
 * `isAuthenticated` diturunkan dari keberadaan `user`, bukan token. User
 * di-hydrate via `fetchMe()` yang dipanggil sekali saat app boot atau
 * pertama kali butuh data.
 */
export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null as AuthUser | null,
        loading: false,
    }),
    getters: {
        isAuthenticated: (state) => !!state.user,
        userId: (state) => state.user?.id ?? undefined,
        userName: (state) => state.user?.name ?? '',
    },
    actions: {
        async signin(payload: { email: string; password: string }) {
            const message = useMessageStore()
            this.loading = true

            try {
                // Ambil cookie XSRF-TOKEN + session sebelum POST credentials.
                // Tanpa ini, Laravel tolak request dengan "CSRF token mismatch".
                await initCsrf()

                const response = await signin(payload)

                if (response.status === 200 || response.status === 201) {
                    // Server sudah set cookie session. Fetch profil + permission
                    // sebelum navigasi supaya CASL ability langsung tersedia.
                    await this.fetchMe()
                    resetIdleTimer()

                    message.setMessage({
                        text: 'Successfully signed in',
                        timeout: 1500,
                        color: 'success',
                    }).then(() => {
                        window.location.href = '/'
                    })
                }
            } catch (error: any) {
                const text = error?.response?.data?.errors?.email?.[0]
                    ?? 'Incorrect username or password'
                message.setMessage({ text, timeout: 1500, color: 'error' })
            } finally {
                this.loading = false
            }
        },

        async fetchMe() {
            if (this.user) return

            this.loading = true

            try {
                const { data } = await me()
                this.user = data
                updateAbility(data.permissions)
            } catch (error) {
                throw error
            } finally {
                this.loading = false
            }
        },

        async refreshPermissions() {
            this.loading = true
            try {
                const { data } = await me()
                this.user = data
                updateAbility(data.permissions)
            } catch (error) {
                throw error
            } finally {
                this.loading = false
            }
        },

        async signout() {
            const message = useMessageStore()
            this.loading = true

            try {
                const response = await signout()
                message.setMessage({
                    text: response.data.message,
                    timeout: 1500,
                    color: 'success',
                })
            } catch (error) {
                message.setMessage({
                    text: 'Sign out was unsuccessful',
                    timeout: 1500,
                    color: 'error',
                })
            } finally {
                this.clearSession()
                this.loading = false
                router.push('/auth/signin')
            }
        },

        /**
         * Clear state lokal saat session di server sudah expire/invalid.
         * TIDAK memanggil API logout (cookie sudah tidak valid).
         */
        clearSession() {
            clearIdleTimer()
            this.$reset()
        },
    },
    // NB: `persist` sengaja tidak dipakai. State user adalah cache yang
    // di-rebuild dari `me()`; cookie HttpOnly yang jadi sumber otoritas
    // session, bukan localStorage.
})
