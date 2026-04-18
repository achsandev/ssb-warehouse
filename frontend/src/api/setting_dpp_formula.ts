import { api } from './api'

const BASE = '/setting_dpp_formula'

export const get = (params: any) => api.get(BASE, { params })
export const show = (uid: string) => api.get(`${BASE}/${uid}`)
export const create = (payload: SettingDppFormulaForm) => api.post(BASE, payload)
export const update = (uid: string, payload: SettingDppFormulaForm) => api.put(`${BASE}/${uid}`, payload)
export const destroy = (uid: string) => api.delete(`${BASE}/${uid}`)
