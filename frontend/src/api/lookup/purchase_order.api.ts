import { api } from "../api"

export const get = async () => {
    const { data: rawData } = await api.get('/lookup/purchase_orders')
    const { data: data, ...meta } = rawData

    return {
        data,
        ...meta
    }
}

export const show = async (uid: string) => {
    const { data: rawData } = await api.get(`/lookup/purchase_orders/${uid}`)
    return rawData?.data ?? rawData
}