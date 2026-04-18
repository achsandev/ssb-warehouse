import { api } from './api'

export const getAll = async (params: any) => {
    return api.get('/setting_po_approval', { params })
}

export const show = async (uid: string) => {
    return api.get(`/setting_po_approval/${uid}`)
}

export const create = async (payload: SettingPoApprovalForm) => {
    return api.post('/setting_po_approval', payload)
}

export const update = async (uid: string, payload: Partial<SettingPoApprovalForm>) => {
    return api.put(`/setting_po_approval/${uid}`, payload)
}

export const destroy = async (uid: string) => {
    return api.delete(`/setting_po_approval/${uid}`)
}
