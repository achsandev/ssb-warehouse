import { api } from "./api"

export const get = async (payload: { page: number, per_page: number, sort_by: string, sort_dir: string, search: string }) => {
    const { data: _data } = await api.get('/sub_material_groups', { params: payload })
    const { data: rawData, ...meta } = _data

    let no = (meta.meta.current_page - 1) * meta.meta.per_page + 1
    const data = rawData.map((item: SubMaterialGroupsList, index: number) => ({
        no: (no + index),
        ...item
    }))

    return {
        data,
        ...meta
    }
}

export const getByUid = async (uid: string | string[]) => {
    const { data: _data } = await api.get(`/sub_material_groups/${uid}`)
    const { data: data, ...meta } = _data

    return {
        data,
        ...meta
    }
}

export const getByMaterialGroupUid = async (uid: string | string[], payload: { page: number, per_page: number, sort_by: string, sort_dir: string, search: string }) => {
    const { data: _data } = await api.get(`/sub_material_groups/get_by_material_group_uid/${uid}`, { params: payload })
    const { data: data, ...meta } = _data

    return {
        data,
        ...meta
    }
}

export const create = async (payload: SubMaterialGroupsForm) => {
    const data = {
        material_group_uid: payload.material_group_uid,
        code: payload.code,
        name: payload.name
    }

    return await api.post('/sub_material_groups', data)
}

export const update = async (uid: string, payload: SubMaterialGroupsForm) => {
    const data = {
        material_group_uid: payload.material_group_uid,
        code: payload.code,
        name: payload.name
    }

    return await api.put(`/sub_material_groups/${uid}`, data)
}

export const destroy = async (uid: string) => {
    return await api.delete(`/sub_material_groups/${uid}`)
}