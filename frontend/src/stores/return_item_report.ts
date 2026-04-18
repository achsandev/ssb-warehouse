import { createReportStore } from '@/modules/reports/_shared/useReportStore'

export interface ReturnItemReportRow {
    return_number: string
    return_date: string
    po_number: string
    project_name: string
    item_name: string
    unit: string
    qty: number
    return_qty: number
    description: string
    status: string
    created_by_name: string
    created_at: string
}

export const useReturnItemReportStore = createReportStore<ReturnItemReportRow>(
    'returnItemReport',
    '/reports/return_item',
)
