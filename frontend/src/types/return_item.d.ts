type ReturnItemDetail = {
    uid?: string
    item: { uid: string; name: string; code: string } | null
    unit: { uid: string; name: string; symbol: string } | null
    qty: number
    return_qty: number | null
    description: string
}

type ReturnItemList = {
    uid: string
    return_number: string
    return_date: Date | string
    project_name: string | null
    purchase_order: { uid: string; po_number: string } | null
    status: string
    details: ReturnItemDetail[] | null
    created_at: Date | string
    updated_at: Date | string
    created_by_name: string
    updated_by_name: string
}

type ReturnItemForm = {
    uid?: string
    purchase_order_uid: string | null
    return_date: Date | string
    project_name: string | null
    status?: string
    details: Array<{
        item_uid: string | null
        unit_uid: string | null
        qty: number
        return_qty: number | null
        description: string
    }>
}
