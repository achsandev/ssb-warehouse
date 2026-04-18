type WarehouseCashRequestList = {
    uid: string
    request_number: string
    request_date: Date | string
    warehouse_uid: string | null
    warehouse_name: string
    cash_balance: number
    amount: number
    attachment_path: string | null
    attachment_name: string | null
    attachment_url: string | null
    notes: string | null
    status: string
    created_at: Date | string
    updated_at: Date | string | null
    created_by_name: string
    updated_by_name: string | null
}

type WarehouseCashRequestForm = {
    request_date: Date | string
    warehouse_uid: string | null
    amount: number | null
    notes: string | null
    status?: string
    attachment?: File | File[] | string | undefined
}

type WarehouseOption = {
    uid: string
    name: string
    cash_balance: number
}
