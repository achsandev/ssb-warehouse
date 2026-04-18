import { createReportStore } from '@/modules/reports/_shared/useReportStore'

export interface DemandRateReportRow {
    part_number: string
    item_name: string
    unit: string
    total_requests: number
    distinct_requests: number
    total_qty: number
}

export const useDemandRateReportStore = createReportStore<DemandRateReportRow>(
    'demandRateReport',
    '/reports/demand_rate',
)
