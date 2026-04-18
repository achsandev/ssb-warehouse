import { api } from "./api"

export const get = async (payload: { page: number, per_page: number, sort_by: string, sort_dir: string, search: string }) => {
    const { data: _data } = await api.get('/usage_units', { params: payload })
    const { data: data, ...meta } = _data

    return {
        data,
        ...meta
    }
}

export const create = async (payload: UsageUnitsForm) => {
    const data = {
        name: payload.name
    }

    return await api.post('/usage_units', data)
}

export const update = async (uid: string, payload: UsageUnitsForm) => {
    const data = {
        name: payload.name
    }

    return await api.put(`/usage_units/${uid}`, data)
}

export const destroy = async (uid: string) => {
    return await api.delete(`/usage_units/${uid}`)
}