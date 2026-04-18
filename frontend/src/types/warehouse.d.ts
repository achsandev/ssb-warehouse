type WarehouseList = {
    uid: string,
    name: string,
    address: string,
    additional_info: string,
    racks: [],
    tanks: [],
    created_at: Date | string,
    updated_at: Date | string,
    created_by_name: string,
    updated_by_name: string
}

type WarehouseBasicData = {
    uid: string,
    name: string,
    address: string,
    additional_info: string,
    created_at: Date | string,
    updated_at: Date | string,
    created_by_name: string,
    updated_by_name: string
}

type WarehouseForm = {
    name: string,
    address: string,
    additional_info?: string
}