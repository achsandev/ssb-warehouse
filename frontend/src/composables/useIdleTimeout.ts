import { onBeforeUnmount, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useMessageStore } from '@/stores/message'
import { router } from '@/routes'

const IDLE_MINUTES = 120
const IDLE_MS = IDLE_MINUTES * 60 * 1000
const CHECK_INTERVAL_MS = 60 * 1000
const LAST_ACTIVITY_KEY = 'auth_last_activity'

const ACTIVITY_EVENTS = ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll'] as const

export function useIdleTimeout() {
    const auth = useAuthStore()
    const message = useMessageStore()

    let intervalId: number | undefined

    const touchActivity = () => {
        if (!auth.isAuthenticated) return
        localStorage.setItem(LAST_ACTIVITY_KEY, String(Date.now()))
    }

    const checkIdle = async () => {
        if (!auth.isAuthenticated) return

        const last = Number(localStorage.getItem(LAST_ACTIVITY_KEY) || 0)
        if (!last) {
            touchActivity()
            return
        }

        if (Date.now() - last > IDLE_MS) {
            // Hapus session + localStorage tanpa hit API (token kemungkinan sudah expired di server).
            auth.clearSession()
            await message.setMessage({
                text: 'Sesi Anda telah berakhir karena tidak ada aktivitas. Silakan login kembali.',
                timeout: 4000,
                color: 'warning'
            })
            if (router.currentRoute.value.path !== '/auth/signin') {
                router.push('/auth/signin')
            }
        }
    }

    onMounted(() => {
        touchActivity()

        ACTIVITY_EVENTS.forEach(ev => {
            window.addEventListener(ev, touchActivity, { passive: true })
        })

        intervalId = window.setInterval(checkIdle, CHECK_INTERVAL_MS)
    })

    onBeforeUnmount(() => {
        ACTIVITY_EVENTS.forEach(ev => {
            window.removeEventListener(ev, touchActivity)
        })
        if (intervalId) window.clearInterval(intervalId)
    })

    return { touchActivity, checkIdle }
}

export function resetIdleTimer() {
    localStorage.setItem(LAST_ACTIVITY_KEY, String(Date.now()))
}

export function clearIdleTimer() {
    localStorage.removeItem(LAST_ACTIVITY_KEY)
}
