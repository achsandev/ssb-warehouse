import { api } from "./api"

export const get = async (payload: { page: number, per_page: number, sort_by: string, sort_dir: string, search: string }) => {
    return api.get('/valuation_methods', { params: payload })
}

export const create = async (payload: ValuationMethodsForm) => {
    return api.post('/valuation_methods', payload)
}

export const update = async (uid: string, payload: ValuationMethodsForm) => {
    return api.put(`/valuation_methods/${uid}`, payload)
}

export const destroy = async (uid: string) => {
    return api.delete(`/valuation_methods/${uid}`)
}