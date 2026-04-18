import { api } from "./api"

export const get = async (payload: { page: number, per_page: number, sort_by: string, sort_dir: string, search: string }) => {
    return api.get('/tax_types', { params: payload })
}

export const create = async (payload: PaymentMethodsForm) => {
    return api.post('/tax_types', payload)
}

export const update = async (uid: string, payload: PaymentMethodsForm) => {
    return api.put(`/tax_types/${uid}`, payload)
}

export const destroy = async (uid: string) => {
    return api.delete(`/tax_types/${uid}`)
}