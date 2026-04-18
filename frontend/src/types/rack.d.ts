type RackList = {
    uid: string,
    warehouse_uid: string | string[],
    warehouse_name: string,
    name: string,
    additional_info: string,
    created_at: Date | string,
    updated_at: Date | string,
    created_by_name: string,
    updated_by_name: string
}

type RackForm = {
    warehouse_uid: string | string[],
    name: string,
    additional_info: string,
}