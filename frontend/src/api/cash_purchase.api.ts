import { api } from './api'

export const get = async (params: any) => {
    return api.get('/cash_purchase', { params })
}

export const show = async (uid: string) => {
    return api.get(`/cash_purchase/${uid}`)
}

export const warehousesLookup = async () => {
    return api.get('/cash_purchase/warehouses')
}

export const purchaseOrdersLookup = async () => {
    return api.get('/cash_purchase/purchase_orders')
}

export const create = async (payload: CashPurchaseForm) => {
    return api.post('/cash_purchase', {
        purchase_date: payload.purchase_date,
        warehouse_uid: payload.warehouse_uid,
        po_uid:        payload.po_uid,
        notes:         payload.notes,
    })
}

export const update = async (uid: string, payload: Partial<CashPurchaseForm>) => {
    return api.put(`/cash_purchase/${uid}`, {
        purchase_date: payload.purchase_date,
        warehouse_uid: payload.warehouse_uid,
        po_uid:        payload.po_uid,
        notes:         payload.notes,
        status:        payload.status,
    })
}

export const destroy = async (uid: string) => {
    return api.delete(`/cash_purchase/${uid}`)
}
