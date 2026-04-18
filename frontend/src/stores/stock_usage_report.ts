import { createReportStore } from '@/modules/reports/_shared/useReportStore'

export interface StockUsageReportRow {
    usage_number: string
    usage_date: string
    project_name: string
    item_name: string
    unit: string
    qty: number
    usage_qty: number
    description: string
    status: string
    created_by_name: string
    created_at: string
}

export const useStockUsageReportStore = createReportStore<StockUsageReportRow>(
    'stockUsageReport',
    '/reports/stock_usage',
)
