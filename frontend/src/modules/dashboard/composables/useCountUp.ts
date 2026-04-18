import { ref, watch, type Ref } from 'vue'

/**
 * Animasi count-up angka dari 0 (atau nilai sebelumnya) ke target baru.
 * Memakai requestAnimationFrame dengan ease-out cubic untuk gerakan natural.
 *
 * @param target  Nilai target (reactive).
 * @param options duration ms (default 900), decimals.
 */
export function useCountUp(target: Ref<number>, options: { duration?: number; decimals?: number } = {}) {
    const duration = options.duration ?? 900
    const decimals = options.decimals ?? 0
    const display = ref(0)

    let frame: number | null = null

    const animate = (from: number, to: number) => {
        if (frame !== null) cancelAnimationFrame(frame)
        const start = performance.now()
        const delta = to - from

        const step = (now: number) => {
            const elapsed = now - start
            const progress = Math.min(elapsed / duration, 1)
            // easeOutCubic
            const eased = 1 - Math.pow(1 - progress, 3)
            const value = from + delta * eased
            display.value = decimals > 0 ? Number(value.toFixed(decimals)) : Math.round(value)

            if (progress < 1) {
                frame = requestAnimationFrame(step)
            } else {
                frame = null
            }
        }

        frame = requestAnimationFrame(step)
    }

    watch(
        target,
        (next, prev) => {
            animate(typeof prev === 'number' ? prev : 0, Number(next) || 0)
        },
        { immediate: true },
    )

    return { display }
}
