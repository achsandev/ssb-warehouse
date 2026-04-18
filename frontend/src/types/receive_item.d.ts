type ReceiveItemDetail = {
    uid: string,
    item: {
        uid: string,
        name: string,
        code: string
    } | null,
    unit: {
        uid: string,
        name: string,
        symbol: string
    } | null,
    supplier: {
        uid: string,
        name: string
    } | null,
    qty: number,
    price: number,
    total: number,
    qty_received: number,
    created_at?: Date | string,
    updated_at?: Date | string,
    created_by_name?: string,
    updated_by_name?: string
}

type ReceiveItemList = {
    uid: string,
    receipt_number: string,
    receipt_date: Date | string,
    project_name: string,
    purchase_order: {
        'uid': string,
        'po_number': string
    } | null,
    warehouse: {
        uid: string,
        name: string
    } | null,
    additional_info: string,
    shipping_cost: number,
    status: string,
    reject_reason?: string | null,
    details: ReceiveItemDetail[] | null,
    created_at: Date | string,
    updated_at: Date | string,
    created_by_name: string,
    updated_by_name: string
}

type ReceiveItemForm = {
    uid?: string,
    receipt_date: Date | string,
    project_name: string,
    purchase_order_id: string | null,
    warehouse_id: string | null,
    status?: string,
    additional_info?: string,
    shipping_cost?: number,
    reject_reason?: string | null,
    details: Array<{
        item_id: string | null,
        unit_id: string | null,
        supplier_id: string | null,
        qty: number,
        price: number,
        total: number,
        qty_received: number
    }>
}