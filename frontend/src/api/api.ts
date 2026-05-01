import { router } from '@/routes'
import { useAuthStore } from '@/stores/auth'
import { useMessageStore } from '@/stores/message'
import { resetIdleTimer } from '@/composables/useIdleTimeout'
import axios from 'axios'

/**
 * Axios instance untuk SPA stateful (cookie session).
 *
 *   `withCredentials: true`  → kirim cookie session HttpOnly + XSRF-TOKEN
 *                              ke setiap request same-origin / cross-origin
 *                              yang di-allowlist Sanctum.
 *   `xsrfCookieName`         → axios membaca cookie `XSRF-TOKEN` yang
 *                              di-set Sanctum, lalu mengirim balik sebagai
 *                              header `X-XSRF-TOKEN` yang divalidasi
 *                              `VerifyCsrfToken` middleware Laravel.
 *
 * Tidak ada Bearer token yang di-attach manual — token disimpan oleh
 * browser dalam cookie HttpOnly yang TIDAK bisa diakses JavaScript
 * (defense terhadap XSS).
 */
export const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL,
    withCredentials: true,
    xsrfCookieName: 'XSRF-TOKEN',
    xsrfHeaderName: 'X-XSRF-TOKEN',
})

/**
 * Inisialisasi cookie CSRF dari Sanctum sebelum request state-mutating
 * (POST/PUT/DELETE) pertama kali — khususnya login.
 *
 * Endpoint `/sanctum/csrf-cookie` di-serve oleh Sanctum dan hanya men-set
 * cookie XSRF-TOKEN + session cookie (tidak ada body response). Idempotent
 * — aman dipanggil berulang.
 */
export const initCsrf = async (): Promise<void> => {
    await axios.get('/sanctum/csrf-cookie', { withCredentials: true })
}

api.interceptors.request.use(
    (config) => {
        // Header Accept JSON eksplisit — supaya Laravel tahu request expect
        // JSON response (mengaktifkan Sanctum stateful detection + JSON 401
        // alih-alih HTML redirect untuk request unauthenticated).
        config.headers.Accept = 'application/json'
        return config
    },
    (error) => Promise.reject(error),
)

api.interceptors.response.use(
    (response) => {
        resetIdleTimer()
        return response
    },
    async (error) => {
        const status = error.response?.status

        if (status === 401) {
            const message = useMessageStore()
            message.setMessage({
                text: 'Sesi Anda telah berakhir. Silakan login kembali.',
                timeout: 4000,
                color: 'warning',
            })

            // Cookie sudah invalid di server — clear state lokal, redirect
            // ke signin tanpa hit API logout.
            const auth = useAuthStore()
            auth.clearSession()

            if (router.currentRoute.value.path !== '/auth/signin') {
                router.push('/auth/signin')
            }
        }

        if (status === 403) {
            console.warn('Akses ditolak')
        }

        return Promise.reject(error)
    },
)
