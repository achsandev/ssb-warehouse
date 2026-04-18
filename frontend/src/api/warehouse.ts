import { api } from "./api"

export const get = async (payload: { page: number, per_page: number, sort_by: string, sort_dir: string, search: string }) => {
    const { data: _data } = await api.get('/warehouse', { params: payload })
    const { data: data, ...meta } = _data

    return {
        data,
        ...meta
    }
}

export const getBasicInfoByUid = async (uid: string) => {
    const data = await api.get(`/warehouse/basic-info/${uid}`)
    return data
}

export const create = async (payload: WarehouseForm) => {
    const data = {
        name: payload.name,
        address: payload.address,
        additional_info: payload?.additional_info
    }

    return await api.post('/warehouse', data)
}

export const update = async (uid: string, payload: WarehouseForm) => {
    const data = {
        name: payload.name,
        address: payload.address,
        additional_info: payload?.additional_info
    }

    return await api.put(`/warehouse/${uid}`, data)
}

export const destroy = async (uid: string) => {
    return await api.delete(`/warehouse/${uid}`)
}