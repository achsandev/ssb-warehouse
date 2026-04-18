type PoApprovalLog = {
    uid: string
    approval_level: number
    role_name: string
    status: 'Approved' | 'Rejected'
    notes: string | null
    approved_by_name: string
    created_at: Date | string
}

type PurchaseOrderDetail = {
    uid: string,
    purchase_order: {
        uid: string
        po_number: string
        po_date: string
        project_name: string
    } | null,
    item: {
        uid: string,
        code: string,
        name: string
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
    created_at: Date | string,
    updated_at: Date | string,
    created_by_name: string,
    updated_by_name: string
}

type PurchaseOrderList = {
    uid: string,
    item_request: {
        uid: string,
        request_number: string,
        project_name: string
    } | null,
    po_number: string,
    po_date: Date | string,
    project_name: string,
    total_amount: number,
    status: string,
    details: PurchaseOrderDetail[] | null,
    approval_logs: PoApprovalLog[] | null,
    created_at: Date | string,
    updated_at: Date | string,
    created_by_name: string,
    updated_by_name: string,
}

type PurchaseOrderForm = {
    uid?: string,
    item_request_uid: string | null,
    po_date: Date | string,
    project_name: string,
    total_amount?: number,
    status?: string,
    details: Array<{
        item_uid: string,
        unit_uid: string,
        supplier_uid: string,
        tax_type_uid?: string | null,
        tax_amount?: number | null,
        dpp_formula_uid?: string | null,
        dpp_amount?: number | null,
        discount?: number | null,
        discount_amount?: number | null,
        qty: number,
        price: number,
        total?: number
    }>
}