import { router } from './../routes/index';
import { defineStore } from 'pinia'
import { me, signin, signout } from '@/api/auth'
import { initCsrf } from '@/api/api'
import { useMessageStore } from './message'
import { updateAbility } from '@/composables/updateAbility';
import { clearIdleTimer, resetIdleTimer } from '@/composables/useIdleTimeout';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: null,
        user: null as AuthUser | null,
        loading: false
    }),
    getters: {
        isAuthenticated: (state) => !!state.token,
        userId: (state) => state.user?.id ?? undefined,
        userName: (state) => state.user?.name ?? ''
    },
    actions: {
        async signin (payload: { email: string, password: string }) {
            const message = useMessageStore()
            this.loading = true

            try {
                // Ambil cookie CSRF (XSRF-TOKEN + session) sebelum POST signin.
                // Tanpa ini, Laravel akan menolak request dengan "CSRF token mismatch".
                await initCsrf()

                const response = await signin(payload)

                if (response.status === 200 || response.status === 201) {
                    this.token = response.data.token
                    resetIdleTimer()

                    message.setMessage({
                        text: 'Successfully signed in',
                        timeout: 1500,
                        color: 'success'
                    }).then(() => {
                        window.location.href = '/'
                    })
                }
            } catch (error) {
                message.setMessage({
                    text: 'Incorrect username or password',
                    timeout: 1500,
                    color: 'error'
                })
            } finally {
                this.loading = false
            }
        },

        async fetchMe () {
            if (this.user) return

            this.loading = true

            try {
                const { data } = await me()

                this.user = data

                // console.log('permissions', data.permissions)
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
                    color: 'success'
                })
            } catch (error) {
                message.setMessage({
                    text: 'Sign out was unsuccessful',
                    timeout: 1500,
                    color: 'error'
                })
            } finally {
                this.clearSession()
                this.loading = false
                router.push('/auth/signin')
            }
        },

        /**
         * Clear semua state auth + storage. Dipanggil ketika token expired/invalid
         * tanpa perlu hit API signout (karena token sudah tidak valid di server).
         */
        clearSession() {
            // Hapus persisted state
            localStorage.removeItem('auth')
            // Hapus key terkait sesi lain (idle tracker)
            clearIdleTimer()
            // Reset Pinia state ke initial values
            this.$reset()
        }
    },
    persist: {
        pick: ['token']
    }
})