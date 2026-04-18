import { api } from "./api"

export const get = async (payload: { page: number, per_page: number, sort_by: string, sort_dir: string, search: string }) => {
    return api.get('/item_brands', { params: payload })
}

export const create = async (payload: ItemBrandsForm) => {
    return api.post('/item_brands', payload)
}

export const update = async (uid: string, payload: ItemBrandsForm) => {
    return api.put(`/item_brands/${uid}`, payload)
}

export const destroy = async (uid: string) => {
    return api.delete(`/item_brands/${uid}`)
}