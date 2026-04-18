import { api } from "./api"

export const get = async (payload: { page: number, per_page: number, sort_by: string, sort_dir: string, search: string }) => {
    const { data: _data } = await api.get('/purchase_order', { params: payload })
    const { data: data, ...meta } = _data
    return {
        data,
        ...meta
    }
}

export const getByUid = async (uid: string) => {
    const { data } = await api.get(`/purchase_order/${uid}`)
    return data
}

export const create = async (payload: PurchaseOrderForm) => {
    let poDate = null
    if (payload.po_date) {
        if (typeof payload.po_date === 'string') {
            const d = new Date(payload.po_date)
            poDate = isNaN(d.getTime()) ? payload.po_date : d.toISOString().split('T')[0]
        } else if (payload.po_date instanceof Date) {
            poDate = payload.po_date.toISOString().split('T')[0]
        }
    }
    const total_amount = payload.details.reduce(
        (sum, detail) => sum + (Number(detail.qty) * Number(detail.price || 0)),
        0
    )
    return api.post('/purchase_order', {
        po_date: poDate,
        project_name: payload.project_name,
        item_request_uid: payload.item_request_uid,
        total_amount: total_amount,
        status: payload.status,
        details: payload.details.map(detail => ({
            item_uid: detail.item_uid ?? '',
            unit_uid: detail.unit_uid ?? '',
            supplier_uid: detail.supplier_uid ?? '',
            qty: detail.qty ?? 0,
            price: detail.price ?? 0,
            total: (detail.qty ?? 0) * (detail.price ?? 0)
        }))
    })
}

export const update = async (uid: string, payload: PurchaseOrderForm) => {
    let poDate = null
    if (payload.po_date) {
        if (typeof payload.po_date === 'string') {
            const d = new Date(payload.po_date)
            poDate = isNaN(d.getTime()) ? payload.po_date : d.toISOString().split('T')[0]
        } else if (payload.po_date instanceof Date) {
            poDate = payload.po_date.toISOString().split('T')[0]
        }
    }
    const total_amount = payload.details.reduce(
        (sum, detail) => sum + (Number(detail.qty) * Number(detail.price || 0)),
        0
    )
    return api.put(`/purchase_order/${uid}`, {
        po_date: poDate,
        item_request_uid: payload.item_request_uid,
        total_amount: total_amount,
        status: payload.status,
        details: payload.details.map(detail => ({
            item_uid: detail.item_uid ?? '',
            unit_uid: detail.unit_uid ?? '',
            supplier_uid: detail.supplier_uid ?? '',
            qty: detail.qty ?? 0,
            price: detail.price ?? 0,
            total: (detail.qty ?? 0) * (detail.price ?? 0)
        }))
    })
}

export const deletePurchaseOrder = async (uid: string) => {
    return api.delete(`/purchase_order/${uid}`)
}

export const approvePo = async (uid: string, payload: { status: 'Approved' | 'Rejected', notes?: string }) => {
    return api.post(`/purchase_order/${uid}/approve`, payload)
}