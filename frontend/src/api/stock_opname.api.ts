import { api } from './api'

export const get = async (params: any) => {
    return api.get('/stock_opname', { params })
}

export const show = async (uid: string) => {
    return api.get(`/stock_opname/${uid}`)
}

export const create = async (payload: StockOpnameForm) => {
    return api.post('/stock_opname', {
        opname_date: payload.opname_date,
        notes: payload.notes,
        details: payload.details.map((d) => ({
            stock_unit_uid: d.stock_unit_uid,
            notes: d.notes,
        })),
    })
}

export const update = async (uid: string, payload: Partial<StockOpnameForm> & { status?: string }) => {
    return api.put(`/stock_opname/${uid}`, {
        opname_date: payload.opname_date,
        notes: payload.notes,
        status: payload.status,
        details: payload.details?.map((d) => ({
            stock_unit_uid: d.stock_unit_uid,
            notes: d.notes,
        })),
    })
}

export const enterCount = async (uid: string, payload: StockOpnameCountForm) => {
    return api.put(`/stock_opname/${uid}/count`, {
        details: payload.details.map((d) => ({
            uid: d.uid,
            actual_qty: d.actual_qty,
            notes: d.notes,
        })),
    })
}

export const revise = async (uid: string) => {
    return api.put(`/stock_opname/${uid}/revise`)
}

export const destroy = async (uid: string) => {
    return api.delete(`/stock_opname/${uid}`)
}
