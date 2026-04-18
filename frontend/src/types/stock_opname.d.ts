type StockOpnameDetail = {
    uid?: string
    stock_unit_uid?: string | null
    item_name: string
    unit_symbol: string
    warehouse_name: string
    rack_name: string | null
    system_qty: number
    actual_qty: number | null
    difference_qty: number | null
    notes: string | null
    created_at?: string | null
    updated_at?: string | null
    created_by_name?: string | null
    updated_by_name?: string | null
}

type StockOpnameList = {
    uid: string
    opname_number: string
    opname_date: Date | string
    notes: string | null
    status: string
    details: StockOpnameDetail[] | null
    created_at: Date | string
    updated_at: Date | string | null
    created_by_name: string
    updated_by_name: string | null
}

type StockOpnameForm = {
    uid?: string
    opname_date: Date | string
    notes: string | null
    status?: string
    details: Array<{
        stock_unit_uid: string | null
        notes: string | null
    }>
}

type StockOpnameCountForm = {
    details: Array<{
        uid: string
        actual_qty: number | null
        notes: string | null
    }>
}
