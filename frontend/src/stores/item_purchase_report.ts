import { createReportStore } from '@/modules/reports/_shared/useReportStore'

export interface ItemPurchaseReportRow {
    po_number: string
    po_date: string
    item_name: string
    unit: string
    supplier_name: string
    qty: number
    price: number
    total: number
    status: string
    created_by_name: string
    created_at: string
}

export const useItemPurchaseReportStore = createReportStore<ItemPurchaseReportRow>(
    'itemPurchaseReport',
    '/reports/item_purchase',
)
