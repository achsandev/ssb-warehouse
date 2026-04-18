import { api } from "./api"

export const get = async (payload: { page: number, per_page: number, sort_by: string, sort_dir: string, search: string }) => {
    const { data: _data } = await api.get('/material_groups', { params: payload })
    const { data: data, ...meta } = _data

    return {
        data,
        ...meta
    }
}

export const getBasicInfoByUid = async (uid: string) => {
    const data = await api.get(`/material_groups/basic-info/${uid}`)
    return data
}

export const create = async (payload: MaterialGroupsForm) => {
    const data = {
        code: payload.code,
        name: payload.name
    }

    return await api.post('/material_groups', data)
}

export const update = async (uid: string, payload: MaterialGroupsForm) => {
    const data = {
        code: payload.code,
        name: payload.name
    }

    return await api.put(`/material_groups/${uid}`, data)
}

export const destroy = async (uid: string) => {
    return await api.delete(`/material_groups/${uid}`)
}