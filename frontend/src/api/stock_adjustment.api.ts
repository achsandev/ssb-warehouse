import { api } from './api'

export const get = async (params: any) => {
    return api.get('/stock_adjustment', { params })
}

export const show = async (uid: string) => {
    return api.get(`/stock_adjustment/${uid}`)
}

export const create = async (payload: StockAdjustmentForm) => {
    return api.post('/stock_adjustment', {
        adjustment_date:   payload.adjustment_date,
        stock_opname_uid:  payload.stock_opname_uid ?? null,
        notes:             payload.notes,
        details: payload.details.map((d) => ({
            stock_unit_uid: d.stock_unit_uid,
            adjustment_qty: d.adjustment_qty,
            notes:          d.notes,
        })),
    })
}

export const update = async (uid: string, payload: Partial<StockAdjustmentForm> & { status?: string }) => {
    return api.put(`/stock_adjustment/${uid}`, {
        adjustment_date:   payload.adjustment_date,
        stock_opname_uid:  payload.stock_opname_uid ?? null,
        notes:             payload.notes,
        status:            payload.status,
        details: payload.details?.map((d) => ({
            stock_unit_uid: d.stock_unit_uid,
            adjustment_qty: d.adjustment_qty,
            notes:          d.notes,
        })),
    })
}

export const destroy = async (uid: string) => {
    return api.delete(`/stock_adjustment/${uid}`)
}
