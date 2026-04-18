type ItemRequestDetail = {
    uid: string,
    item_request: {
        uid: string,
        request_date: Date | string,
        project_name: string | null,
        department_name: string,
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
    qty: number,
    description: string | null
}

type ItemRequestList = {
    uid: string,
    requirement: string | null,
    request_number: string,
    request_date: Date | string,
    unit_code: string | null,
    wo_number: string | null,
    warehouse: { uid: string, name: string } | null,
    is_project?: boolean,
    project_name: string | null,
    department_name: string,
    status: string,
    reject_reason?: string | null,
    created_by_role_id?: number | null,
    details: ItemRequestDetail[] | null,
    approver?: {
        role_id: number | null,
        role_name: string | null
    } | null,
    created_at: Date | string,
    updated_at: Date | string,
    created_by_name: string,
    updated_by_name: string
}

type ItemRequestForm = {
    uid?: string,
    requirement: string | null,
    request_date: Date | string,
    unit_code?: string | null,
    wo_number?: string | null,
    warehouse_uid?: string | null,
    is_project: boolean,
    project_name?: string | null,
    department_name: string,
    status?: string,
    reject_reason?: string | null,
    details: Array<{
        item_uid: string | null,
        unit_uid: string | null,
        qty: number,
        description: string | null
    }>
}
