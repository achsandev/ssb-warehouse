import { api } from './api'

export const get = async (params: any) => {
    return api.get('/warehouse_cash_request', { params })
}

export const show = async (uid: string) => {
    return api.get(`/warehouse_cash_request/${uid}`)
}

export const warehousesLookup = async () => {
    return api.get('/warehouse_cash_request/warehouses')
}

export const create = async (payload: WarehouseCashRequestForm) => {
    const formData = new FormData()

    for (const key of Object.keys(payload) as (keyof WarehouseCashRequestForm)[]) {
        const value = payload[key]

        if (Array.isArray(value)) {
            value.forEach(v => formData.append(`${String(key)}[]`, v as any))
        } else if (value !== undefined && value !== null) {
            formData.append(String(key), value as any)
        }
    }

    return await api.post('/warehouse_cash_request', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
    })
}

export const update = async (uid: string, payload: Partial<WarehouseCashRequestForm> & { status?: string }) => {
    return api.put(`/warehouse_cash_request/${uid}`, {
        request_date:  payload.request_date,
        warehouse_uid: payload.warehouse_uid,
        amount:        payload.amount,
        notes:         payload.notes ?? null,
        status:        payload.status,
    })
}

export const uploadAttachment = async (uid: string, file: File) => {
    const form = new FormData()
    form.append('attachment', file)
    return api.post(`/warehouse_cash_request/${uid}/attachment`, form)
}

export const destroy = async (uid: string) => {
    return api.delete(`/warehouse_cash_request/${uid}`)
}
