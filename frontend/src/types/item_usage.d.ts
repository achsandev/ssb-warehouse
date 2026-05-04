type ItemUsageDetail = {
    uid?: string
    item: {
        uid: string
        name: string
        code: string
        part_number?: string | null
        price?: number | string | null
    } | null
    unit: { uid: string; name: string; symbol: string } | null
    qty: number
    usage_qty: number
    description: string
}

type ItemUsageItemRequestRef = {
    uid: string
    request_number: string
    department_name?: string | null
    unit_code?: string | null
    wo_number?: string | null
    is_project?: boolean
}

type ItemUsageList = {
    uid: string
    usage_number: string
    usage_date: Date | string
    item_request: ItemUsageItemRequestRef | null
    project_name: string | null
    recipient_name: string | null
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
    recipient_name: string | null
    status?: string
    details: Array<{
        item_uid: string | null
        unit_uid: string | null
        qty: number
        usage_qty: number | null
        description: string
    }>
}
