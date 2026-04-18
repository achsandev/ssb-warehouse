type SubMaterialGroupsList = {
    uid: string,
    material_group_uid: string | string[],
    material_group_code: string,
    material_group_name: string,
    code: string,
    name: string,
    created_at: Date | string,
    updated_at: Date | string,
    created_by_name: string,
    updated_by_name: string
}

type SubMaterialGroupsForm = {
    material_group_uid: string | string[],
    code: string,
    name: string
}