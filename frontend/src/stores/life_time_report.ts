import { createReportStore } from '@/modules/reports/_shared/useReportStore'

export interface LifeTimeReportRow {
    item_code: string
    item_name: string
    unit: string
    first_receipt_date: string
    last_receipt_date: string
    days_in_stock: number
}

export const useLifeTimeReportStore = createReportStore<LifeTimeReportRow>(
    'lifeTimeReport',
    '/reports/life_time',
)
