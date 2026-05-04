import { api } from './api'

const BASE = '/setting_min_warehouse_cash'

export const get = (params: any) => api.get(BASE, { params })

export const show = (uid: string) => api.get(`${BASE}/${uid}`)

export const update = (uid: string, payload: SettingMinWarehouseCashForm) =>
    api.put(`${BASE}/${uid}`, payload)
