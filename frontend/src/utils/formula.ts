/**
 * Evaluator formula dengan substitusi karakter `x` → nilai parameter.
 *
 * Kontrak:
 *  - Setiap karakter `x` (case-insensitive) pada string formula DIGANTIKAN
 *    langsung dengan nilai parameter `x`. Penggantian dilakukan dengan
 *    membungkus nilai dalam tanda kurung agar prioritas operator tidak rusak
 *    saat `x` bertanda negatif.
 *  - Karakter `%` di belakang angka diperlakukan sebagai pembagian /100.
 *    Contoh: "x * 11%" dengan x=100 → "(100) * (11/100)" = 11.
 *
 * Keamanan:
 *  - Input divalidasi terhadap whitelist karakter sebelum dievaluasi.
 *  - Memakai `math.evaluate` (bukan `eval`/`new Function`). mathjs sandboxed
 *    terhadap DOM/Window/globals.
 *  - Tidak melempar saat input invalid — kembalikan `fallback` (default 0).
 *    Aman untuk dipanggil realtime dari template.
 */
import { create, all, type MathJsInstance } from 'mathjs'

// ─── mathjs instance (singleton modul) ──────────────────────────────────────
const math: MathJsInstance = create(all)

// ─── Constants ──────────────────────────────────────────────────────────────
/** Whitelist karakter yang boleh muncul di formula (defense-in-depth). */
const ALLOWED_FORMULA = /^[0-9xX+\-*/().% ]*$/

/** Match angka (int/desimal) diikuti `%`, untuk diubah ke `(n/100)`. */
const PERCENT_TOKEN = /(\d+(?:\.\d+)?)\s*%/g

/** Match variabel `x` (case-insensitive) untuk disubstitusi dengan nilai. */
const X_TOKEN = /[xX]/g

// ─── Types ──────────────────────────────────────────────────────────────────
export interface EvaluateFormulaParams {
    /** Nilai variable `x` pada rumus. Non-number / NaN → dianggap 0. */
    x: number | string | null | undefined
    /** Rumus, mis. `x * 11%`, `(x + 100) * 0.11`, atau `12`. */
    formula: string | null | undefined
    /** Nilai fallback saat formula kosong/invalid. Default 0. */
    fallback?: number
    /**
     * Jumlah desimal untuk membulatkan hasil. Default 0 (bulat ke integer
     * terdekat — cocok untuk nilai rupiah). Gunakan 2 untuk presisi sen dsb.
     */
    decimals?: number
}

// ─── Internals ──────────────────────────────────────────────────────────────
/**
 * Normalisasi ekspresi:
 *  1. `11%` → `(11/100)` agar mathjs mengenalinya.
 *  2. Setiap `x` / `X` diganti langsung dengan `(value)`.
 *
 * Urutan penting — substitusi `%` dulu agar `x%` tidak salah tangkap
 * sebagai `(value)%` yang invalid.
 */
const normalize = (formula: string, xValue: number): string => {
    // `Number.isFinite` memastikan hasil serialize tetap valid untuk mathjs.
    const xLiteral = Number.isFinite(xValue) ? `(${xValue})` : '(0)'
    return formula
        .replace(PERCENT_TOKEN, '($1/100)')
        .replace(X_TOKEN, xLiteral)
}

const toSafeNumber = (value: unknown, fallback: number): number => {
    const n = typeof value === 'number' ? value : Number(value)
    return Number.isFinite(n) ? n : fallback
}

// ─── Public API ─────────────────────────────────────────────────────────────
/**
 * Evaluate formula aritmatika dengan variable `x`.
 * Tidak melempar — input invalid / error mathjs → kembalikan `fallback`.
 */
export const evaluateFormula = (params: EvaluateFormulaParams): number => {
    const { x, formula, fallback = 0, decimals = 0 } = params

    const raw = (formula ?? '').toString().trim()
    if (!raw) return fallback
    if (!ALLOWED_FORMULA.test(raw)) return fallback

    const xValue = toSafeNumber(x, 0)

    try {
        const expr = normalize(raw, xValue)
        const result = math.evaluate(expr)
        const n = toSafeNumber(result, fallback)

        // Bulatkan ke `decimals` desimal (round-half-away-from-zero default JS).
        const factor = 10 ** Math.max(0, Math.trunc(decimals))
        return Math.round(n * factor) / factor
    } catch {
        return fallback
    }
}

/**
 * Validasi cepat apakah formula valid secara syntaksis (tanpa benar-benar
 * menghitung nilainya). Berguna untuk validator form.
 */
export const isValidFormula = (formula: string | null | undefined): boolean => {
    const raw = (formula ?? '').toString().trim()
    if (!raw) return false
    if (!ALLOWED_FORMULA.test(raw)) return false

    try {
        // Substitusi dengan nilai dummy `0` — cukup untuk uji syntax.
        math.parse(normalize(raw, 0))
        return true
    } catch {
        return false
    }
}
