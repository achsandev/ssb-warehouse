import { createReportStore } from '@/modules/reports/_shared/useReportStore'

export interface ItemReceiptReportRow {
    receipt_number: string
    receipt_date: string
    po_number: string
    item_name: string
    unit: string
    supplier_name: string
    qty: number
    qty_received: number
    price: number
    total: number
    status: string
    created_by_name: string
    created_at: string
}

export const useItemReceiptReportStore = createReportStore<ItemReceiptReportRow>(
    'itemReceiptReport',
    '/reports/item_receipt',
)
