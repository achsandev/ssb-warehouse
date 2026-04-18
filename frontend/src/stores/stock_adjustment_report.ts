import { createReportStore } from '@/modules/reports/_shared/useReportStore'

export interface StockAdjustmentReportRow {
    adjustment_number: string
    adjustment_date: string
    item_name: string
    unit: string
    warehouse_name: string
    rack_name: string
    adjustment_qty: number
    stock_opname_number: string
    notes: string
    status: string
    created_by_name: string
    created_at: string
}

export const useStockAdjustmentReportStore = createReportStore<StockAdjustmentReportRow>(
    'stockAdjustmentReport',
    '/reports/stock_adjustment',
)
