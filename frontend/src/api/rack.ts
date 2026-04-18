import { api } from "./api"

export const get = async (payload: { page: number, per_page: number, sort_by: string, sort_dir: string, search: string }) => {
    const { data: _data } = await api.get('/rack', { params: payload })
    const { data: data, ...meta } = _data

    return {
        data,
        ...meta
    }
}

export const getByUid = async (uid: string | string[], payload: { page: number, per_page: number, sort_by: string, sort_dir: string, search: string }) => {
    const { data: _data } = await api.get(`/rack/${uid}`, { params: payload })
    const { data: data, ...meta } = _data

    return {
        data,
        ...meta
    }
}

export const create = async (payload: RackForm) => {
    const data = {
        warehouse_uid: payload.warehouse_uid,
        name: payload.name,
        additional_info: payload?.additional_info
    }

    return api.post('/rack', data)
}

export const update = async (uid: string, payload: RackForm) => {
    const data = {
        warehouse_uid: payload.warehouse_uid,
        name: payload.name,
        additional_info: payload?.additional_info
    }

    return api.put(`/rack/${uid}`, data)
}

export const destroy = async (uid: string) => {
    return api.delete(`/rack/${uid}`)
}