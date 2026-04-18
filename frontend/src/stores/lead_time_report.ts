import { createReportStore } from '@/modules/reports/_shared/useReportStore'

export interface LeadTimeReportRow {
    po_number: string
    po_date: string
    first_receipt_date: string
    lead_days: number
    status: string
    created_by_name: string
}

export const useLeadTimeReportStore = createReportStore<LeadTimeReportRow>(
    'leadTimeReport',
    '/reports/lead_time',
)
