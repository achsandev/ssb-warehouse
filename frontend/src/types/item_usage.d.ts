type ItemUsageDetail = {
    uid?: string
    item: { uid: string; name: string; code: string } | null
    unit: { uid: string; name: string; symbol: string } | null
    qty: number
    usage_qty: number
    description: string
}

type ItemUsageList = {
    uid: string
    usage_number: string
    usage_date: Date | string
    item_request: { uid: string; request_number: string } | null
    project_name: string | null
    status: string
    details: ItemUsageDetail[] | null
    created_at: Date | string
    updated_at: Date | string
    created_by_name: string
    updated_by_name: string
}

type ItemUsageForm = {
    uid?: string
    item_request_uid: string | null
    usage_date: Date | string
    project_name: string | null
    status?: string
    details: Array<{
        item_uid: string | null
        unit_uid: string | null
        qty: number
        usage_qty: number | null
        description: string
    }>
}
