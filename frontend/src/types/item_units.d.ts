type ItemUnitsList = {
    id: number,
    uid: string,
    name: string,
    symbol: string,
    created_by_id: number,
    created_by_name: string,
    updated_by_id: number,
    updated_by_name: string
}

type ItemUnitsForm = {
    id?: number,
    uid?: string,
    name: string,
    symbol: string,
    created_by_id?: number,
    created_by_name?: string,
    updated_by_id?: number,
    updated_by_name?: string
}