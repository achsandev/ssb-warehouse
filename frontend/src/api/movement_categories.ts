import { api } from "./api"

export const get = async (payload: { page: number, per_page: number, sort_by: string, sort_dir: string, search: string }) => {
    return api.get('/movement_categories', { params: payload })
}

export const create = async (payload: MovementCategoriesForm) => {
    return api.post('/movement_categories', payload)
}

export const update = async (uid: string, payload: MovementCategoriesForm) => {
    return api.put(`/movement_categories/${uid}`, payload)
}

export const destroy = async (uid: string) => {
    return api.delete(`/movement_categories/${uid}`)
}