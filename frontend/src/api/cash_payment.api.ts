import { api } from './api'

export const get = async (params: any) => {
    return api.get('/cash_payment', { params })
}

export const show = async (uid: string) => {
    return api.get(`/cash_payment/${uid}`)
}

export const warehousesLookup = async () => {
    return api.get('/cash_payment/warehouses')
}

export const create = async (payload: CashPaymentForm) => {
    const formData = new FormData()

    for (const key of Object.keys(payload) as (keyof CashPaymentForm)[]) {
        const value = payload[key]

        if (Array.isArray(value)) {
            value.forEach(v => formData.append(`${String(key)}[]`, v as any))
        } else if (value !== undefined && value !== null) {
            formData.append(String(key), value as any)
        }
    }

    return await api.post('/cash_payment', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
    })
}

export const update = async (uid: string, payload: Partial<CashPaymentForm> & { status?: string }) => {
    return api.put(`/cash_payment/${uid}`, {
        payment_date:  payload.payment_date,
        warehouse_uid: payload.warehouse_uid,
        description:   payload.description,
        amount:        payload.amount,
        notes:         payload.notes ?? null,
        status:        payload.status,
    })
}

export const destroy = async (uid: string) => {
    return api.delete(`/cash_payment/${uid}`)
}
