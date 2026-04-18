/**
 * Keypress + paste handler untuk input formula pajak.
 *
 * Mode:
 *  - 'formula'   : 0-9, x, + - * / ( ), ., %, spasi. `%` wajib didahului digit.
 *  - 'percentage': 0-9 dan . (titik desimal). Hanya satu `.` yang diperbolehkan.
 */

export type FormulaMode = 'formula' | 'percentage'

const ALLOWED_BY_MODE: Record<FormulaMode, RegExp> = {
    formula:    /^[0-9+\-*/().% x]$/,
    percentage: /^[0-9.]$/,
}

export const DISALLOWED_BY_MODE: Record<FormulaMode, RegExp> = {
    formula:    /[^0-9+\-*/().% x]/g,
    percentage: /[^0-9.]/g,
}

const NAV_KEYS = new Set([
    'Backspace',
    'Delete',
    'ArrowLeft',
    'ArrowRight',
    'ArrowUp',
    'ArrowDown',
    'Tab',
    'Home',
    'End',
    'Enter',
])

/** Blok char tidak valid saat user mengetik. */
export const formulaKeypress = (event: KeyboardEvent, mode: FormulaMode = 'formula'): void => {
    const key = event.key
    if (NAV_KEYS.has(key) || event.ctrlKey || event.metaKey) return

    // Whitelist dasar per mode.
    if (!ALLOWED_BY_MODE[mode].test(key)) {
        event.preventDefault()

        return
    }

    const input = event.target as HTMLInputElement | null
    const value = input?.value ?? ''
    const pos   = input?.selectionStart ?? value.length
    const prev  = value.charAt(pos - 1)

    if (mode === 'percentage') {
        // Hanya boleh satu `.` di seluruh string.
        if (key === '.' && value.includes('.')) {
            event.preventDefault()
        }

        return
    }

    // Mode formula: `%` wajib tepat setelah digit.
    if (key === '%' && !/\d/.test(prev)) {
        event.preventDefault()
    }
}

/** Bersihkan clipboard text dari char tidak valid sebelum inject ke input. */
export const formulaPaste = (event: ClipboardEvent, mode: FormulaMode = 'formula'): void => {
    const text = event.clipboardData?.getData('text')
    if (text == null) return

    let sanitized = text.replace(DISALLOWED_BY_MODE[mode], '')

    // Mode percentage: pertahankan hanya `.` pertama.
    if (mode === 'percentage') {
        const firstDot = sanitized.indexOf('.')
        if (firstDot !== -1) {
            sanitized = sanitized.slice(0, firstDot + 1) + sanitized.slice(firstDot + 1).replace(/\./g, '')
        }
    }

    if (sanitized === text) return

    event.preventDefault()
    const input = event.target as HTMLInputElement | null
    if (!input) return

    const start = input.selectionStart ?? input.value.length
    const end   = input.selectionEnd ?? start
    const next  = input.value.slice(0, start) + sanitized + input.value.slice(end)
    input.value = next
    input.setSelectionRange(start + sanitized.length, start + sanitized.length)
    input.dispatchEvent(new Event('input', { bubbles: true }))
}

/** Sanitizer untuk value yang sudah ada di state (mis. saat load data). */
export const sanitizeFormulaValue = (value: string, mode: FormulaMode = 'formula'): string => {
    let cleaned = value.replace(DISALLOWED_BY_MODE[mode], '')
    if (mode === 'percentage') {
        const firstDot = cleaned.indexOf('.')
        if (firstDot !== -1) {
            cleaned = cleaned.slice(0, firstDot + 1) + cleaned.slice(firstDot + 1).replace(/\./g, '')
        }
    }

    return cleaned
}
