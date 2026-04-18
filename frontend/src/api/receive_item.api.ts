import { api } from "./api"

export const get = async (params: any) => {
    return await api.get('/receive_item', { params })
}

export const create = async (payload: any) => {
    let receipt_date = null
    if (payload.receipt_date) {
        if (typeof payload.receipt_date === 'string') {
            const d = new Date(payload.receipt_date)
            receipt_date = isNaN(d.getTime()) ? payload.receipt_date : d.toISOString().split('T')[0]
        } else if (payload.receipt_date instanceof Date) {
            receipt_date = payload.receipt_date.toISOString().split('T')[0]
        }
    }

    return await api.post('/receive_item', {
        receipt_date: receipt_date,
        project_name: payload.project_name,
        purchase_order_uid: payload.purchase_order_id,
        warehouse_uid: payload.warehouse_id,
        shipping_cost: payload.shipping_cost ?? 0,
        additional_info: payload.additional_info,
        details: payload.details.map((detail: any) => ({
            item_uid: detail.item_id ?? '',
            unit_uid: detail.unit_id ?? '',
            supplier_uid: detail.supplier_id ?? '',
            qty: detail.qty ?? 0,
            price: detail.price ?? 0,
            total: (detail.qty ?? 0) * (detail.price ?? 0),
            qty_received: detail.qty_received ?? 0
        }))
    })
}

export const update = async (uid: string, payload: any) => {
    let receipt_date = null
    if (payload.receipt_date) {
        if (typeof payload.receipt_date === 'string') {
            const d = new Date(payload.receipt_date)
            receipt_date = isNaN(d.getTime()) ? payload.receipt_date : d.toISOString().split('T')[0]
        } else if (payload.receipt_date instanceof Date) {
            receipt_date = payload.receipt_date.toISOString().split('T')[0]
        }
    }

    return await api.put(`/receive_item/${uid}`, {
        receipt_date: receipt_date,
        project_name: payload.project_name,
        purchase_order_uid: payload.purchase_order_id,
        warehouse_uid: payload.warehouse_id,
        shipping_cost: payload.shipping_cost ?? 0,
        status: payload.status,
        reject_reason: payload.reject_reason ?? null,
        additional_info: payload.additional_info,
        details: payload.details.map((detail: any) => ({
            item_uid: detail.item_id ?? '',
            unit_uid: detail.unit_id ?? '',
            supplier_uid: detail.supplier_id ?? '',
            qty: detail.qty ?? 0,
            price: detail.price ?? 0,
            total: (detail.qty ?? 0) * (detail.price ?? 0),
            qty_received: detail.qty_received ?? 0
        }))
    })
}

export const remove = async (uid: string) => {
    return await api.delete(`/receive_item/${uid}`)
}