import { api } from "./api"

export const get = async (payload: { page: number, per_page: number, sort_by: string, sort_dir: string, search: string }) => {
    return api.get('/payment_methods', { params: payload })
}

export const create = async (payload: PaymentMethodsForm) => {
    return api.post('/payment_methods', payload)
}

export const update = async (uid: string, payload: PaymentMethodsForm) => {
    return api.put(`/payment_methods/${uid}`, payload)
}

export const destroy = async (uid: string) => {
    return api.delete(`/payment_methods/${uid}`)
}