import { api } from './api'

/** Normalize tanggal ke format YYYY-MM-DD. */
const normalizeDate = (value: Date | string | null | undefined): string | null => {
    if (!value) return null
    if (value instanceof Date) return value.toISOString().split('T')[0]
    const d = new Date(value)
    return isNaN(d.getTime()) ? String(value) : d.toISOString().split('T')[0]
}

/** Builder payload seragam untuk semua mutasi (create/update/revision/approve/reject). */
const buildPayload = (payload: ItemRequestForm, statusOverride?: string) => {
    // `is_project` di-normalize ke boolean eksplisit supaya BE `required|boolean`
    // tidak menolak saat field undefined. Saat non-project, project_name
    // dipaksa null untuk menjaga konsistensi dengan rule `required_if`.
    const isProject = !!payload.is_project
    return {
        requirement: payload.requirement,
        request_date: normalizeDate(payload.request_date),
        unit_code: payload.unit_code ?? null,
        wo_number: payload.wo_number ?? null,
        warehouse_uid: payload.warehouse_uid ?? null,
        is_project: isProject,
        project_name: isProject ? (payload.project_name ?? null) : null,
        department_name: payload.department_name,
        ...(statusOverride ? { status: statusOverride } : {}),
        ...(payload.reject_reason ? { reject_reason: payload.reject_reason } : {}),
        details: payload.details.map(d => ({
            item_uid: d.item_uid ?? '',
            unit_uid: d.unit_uid ?? '',
            qty: d.qty ?? 0,
            description: d.description ?? null,
        })),
    }
}

// ─── Queries ────────────────────────────────────────────────────────────────
export const get = async (payload: {
    page: number; per_page: number; sort_by: string; sort_dir: string; search: string
}) => {
    const { data: raw } = await api.get('/item_request', { params: payload })
    const { data, ...meta } = raw
    return { data, ...meta }
}

export const getByUid = async (uid: string) => api.get(`/item_request/${uid}`)

// ─── Mutations ──────────────────────────────────────────────────────────────
export const create = async (payload: ItemRequestForm) =>
    api.post('/item_request', buildPayload(payload))

export const update = async (uid: string, payload: ItemRequestForm) =>
    api.put(`/item_request/${uid}`, buildPayload(payload, 'Waiting Approval'))

export const revision = async (uid: string, payload: ItemRequestForm) =>
    api.put(`/item_request/${uid}`, buildPayload(payload, 'Revised'))

export const approve = async (uid: string, payload: ItemRequestForm) =>
    api.put(`/item_request/${uid}`, buildPayload(payload, 'Approved'))

export const reject = async (uid: string, payload: ItemRequestForm) =>
    api.put(`/item_request/${uid}`, buildPayload(payload, 'Rejected'))

export const deleteItemRequest = async (uid: string) =>
    api.delete(`/item_request/${uid}`)
