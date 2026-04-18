type StockAdjustmentDetailItem = {
    uid?: string
    stock_unit_uid?: string | null
    item_name: string
    unit_symbol: string
    warehouse_name: string
    rack_name: string | null
    adjustment_qty: number
    notes: string | null
    created_at?: string | null
    updated_at?: string | null
    created_by_name?: string | null
    updated_by_name?: string | null
}

type StockAdjustmentList = {
    uid: string
    adjustment_number: string
    adjustment_date: Date | string
    stock_opname_uid: string | null
    stock_opname_number: string | null
    notes: string | null
    status: string
    details: StockAdjustmentDetailItem[] | null
    created_at: Date | string
    updated_at: Date | string | null
    created_by_name: string
    updated_by_name: string | null
}

type StockAdjustmentForm = {
    uid?: string
    adjustment_date: Date | string
    stock_opname_uid?: string | null
    notes: string | null
    status?: string
    details: Array<{
        stock_unit_uid: string | null
        adjustment_qty: number | null
        notes: string | null
    }>
}
