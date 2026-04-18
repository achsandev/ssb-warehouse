import { api } from './api'

export const get = async (params: any) => {
    return api.get('/return_item', { params })
}

export const show = async (uid: string) => {
    return api.get(`/return_item/${uid}`)
}

export const create = async (payload: ReturnItemForm) => {
    return api.post('/return_item', {
        purchase_order_uid: payload.purchase_order_uid,
        return_date: payload.return_date,
        project_name: payload.project_name,
        details: payload.details.map((d) => ({
            item_uid:    d.item_uid,
            unit_uid:    d.unit_uid,
            qty:         d.qty,
            return_qty:  d.return_qty,
            description: d.description,
        })),
    })
}

export const update = async (uid: string, payload: Partial<ReturnItemForm>) => {
    return api.put(`/return_item/${uid}`, {
        purchase_order_uid: payload.purchase_order_uid,
        return_date:        payload.return_date,
        project_name:       payload.project_name,
        status:             payload.status,
        details: payload.details?.map((d) => ({
            item_uid:    d.item_uid,
            unit_uid:    d.unit_uid,
            qty:         d.qty,
            return_qty:  d.return_qty,
            description: d.description,
        })),
    })
}

export const destroy = async (uid: string) => {
    return api.delete(`/return_item/${uid}`)
}
