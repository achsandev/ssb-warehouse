import { api } from "./api"

export const get = async (payload: { page: number, per_page: number, sort_by: string, sort_dir: string, search: string }) => {
    const { data: _data } = await api.get('/payment_duration', { params: payload })
    const { data: rawData, ...meta } = _data

    let no = (meta.meta.current_page - 1) * meta.meta.per_page + 1
    const data = rawData.map((item: PaymentDurationList, index: number) => ({
        no: (no + index),
        ...item
    }))

    return {
        data,
        ...meta
    }
}

export const create = async (payload: PaymentDurationForm) => {
    return await api.post('/payment_duration', payload)
}

export const update = async (uid: string, payload: PaymentDurationForm) => {
    const data = {
        name: payload.name
    }

    return await api.put(`/payment_duration/${uid}`, data)
}

export const destroy = async (uid: string) => {
    return await api.delete(`/payment_duration/${uid}`)
}