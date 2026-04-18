import { api } from "../api"

export const get = async () => {
    const { data: rawData } = await api.get('/lookup/roles')
    const { data, ...meta } = rawData

    return {
        data,
        ...meta
    }
}

export const show = async (id: string) => {
    const { data: rawData } = await api.get(`/lookup/roles/${id}`)
    return rawData?.data ?? rawData
}
