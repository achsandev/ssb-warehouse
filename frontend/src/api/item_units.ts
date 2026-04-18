import { api } from "./api"

export const get = async (payload: { page: number, per_page: number, sort_by: string, sort_dir: string, search: string }) => {
    return api.get('/item_units', { params: payload })
}

export const create = async (payload: ItemUnitsForm) => {
    return api.post('/item_units', payload)
}

export const update = async (uid: string, payload: ItemUnitsForm) => {
    return api.put(`/item_units/${uid}`, payload)
}

export const destroy = async (uid: string) => {
    return api.delete(`/item_units/${uid}`)
}