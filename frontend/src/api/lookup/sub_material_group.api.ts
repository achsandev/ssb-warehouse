import { api } from "../api"

export const get = async () => {
    const { data: rawData } = await api.get('/lookup/sub_material_groups')
    const { data, ...meta } = rawData

    return {
        data,
        ...meta
    }
}

export const show = async (uid: string) => {
    const { data: rawData } = await api.get(`/lookup/sub_material_groups/${uid}`)
    return rawData?.data ?? rawData
}
