import type { ExportColumnOption } from './components/ExportDialog.vue'

/**
 * Registry kolom export per-report. Key column harus persis sama dengan
 * `COLUMN_DEFS` di controller BE (whitelist enforced).
 *
 * Pola tipikal:
 *  - `default: false` → kolom opsional, off by default.
 *  - `locked: true`   → kolom wajib, user tidak bisa uncheck.
 *
 * Label dipakai apa adanya di checkbox; bisa di-translate via key i18n
 * jika ingin multi-bahasa (lihat `_shared/useReportColumns.ts`).
 */

export const stockUsageColumns: ExportColumnOption[] = [
    { key: 'usage_date',      label: 'Tanggal' },
    { key: 'item_code',       label: 'Kode Barang' },
    { key: 'item_name',       label: 'Nama Barang' },
    { key: 'usage_qty',       label: 'Kuantitas' },
    { key: 'usage_number',    label: 'Nomor #' },
    { key: 'department_name', label: 'Nama Departemen' },
    { key: 'project_name',    label: 'Nama Proyek' },
]

export const stockAdjustmentColumns: ExportColumnOption[] = [
    { key: 'adjustment_date',   label: 'Tanggal' },
    { key: 'item_code',         label: 'Kode Barang' },
    { key: 'item_name',         label: 'Nama Barang' },
    { key: 'adjustment_qty',    label: 'Kuantitas' },
    { key: 'adjustment_number', label: 'Nomor #' },
    { key: 'department_name',   label: 'Nama Departemen' },
    { key: 'project_name',      label: 'Nama Proyek' },
]

export const itemPurchaseColumns: ExportColumnOption[] = [
    { key: 'po_date',         label: 'Tanggal' },
    { key: 'po_number',       label: 'Nomor #' },
    { key: 'supplier_name',   label: 'Pemasok' },
    { key: 'item_code',       label: 'Kode #' },
    { key: 'item_name',       label: 'Nama Barang' },
    { key: 'qty',             label: 'Kuantitas' },
    { key: 'price',           label: 'Harga' },
    { key: 'total',           label: 'Total Harga' },
    { key: 'project_name',    label: 'Nama Proyek' },
    { key: 'department_name', label: 'Nama Departemen' },
]

export const itemReceiptColumns: ExportColumnOption[] = [
    { key: 'item_code',      label: 'Kode #' },
    { key: 'item_name',      label: 'Nama Barang' },
    { key: 'qty_received',   label: 'Kuantitas' },
    { key: 'unit',           label: 'Satuan' },
    { key: 'receipt_number', label: 'Nomor #' },
    { key: 'receipt_date',   label: 'Tanggal' },
    { key: 'supplier_name',  label: 'Pemasok' },
    { key: 'qty',            label: 'Kuantitas Dipesan' },
    { key: 'total',          label: 'Total Harga' },
]

export const returnItemColumns: ExportColumnOption[] = [
    { key: 'no',            label: 'No', hint: 'Nomor urut otomatis' },
    { key: 'return_date',   label: 'Tanggal Return' },
    { key: 'item_name',     label: 'Nama Barang' },
    { key: 'item_code',     label: 'Kode Barang' },
    { key: 'return_qty',    label: 'Jumlah Return' },
    { key: 'unit',          label: 'Satuan' },
    { key: 'description',   label: 'Alasan Return' },
    { key: 'supplier_name', label: 'Nama Pemasok' },
]

export const leadTimeColumns: ExportColumnOption[] = [
    { key: 'no',                 label: 'No', hint: 'Nomor urut otomatis' },
    { key: 'item_name',          label: 'Nama Barang' },
    { key: 'item_code',          label: 'Kode Barang' },
    { key: 'po_date',            label: 'Tanggal PO' },
    { key: 'first_receipt_date', label: 'Tanggal Diterima' },
    { key: 'lead_days',          label: 'Lead Time (hari)' },
    { key: 'description',        label: 'Keterangan' },
]

export const lifeTimeColumns: ExportColumnOption[] = [
    { key: 'item_code',          label: 'Item Code' },
    { key: 'item_name',          label: 'Item Name' },
    { key: 'unit',               label: 'Unit' },
    { key: 'first_receipt_date', label: 'First Receipt Date' },
    { key: 'last_receipt_date',  label: 'Last Receipt Date' },
    { key: 'days_in_stock',      label: 'Days In Stock' },
]

/**
 * Demand rate punya pivot bulan dinamis. `months_pivot` direpresentasikan
 * sebagai 1 entry yang kalau dipilih → BE expand jadi N kolom (1 per bulan).
 */
export const demandRateColumns: ExportColumnOption[] = [
    { key: 'part_number',  label: 'Part System', locked: true },
    { key: 'desc',         label: "Desc'", locked: true },
    { key: 'part_cat',     label: "Part Cat'g" },
    { key: 'months_pivot', label: 'Call & Demand (Bulanan)', locked: true, hint: 'Pivot frekuensi per-bulan' },
    { key: 'grand_total',  label: 'Grand Total' },
    { key: 'rata2',        label: 'Rata-rata Konsumsi' },
    { key: 'call',         label: 'Call' },
    { key: 'moving_cat',   label: 'Moving Category' },
    { key: 'min',          label: 'Min' },
    { key: 'max',          label: 'Max' },
    { key: 'ss',           label: 'SS', hint: 'Safety Stock' },
]
