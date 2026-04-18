/**
 * Helpers murni untuk perhitungan jumlah pajak pada detail Purchase Order.
 *
 * Dipisah dari komponen form:
 *  - Agar mudah di-test secara unit.
 *  - Agar logika perhitungan dapat digunakan ulang (mis. di backoffice report,
 *    atau proses batch perhitungan server-side via SSR).
 *  - Menjaga komponen UI tetap ringan & deklaratif.
 */
import { evaluateFormula } from '@/utils/formula'

// ─── Types ──────────────────────────────────────────────────────────────────
export type TaxFormulaType = 'formula' | 'percentage' | 'manual'

export interface TaxMeta {
    uid: string
    name: string
    formula_type: TaxFormulaType
    formula: string | null
    /** Flag dari master TaxType: true berarti baris pakai DPP. */
    uses_dpp?: boolean
}

/** Meta formula DPP dari master Setting DPP Formula. */
export interface DppFormulaMeta {
    uid: string
    name: string
    formula: string
}

// ─── Primitives ─────────────────────────────────────────────────────────────
/** Konversi aman ke number; non-finite / invalid → 0. */
const toSafeNumber = (value: unknown): number => {
    const n = typeof value === 'number' ? value : Number(value)
    return Number.isFinite(n) ? n : 0
}

/** Subtotal baris = qty × price (dijamin number finite). */
export const subtotalOf = (qty: unknown, price: unknown): number =>
    toSafeNumber(qty) * toSafeNumber(price)

/** True saat tax type yang dipilih bertipe `manual` (value diinput user). */
export const isManualTax = (meta: TaxMeta | null | undefined): boolean =>
    meta?.formula_type === 'manual'

/**
 * Hitung jumlah pajak otomatis untuk tipe `percentage` dan `formula`.
 *  - `percentage`: subtotal × rate/100, dibulatkan ke 2 desimal (presisi cent).
 *  - `formula`   : evaluasi rumus mathjs dengan `x = subtotal`.
 *  - `manual`    : null — caller wajib menghormati nilai yang diinput user.
 *  - meta null   : 0 (tidak ada pajak).
 */
export const computeAutoTaxAmount = (
    qty: unknown,
    price: unknown,
    meta: TaxMeta | null | undefined,
): number | null => {
    if (!meta) return 0

    const subtotal = subtotalOf(qty, price)

    switch (meta.formula_type) {
        case 'percentage': {
            const rate = toSafeNumber(meta.formula)
            if (rate <= 0) return 0

            // Round-half-to-even agar konsisten lintas platform.
            return Math.round(subtotal * rate) / 100
        }

        case 'formula':
            return evaluateFormula({ x: subtotal, formula: meta.formula, fallback: 0 })

        case 'manual':
        default:
            return null
    }
}

/**
 * Hitung DPP (Dasar Pengenaan Pajak) dari subtotal berdasarkan formula DPP
 * yang dipilih. Nilai `x` pada formula DPP = subtotal baris.
 *
 *  - `subtotal <= 0` atau formula kosong → 0 (aman untuk ditampilkan di input).
 *  - Dibulatkan ke integer terdekat (presisi rupiah). Gunakan override bila
 *    diperlukan presisi sen.
 */
export const computeDppAmount = (
    qty: unknown,
    price: unknown,
    formula: DppFormulaMeta | null | undefined,
    decimals = 0,
): number => {
    if (!formula?.formula) return 0
    const subtotal = subtotalOf(qty, price)
    if (subtotal <= 0) return 0

    return evaluateFormula({
        x:        subtotal,
        formula:  formula.formula,
        fallback: 0,
        decimals,
    })
}

/** True saat tax type yang dipilih menggunakan DPP. */
export const taxUsesDpp = (meta: TaxMeta | null | undefined): boolean =>
    !!meta?.uses_dpp

/**
 * Hitung nilai diskon = subtotal × (discount / 100).
 *
 *  - `discount` diperlakukan sebagai persen (input user: 10 = 10%).
 *  - Clamped ke range [0, 100] untuk mencegah nilai tidak masuk akal.
 *  - Subtotal ≤ 0 atau discount ≤ 0 → 0.
 *  - Pembulatan dilakukan pada hasil akhir ke integer rupiah terdekat agar
 *    konsisten dengan tampilan `BaseCurrencyInput` (IDR tanpa desimal).
 */
export const computeDiscountAmount = (
    qty: unknown,
    price: unknown,
    discount: unknown,
): number => {
    const subtotal = subtotalOf(qty, price)
    if (subtotal <= 0) return 0

    const raw = toSafeNumber(discount)
    if (raw <= 0) return 0

    const clamped = Math.min(raw, 100)
    return Math.round((subtotal * clamped) / 100)
}
