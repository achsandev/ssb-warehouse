import { router } from '@/routes'
import { useAuthStore } from '@/stores/auth'
import { useMessageStore } from '@/stores/message'
import { resetIdleTimer } from '@/composables/useIdleTimeout'
import axios from 'axios'

export const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL,
    // Kirim session cookie + XSRF-TOKEN cookie pada setiap request same-origin.
    // Axios akan otomatis membaca cookie `XSRF-TOKEN` dan mengirimkannya sebagai
    // header `X-XSRF-TOKEN`, yang divalidasi oleh middleware VerifyCsrfToken Laravel.
    withCredentials: true,
    xsrfCookieName: 'XSRF-TOKEN',
    xsrfHeaderName: 'X-XSRF-TOKEN',
})

/**
 * Inisialisasi cookie CSRF dari Sanctum sebelum request state-mutating (POST/PUT/DELETE)
 * pertama kali dilakukan — khususnya login.
 *
 * Endpoint `/sanctum/csrf-cookie` di-serve oleh Sanctum dan hanya men-set
 * cookie XSRF-TOKEN + session cookie (tidak ada body response). Aman dipanggil
 * berulang kali karena idempotent.
 */
export const initCsrf = async (): Promise<void> => {
    await axios.get('/sanctum/csrf-cookie', { withCredentials: true })
}

api.interceptors.request.use((config) => {
    const auth = localStorage.getItem('auth')
    const parsed = auth ? JSON.parse(auth) : null
    const token = parsed?.token

    if (token) {
        config.headers.Authorization = `Bearer ${token}`
    }

    config.headers.Accept = 'application/json'

    return config
}, (error) => {
    return Promise.reject(error)
})

api.interceptors.response.use(
    (response) => {
        resetIdleTimer()
        return response
    },
    async (error) => {
        const status = error.response?.status

        if (status === 401) {
            const code = error.response?.data?.code
            const isSessionExpired = code === 'SESSION_EXPIRED'

            const message = useMessageStore()
            message.setMessage({
                text: isSessionExpired
                    ? 'Sesi Anda telah berakhir karena tidak ada aktivitas. Silakan login kembali.'
                    : 'Sesi tidak valid. Silakan login kembali.',
                timeout: 4000,
                color: 'warning'
            })

            // Hapus session + localStorage tanpa hit API signout (token sudah invalid).
            const auth = useAuthStore()
            auth.clearSession()

            // Hindari redirect dobel jika sudah di halaman signin.
            if (router.currentRoute.value.path !== '/auth/signin') {
                router.push('/auth/signin')
            }
        }

        if (status === 403) {
            console.warn('Akses ditolak')
        }

        return Promise.reject(error)
    }
)