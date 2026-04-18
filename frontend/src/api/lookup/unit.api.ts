import { api } from "../api"

export const get = async () => {
    const { data: rawData } = await api.get('/lookup/units')
    const { data, ...meta } = rawData
    return { data, ...meta }
}

export const show = async (uid: string) => {
    const { data: rawData } = await api.get(`/lookup/units/${uid}`)
    return rawData?.data ?? rawData
}