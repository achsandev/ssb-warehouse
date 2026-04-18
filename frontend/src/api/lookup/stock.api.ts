import { api } from "../api"

export const get = async () => {
    const { data: rawData } = await api.get('/lookup/stocks')
    const { data, ...meta } = rawData

    return {
        data,
        ...meta
    }
}

export const show = async (item_uid: string) => {
    const { data: rawData } = await api.get(`/lookup/stocks/${item_uid}`)
    return rawData?.data ?? rawData
}
