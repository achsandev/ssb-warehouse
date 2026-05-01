import { api } from './api'

const BASE = '/api_clients'

export const get = (params: any) => api.get(BASE, { params })

export const show = (uid: string) => api.get(`${BASE}/${uid}`)

export const create = (payload: ApiClientForm) => api.post(BASE, payload)

export const update = (uid: string, payload: ApiClientForm) =>
    api.put(`${BASE}/${uid}`, payload)

export const destroy = (uid: string) => api.delete(`${BASE}/${uid}`)

export const generateToken = (uid: string, payload: ApiClientGenerateTokenForm) =>
    api.post(`${BASE}/${uid}/token`, payload)

export const deleteToken = (uid: string) =>
    api.delete(`${BASE}/${uid}/token`)
