import { api } from './api'

export const get = async (params: any) => {
    return api.get('/item_usage', { params })
}

export const show = async (uid: string) => {
    return api.get(`/item_usage/${uid}`)
}

export const create = async (payload: ItemUsageForm) => {
    return api.post('/item_usage', {
        item_request_uid: payload.item_request_uid,
        usage_date:       payload.usage_date,
        project_name:     payload.project_name,
        recipient_name:   payload.recipient_name,
        details: payload.details.map((d) => ({
            item_uid:    d.item_uid,
            unit_uid:    d.unit_uid,
            qty:         d.qty,
            usage_qty:   d.usage_qty,
            description: d.description,
        })),
    })
}

export const update = async (uid: string, payload: Partial<ItemUsageForm>) => {
    return api.put(`/item_usage/${uid}`, {
        item_request_uid: payload.item_request_uid,
        usage_date:       payload.usage_date,
        project_name:     payload.project_name,
        recipient_name:   payload.recipient_name,
        status:           payload.status,
        details: payload.details?.map((d) => ({
            item_uid:    d.item_uid,
            unit_uid:    d.unit_uid,
            qty:         d.qty,
            usage_qty:   d.usage_qty,
            description: d.description,
        })),
    })
}

export const destroy = async (uid: string) => {
    return api.delete(`/item_usage/${uid}`)
}
