/**
 * Format `Date | string` ke `YYYY-MM-DD` (UTC). Aman dari nilai invalid:
 *   - null / undefined / '' → ''
 *   - string yang tidak bisa di-parse → ''  (dulu throw RangeError dari
 *     Date.toISOString() — sumber bug "Invalid time value")
 *
 * Mengembalikan string kosong (bukan '-' atau placeholder lain) supaya
 * caller bebas memutuskan fallback display sendiri (`?? '-'`, `|| 'N/A'`).
 */
export const formatDate = (value?: Date | string | null): string => {
    if (value === null || value === undefined || value === '') return ''

    const date = value instanceof Date ? value : new Date(value)
    if (Number.isNaN(date.getTime())) return ''

    return date.toISOString().split('T')[0]
}