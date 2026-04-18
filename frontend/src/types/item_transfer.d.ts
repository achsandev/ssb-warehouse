type ItemTransferDetail = {
    uid?: string
    item: { uid: string; name: string } | null
    unit: { uid: string; name: string; symbol: string } | null
    qty: number
    description: string | null
}

type ItemTransferLog = {
    uid: string
    action: string
    from_status: string | null
    to_status: string | null
    notes: string | null
    metadata: Record<string, any> | null
    actor_name: string | null
    actor_role: string | null
    created_at: string
}

type ItemTransferChainRef = {
    uid: string
    transfer_number: string
    status: string
}

type ItemTransferList = {
    uid: string
    transfer_number: string
    transfer_date: Date | string

    from_warehouse: { uid: string; name: string } | null
    from_rack: { uid: string; name: string } | null
    from_tank: { uid: string; name: string } | null

    to_warehouse: { uid: string; name: string } | null
    to_rack: { uid: string; name: string } | null
    to_tank: { uid: string; name: string } | null

    notes: string | null
    reject_notes: string | null
    status: string

    has_pending_displacement: boolean
    parent_transfer: ItemTransferChainRef | null
    child_transfers: ItemTransferChainRef[] | null

    details: ItemTransferDetail[] | null
    logs: ItemTransferLog[] | null

    approved_by_name: string | null
    approved_at: string | null
    cancelled_at: string | null

    created_at: Date | string
    updated_at: Date | string | null
    created_by_name: string
    updated_by_name: string | null
}

type ItemTransferForm = {
    uid?: string
    transfer_date: Date | string

    from_warehouse_uid: string | null
    from_rack_uid: string | null
    from_tank_uid: string | null

    to_warehouse_uid: string | null
    to_rack_uid: string | null
    to_tank_uid: string | null

    notes: string | null
    parent_transfer_uid?: string | null

    details: Array<{
        item_uid: string | null
        unit_uid: string | null
        qty: number | null
        description: string | null
    }>
}
