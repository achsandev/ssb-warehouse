import { api } from './api'

const buildPayload = (payload: Partial<ItemTransferForm>) => ({
    transfer_date: payload.transfer_date,
    from_warehouse_uid: payload.from_warehouse_uid,
    from_rack_uid: payload.from_rack_uid ?? null,
    from_tank_uid: payload.from_tank_uid ?? null,
    to_warehouse_uid: payload.to_warehouse_uid,
    to_rack_uid: payload.to_rack_uid ?? null,
    to_tank_uid: payload.to_tank_uid ?? null,
    notes: payload.notes,
    parent_transfer_uid: payload.parent_transfer_uid ?? null,
    details: payload.details?.map((d) => ({
        item_uid: d.item_uid,
        unit_uid: d.unit_uid,
        qty: d.qty,
        description: d.description,
    })),
})

export const get = async (params: any) => api.get('/item_transfer', { params })
export const show = async (uid: string) => api.get(`/item_transfer/${uid}`)

export const create = async (payload: ItemTransferForm) =>
    api.post('/item_transfer', buildPayload(payload))

export const update = async (uid: string, payload: Partial<ItemTransferForm>) =>
    api.put(`/item_transfer/${uid}`, buildPayload(payload))

export const destroy = async (uid: string) => api.delete(`/item_transfer/${uid}`)

// ─── Approval flow ──────────────────────────────────────────────────────────
export const approve = async (uid: string) =>
    api.post(`/item_transfer/${uid}/approve`)

export const reject = async (uid: string, rejectNotes: string) =>
    api.post(`/item_transfer/${uid}/reject`, { reject_notes: rejectNotes })

export const cancel = async (uid: string, notes?: string) =>
    api.post(`/item_transfer/${uid}/cancel`, { notes: notes ?? null })
