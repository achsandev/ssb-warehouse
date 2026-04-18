type MaterialGroupsList = {
    uid: string,
    code: string,
    name: string,
    sub_material_groups: [],
    created_at: Date | string,
    updated_at: Date | string,
    created_by_name: string,
    updated_by_name: string
}

type MaterialGroupsBasicData = {
    uid: string,
    code: string,
    name: string,
    created_at: Date | string,
    updated_at: Date | string,
    created_by_name: string,
    updated_by_name: string
}

type MaterialGroupsForm = {
    code: string,
    name: string
}