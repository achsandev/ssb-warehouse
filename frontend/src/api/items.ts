import { api } from "./api"

export const get = async (payload: { page: number, per_page: number, sort_by: string, sort_dir: string, search: string }) => {
    const { data: _data } = await api.get('/items', { params: payload })
    const { data: data, ...meta } = _data

    return {
        data,
        ...meta
    }
}

export const getByUid = async (uid: string) => {
    const { data: _data } = await api.get(`/items/${uid}`)
    const { data: data, ...meta } = _data

    return {
        data,
        ...meta
    }
}

export const create = async (payload: ItemsForm) => {
    const formData = new FormData()

    for (const key of Object.keys(payload) as (keyof ItemsForm)[]) {
        const value = payload[key]

        if (Array.isArray(value)) {
            value.forEach(v => formData.append(`${String(key)}[]`, v))
        } else if (value instanceof File) {
            formData.append(String(key), value)
        } else if (key === 'image') {
            // Image existing (string) — jangan kirim agar backend tidak menimpa path lama.
            continue
        } else if (value !== undefined && value !== null) {
            formData.append(String(key), value as any)
        }
    }

    return await api.post('/items', formData, { headers: { 'Content-Type': 'multipart/form-data' } })
}

export const update = async (uid: string, payload: ItemsForm) => {
    const formData = new FormData()

    formData.append('_method', 'PUT')

    for (const key of Object.keys(payload) as (keyof ItemsForm)[]) {
        const value = payload[key]

        if (Array.isArray(value)) {
            value.forEach(v => formData.append(`${String(key)}[]`, v))
        } else if (value instanceof File) {
            formData.append(String(key), value)
        } else if (key === 'image') {
            // Image existing (string) — jangan kirim agar backend tidak menimpa path lama.
            continue
        } else if (value !== undefined && value !== null) {
            formData.append(String(key), value as any)
        }
    }

    return await api.post(`/items/${uid}`, formData, { headers: { 'Content-Type': 'multipart/form-data' } })
}

export const destroy = async (uid: string) => {
    return await api.delete(`/items/${uid}`)
}