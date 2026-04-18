import { api } from "../api"

export const get = async () => {
    const { data: rawData } = await api.get('/lookup/items')
    const { data: data, ...meta } = rawData

    return {
        data,
        ...meta
    }
}

export const show = async (uid: string) => {
    const { data: rawData } = await api.get(`/lookup/items/${uid}`)
    return rawData?.data ?? rawData
}