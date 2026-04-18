type CashPurchasePoDetail = {
    item_name:   string | null
    unit_symbol: string | null
    qty:         number
    price:       number
    total:       number
}

type CashPurchaseList = {
    uid:             string
    purchase_number: string
    purchase_date:   Date | string
    warehouse_uid:   string | null
    warehouse_name:  string
    cash_balance:    number
    po_uid:          string | null
    po_number:       string
    po_total_amount: number | null
    po_details:      CashPurchasePoDetail[]
    notes:           string | null
    status:          string
    created_at:      Date | string
    updated_at:      Date | string | null
    created_by_name: string
    updated_by_name: string | null
}

type CashPurchaseForm = {
    purchase_date: Date | string
    warehouse_uid: string | null
    po_uid:        string | null
    notes:         string | null
    status?:       string
}
