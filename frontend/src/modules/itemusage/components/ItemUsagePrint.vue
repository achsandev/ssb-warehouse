<script lang="ts" setup>
/**
 * Print-out form "Permintaan Pakai Barang" untuk Item Usage.
 *
 * Layout disesuaikan dengan template SSB official:
 *   - Header kiri  : SSB logo + nama perusahaan + alamat
 *   - Header kanan : title + nomor + tanggal
 *   - Info ringkas : Nama Departemen, Jenis Permintaan, dll.
 *   - Tabel detail : kode, nama, aset, project, qty, satuan, harga, subtotal
 *   - Footer       : keterangan agregat + tanda tangan Request/Approval
 *
 * Pola dialog mengikuti `StockOpnamePrint.vue` — open via prop `model`,
 * `window.print()` di-trigger dari tombol Print, lalu CSS `@media print`
 * memastikan hanya area `.print-area` yang tercetak.
 *
 * SECURITY: semua nilai dari data di-render via Vue interpolation `{{ }}`
 * (bukan `v-html`) → HTML escaping otomatis, anti XSS.
 */
import { computed } from 'vue'
import { useTranslate } from '@/composables/useTranslate'
import { formatDate } from '@/utils/date'
import { formatRupiah } from '@/utils/currency'
// Assets
import ssbLogo from '@/assets/logo/ssb_logo.png'
// Icons
import MdiPrinter from '~icons/mdi/printer'
import MdiClose from '~icons/mdi/close'
import MdiInformationOutline from '~icons/mdi/information-outline'

const model = defineModel<boolean>()

const props = defineProps<{
    item: ItemUsageList
}>()

const emit = defineEmits<{
    (e: 'close'): void
}>()

const t = useTranslate()

// ─── Derived rows (precompute supaya template tetap deklaratif) ─────────────
type PrintRow = {
    code: string
    name: string
    aset: string
    project: string
    qty: number
    unit: string
    price: number
    subtotal: number
    description: string
}

const rows = computed<PrintRow[]>(() => {
    const list = props.item?.details ?? []
    const projectName = props.item?.project_name ?? ''

    return list.map((d): PrintRow => {
        const price = toNumber(d.item?.price)
        const qty = toNumber(d.usage_qty ?? d.qty)
        return {
            code:        d.item?.code ?? '-',
            name:        d.item?.name ?? '-',
            // "Aset" pada template SSB merujuk ke part number / asset code internal.
            aset:        d.item?.part_number ?? '-',
            project:     projectName || '-',
            qty,
            unit:        d.unit?.symbol ?? '-',
            price,
            subtotal:    qty * price,
            description: d.description ?? '',
        }
    })
})

const grandTotal = computed<number>(() =>
    rows.value.reduce((sum, r) => sum + r.subtotal, 0),
)

/** Aggregated keterangan dari semua baris detail (skip yang kosong / dup). */
const aggregateKeterangan = computed<string>(() => {
    const seen = new Set<string>()
    return rows.value
        .map((r) => r.description?.trim())
        .filter((s): s is string => !!s && !seen.has(s) && (seen.add(s), true))
        .join(' • ')
})

const headerInfo = computed(() => {
    const ir = props.item?.item_request
    return {
        department: ir?.department_name ?? '-',
        // Field "HM/KM" di template biasa diisi unit_code (untuk equipment) atau
        // wo_number jika tidak ada unit_code. Fallback ke '-' kalau dua-duanya null.
        hmKm: ir?.unit_code || ir?.wo_number || '-',
        woNumber: ir?.wo_number || ir?.request_number || props.item.usage_number,
        date: formatDate(props.item.usage_date as string),
    }
})

// ─── Helpers ─────────────────────────────────────────────────────────────────
function toNumber(value: unknown): number {
    if (value === null || value === undefined || value === '') return 0
    const n = typeof value === 'number' ? value : Number(value)
    return Number.isFinite(n) ? n : 0
}

const handleClose = () => {
    model.value = false
    emit('close')
}

/**
 * Trigger native browser print. Style sementara di-injeksi supaya:
 *   1. Elemen di luar dialog (sidebar, navbar, app shell) tersembunyi.
 *   2. Vuetify dialog yang aslinya `position: fixed + transform + max-height`
 *      diubah jadi static + overflow visible + height auto, sehingga browser
 *      print SEMUA konten (browser akan paginate otomatis kalau lebih dari
 *      1 halaman). Tanpa override ini, scrollable v-card-text akan crop
 *      konten ke area yang visible di viewport.
 *   3. `@page` set landscape A4 + 12mm margin printer (sebagai pelengkap
 *      content padding 8mm di .print-area).
 *
 * Untuk export PDF: user pilih "Save as PDF" di dialog print browser
 * (Chrome/Edge: Destination → Save as PDF; Firefox: tombol "Print"
 * dropdown → "Save to PDF"). Tidak butuh library tambahan — output
 * PDF dari browser sudah konsisten dengan layout yang kita design.
 */
const handlePrint = () => {
    const STYLE_ID = 'item-usage-print-override'
    const style = document.createElement('style')
    style.id = STYLE_ID
    style.textContent = `
        @media print {
            /* margin: 0 menghilangkan area kosong default browser SEKALIGUS
             * area dimana Chrome/Edge biasanya inject header (jam + judul tab)
             * dan footer (URL + nomor halaman). Whitespace dari tepi kertas
             * dipindah ke .print-area padding di bawah supaya konten tetap
             * tidak nempel ke pinggir. */
            @page { size: A4 landscape; margin: 0; }

            /* Hide app shell di luar dialog. */
            body > *:not(.v-overlay-container) { display: none !important; }
            .v-overlay-container .v-overlay:not(.v-overlay--active) { display: none !important; }
            .v-overlay__scrim { display: none !important; }
            .no-print { display: none !important; }

            /* Reset html/body supaya tidak ada outer scroll yang membatasi cetak. */
            html, body {
                height: auto !important;
                overflow: visible !important;
                background: #fff !important;
            }

            /* Vuetify overlay/dialog stack — buang fixed-position + transform +
             * max-height supaya konten flow natural & browser bisa paginate.
             * Tanpa ini, dialog di-render di viewport-position dan crop konten
             * yang harusnya scroll. */
            .v-overlay-container,
            .v-overlay,
            .v-overlay__content,
            .v-overlay__content > .v-card {
                position: static !important;
                inset: auto !important;
                top: auto !important;
                left: auto !important;
                right: auto !important;
                bottom: auto !important;
                transform: none !important;
                max-width: none !important;
                max-height: none !important;
                height: auto !important;
                width: 100% !important;
                margin: 0 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                overflow: visible !important;
            }

            /* v-card-text default punya overflow-y: auto saat scrollable —
             * lepaskan supaya konten print full. */
            .v-overlay__content .v-card-text {
                overflow: visible !important;
                max-height: none !important;
                height: auto !important;
            }
        }
    `
    document.head.appendChild(style)
    window.print()
    // Cleanup async — beberapa browser men-trigger print di event berikutnya.
    setTimeout(() => document.getElementById(STYLE_ID)?.remove(), 0)
}
</script>

<template>
    <v-dialog
        v-model="model"
        max-width="1100"
        scrollable
        @update:model-value="handleClose"
    >
        <v-card rounded="lg">
            <!-- Action bar (hidden on print) -->
            <v-card-title class="d-flex align-center gap-2 px-6 py-4 no-print">
                <v-icon :icon="MdiPrinter" color="primary" class="me-1" />
                <span class="text-h6 font-weight-semibold">
                    {{ t('itemUsagePrintTitle') }}
                </span>
                <v-spacer />
                <v-btn
                    color="primary"
                    variant="elevated"
                    :prepend-icon="MdiPrinter"
                    @click="handlePrint"
                >
                    {{ t('print') }}
                </v-btn>
                <v-btn
                    :icon="MdiClose"
                    variant="text"
                    size="small"
                    :aria-label="t('close')"
                    @click="handleClose"
                />
            </v-card-title>

            <!-- Hint: cara save sebagai PDF (no-print, hanya tampil di UI) -->
            <div class="no-print pdf-hint px-6 pb-2">
                <v-icon :icon="MdiInformationOutline" size="14" class="me-1" />
                {{ t('printSavePdfHint') }}
            </div>

            <v-divider class="no-print" />

            <!-- Printable area -->
            <v-card-text class="print-area pa-6">
                <!-- ════ Header (logo + company info) ════ -->
                <header class="print-header">
                    <div class="header-left">
                        <img :src="ssbLogo" alt="SSB" class="header-logo" />
                    </div>

                    <div class="header-right">
                        <div class="company-name">PT SUMBER SETIA BUDI</div>
                        <div class="company-address">
                            Jl. Protokol No. 45, Kel. Dawi-Dawi, Kec. Pomalaa<br />
                            Kab. Kolaka Sulawesi Tenggara 93562<br />
                            Indonesia
                        </div>
                    </div>
                </header>

                <hr class="header-divider" />

                <!-- ════ Info table + Title block ════ -->
                <section class="info-section">
                    <table class="meta-table">
                        <tbody>
                            <tr>
                                <th>{{ t('printDepartmentName') }}</th>
                                <td>{{ headerInfo.department }}</td>
                            </tr>
                            <tr>
                                <th>{{ t('printRequestKind') }}</th>
                                <td>{{ t('printRequestKindValue') }}</td>
                            </tr>
                            <tr>
                                <th>{{ t('printRequest') }}</th>
                                <td>{{ t('printRequestValue') }}</td>
                            </tr>
                            <tr>
                                <th>HM/KM</th>
                                <td>{{ headerInfo.hmKm }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="title-block">
                        <div class="title-text">{{ t('printTitle') }}</div>
                        <table class="title-meta">
                            <tbody>
                                <tr>
                                    <th>{{ t('printNumber') }}</th>
                                    <td>: {{ headerInfo.woNumber }}</td>
                                </tr>
                                <tr>
                                    <th>{{ t('printDate') }}</th>
                                    <td>: {{ headerInfo.date }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- ════ Detail Table ════ -->
                <table class="detail-table">
                    <thead>
                        <tr>
                            <th class="col-code">{{ t('printItemCode') }}</th>
                            <th class="col-name">{{ t('printItemName') }}</th>
                            <th class="col-asset">{{ t('printAsset') }}</th>
                            <th class="col-project">{{ t('printProject') }}</th>
                            <th class="col-qty">{{ t('qty') }}</th>
                            <th class="col-unit">{{ t('printUnit') }}</th>
                            <th class="col-price">{{ t('printPrice') }}</th>
                            <th class="col-subtotal">{{ t('printSubtotal') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, idx) in rows" :key="idx">
                            <td class="text-mono">{{ row.code }}</td>
                            <td>{{ row.name }}</td>
                            <td class="text-mono">{{ row.aset }}</td>
                            <td>{{ row.project }}</td>
                            <td class="text-end font-weight-bold">{{ row.qty }}</td>
                            <td class="text-center">{{ row.unit }}</td>
                            <td class="text-end text-mono">{{ formatRupiah(row.price) }}</td>
                            <td class="text-end text-mono">{{ formatRupiah(row.subtotal) }}</td>
                        </tr>
                        <tr v-if="rows.length === 0">
                            <td colspan="8" class="text-center text-medium-emphasis py-4">
                                {{ t('noDataAvailable') }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot v-if="rows.length > 0">
                        <tr>
                            <td colspan="7" class="text-end font-weight-bold">
                                {{ t('printTotal') }}
                            </td>
                            <td class="text-end text-mono font-weight-bold">
                                {{ formatRupiah(grandTotal) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>

                <!-- ════ Keterangan + Recipient ════ -->
                <section class="notes-section">
                    <div class="notes-label">{{ t('description') }}</div>
                    <div class="notes-value">
                        {{ aggregateKeterangan || '-' }}
                    </div>

                    <div v-if="item.recipient_name" class="recipient-line">
                        <span class="notes-label">{{ t('recipientName') }}:</span>
                        <span class="recipient-value">{{ item.recipient_name }}</span>
                    </div>
                </section>

                <!-- ════ Signatures ════ -->
                <section class="sign-section">
                    <div class="sign-block">
                        <div class="sign-caption">{{ t('printRequestBy') }}</div>
                        <div class="sign-line"></div>
                        <div class="sign-name">{{ item.created_by_name || '&nbsp;' }}</div>
                        <div class="sign-date">Tgl.</div>
                    </div>
                    <div class="sign-block">
                        <div class="sign-caption">{{ t('printApprovalBy') }}</div>
                        <div class="sign-line"></div>
                        <div class="sign-name">{{ item.updated_by_name || '&nbsp;' }}</div>
                        <div class="sign-date">Tgl.</div>
                    </div>
                </section>
            </v-card-text>
        </v-card>
    </v-dialog>
</template>

<style scoped>
/* ════ PDF hint banner ════ */
.pdf-hint {
    font-size: 0.75rem;
    color: rgba(var(--v-theme-on-surface), 0.6);
    display: flex;
    align-items: center;
    gap: 4px;
    line-height: 1.4;
}

/* ════ Print area chrome ════ */
.print-area {
    background: #fff;
    color: #111;
    font-family: 'Arial', 'Helvetica', sans-serif;
    font-size: 0.78rem;
    line-height: 1.35;
}

/* ════ Header ════
 *
 * Strategi positioning:
 *   - `.print-header` jadi container relative (anchor untuk logo absolute).
 *   - `.header-left` (logo) di-posisikan absolute di kiri-tengah-vertikal.
 *     Tidak ikut flow → tidak menggeser company text.
 *   - `.header-right` (company info) full-width + center-aligned, jadi
 *     teks SELALU di tengah halaman tanpa terpengaruh ada/tidaknya logo
 *     atau ukuran logo.
 */
.print-header {
    position: relative;
    margin-bottom: 8px;
    min-height: 140px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.header-left {
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
}
.header-logo {
    width: 140px;
    height: auto;
    object-fit: contain;
    display: block;
}
.header-right {
    width: 100%;
    text-align: center;
}
.company-name {
    font-size: 1.4rem;
    font-weight: 800;
    letter-spacing: 0.02em;
    color: #1a1a1a;
}
.company-address {
    font-size: 0.78rem;
    color: #4a4a4a;
    line-height: 1.4;
    margin-top: 2px;
}
.header-divider {
    border: none;
    border-top: 1.5px solid #1a1a1a;
    margin: 6px 0 10px;
}

/* ════ Info section ════ */
.info-section {
    display: grid;
    grid-template-columns: 1.4fr 1fr;
    gap: 16px;
    align-items: flex-start;
    margin-bottom: 10px;
}
.meta-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.78rem;
}
.meta-table th,
.meta-table td {
    border: 1px solid #1a1a1a;
    padding: 4px 8px;
    text-align: left;
    vertical-align: middle;
}
.meta-table th {
    background: #f5f5f5;
    font-weight: 600;
    width: 32%;
    color: #1a1a1a;
}

.title-block {
    border: 1px solid #1a1a1a;
    background: #f5f5f5;
    padding: 8px 12px;
    border-radius: 0;
}
.title-text {
    font-size: 1.05rem;
    font-weight: 800;
    text-align: center;
    border-bottom: 1px solid #1a1a1a;
    padding-bottom: 6px;
    margin-bottom: 6px;
    color: #1a1a1a;
}
.title-meta {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.78rem;
}
.title-meta th {
    text-align: left;
    font-weight: 600;
    width: 40%;
    padding: 2px 0;
    color: #1a1a1a;
}
.title-meta td {
    padding: 2px 0;
}

/* ════ Detail table ════ */
.detail-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.74rem;
    margin-top: 6px;
}
.detail-table th,
.detail-table td {
    border: 1px solid #1a1a1a;
    padding: 5px 7px;
    vertical-align: middle;
}
.detail-table thead th {
    background: #1f3c8c;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    font-weight: 700;
    font-size: 0.7rem;
    text-align: center;
}
.detail-table tbody tr:nth-child(even) {
    background: #fafafa;
}
.detail-table tfoot td {
    background: #f0f0f0;
}
.col-code     { width: 80px; }
.col-name     { width: auto; }
.col-asset    { width: 70px; text-align: center; }
.col-project  { width: 140px; }
.col-qty      { width: 50px; text-align: end; }
.col-unit     { width: 60px; text-align: center; }
.col-price    { width: 100px; text-align: end; }
.col-subtotal { width: 110px; text-align: end; }

.text-end { text-align: end; }
.text-center { text-align: center; }
.text-mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
}

/* ════ Notes ════ */
.notes-section {
    margin-top: 14px;
    border: 1px solid #1a1a1a;
    padding: 8px 10px;
    min-height: 60px;
}
.notes-label {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #1a1a1a;
    margin-bottom: 2px;
}
.notes-value {
    font-size: 0.82rem;
    color: #333;
    word-break: break-word;
}
.recipient-line {
    margin-top: 6px;
    padding-top: 6px;
    border-top: 1px dashed #999;
    display: flex;
    align-items: center;
    gap: 8px;
}
.recipient-value {
    font-weight: 600;
    color: #1a1a1a;
}

/* ════ Signatures ════ */
.sign-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    margin-top: 28px;
    justify-items: center;
}
.sign-block {
    text-align: center;
    width: 220px;
}
.sign-caption {
    font-size: 0.78rem;
    font-weight: 600;
    color: #4a4a4a;
    margin-bottom: 50px;
}
.sign-line {
    border-top: 1px solid #1a1a1a;
    width: 100%;
    margin: 0 auto;
}
.sign-name {
    font-size: 0.78rem;
    font-weight: 600;
    margin-top: 4px;
    color: #1a1a1a;
}
.sign-date {
    font-size: 0.72rem;
    color: #666;
    margin-top: 2px;
}

/* ════ Print-specific overrides ════ */
@media print {
    /* Whitespace tunggal dari tepi kertas (sekitar 14-16mm).
     * `@page margin: 0` di handlePrint menghapus area default browser
     * (juga area yang biasa diisi header/footer browser native), jadi
     * SEMUA whitespace dipindah ke padding ini. Cukup besar untuk safe
     * dari unprintable area di laser/inkjet printer. */
    .print-area {
        padding: 14mm 16mm !important;
    }

    /* Force background colors di printer (default browser strip color background). */
    .detail-table thead th,
    .detail-table tbody tr:nth-child(even),
    .meta-table th,
    .title-block,
    .detail-table tfoot td {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}

/* ════ Responsive (preview di layar kecil) ════ */
@media (max-width: 700px) {
    /* Di mobile preview, logo absolute beresiko overlap teks panjang —
     * kembalikan ke flow normal stacked (logo di atas, teks di bawah).
     * Saat print A4 landscape (>1100px), absolute centering tetap aktif. */
    .print-header {
        flex-direction: column;
        gap: 8px;
        min-height: 0;
    }
    .header-left {
        position: static;
        transform: none;
    }
    .header-logo {
        width: 100px;
    }
    .company-name {
        font-size: 1.1rem;
    }
    .info-section {
        grid-template-columns: 1fr;
    }
    .sign-section {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    .detail-table {
        font-size: 0.7rem;
    }
}
</style>
