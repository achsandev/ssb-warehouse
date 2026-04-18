import { api } from "./api"

export const get = async (payload: { page: number, per_page: number, sort_by: string, sort_dir: string, search: string }) => {
    const { data: _data } = await api.get('/stock', { params: payload })
    const { data: data, ...meta } = _data

    return {
        data,
        ...meta
    }
}

export const getByItemUid = async (item_uid: string) => {
    const { data: _data } = await api.get('/stock/get_by_item_uid/' + item_uid)
    const { data: data, ...meta } = _data
    return {
        data,
        ...meta
    }
}

export const getStockUnitByStockUid = async (uid: string) => {
    const { data: _data } = await api.get('/stock_unit/get_by_stock_uid/' + uid)
    const { data: data, ...meta } = _data

    return {
        data,
        ...meta
    }
}

export const conversion = async (payload: ConversionForm) => {
    const data = {
        stock_uid: payload.stock_uid,
        base_unit_uid: payload.base_unit_uid,
        derived_unit_uid: payload.derived_unit_uid,
        convert_qty: payload.convert_qty,
        converted_qty: payload.converted_qty
    }

    return await api.post('/stock_unit', data)
}

export const create = async (payload: StockForm) => {
    const formData = new FormData()

    for (const key of Object.keys(payload) as (keyof StockForm)[]) {
        const value = payload[key]

        if (Array.isArray(value)) {
            value.forEach(v => formData.append(`${String(key)}[]`, v))
        } else if (value !== undefined && value !== null) {
            formData.append(String(key), value as any)
        }
    }

    return await api.post('/stock', formData, { headers: { 'Content-Type': 'multipart/form-data' } })
}

export const update = async (uid: string, payload: StockForm) => {
    const formData = new FormData()

    formData.append('_method', 'PUT')

    for (const key of Object.keys(payload) as (keyof StockForm)[]) {
        const value = payload[key]

        if (Array.isArray(value)) {
            value.forEach(v => formData.append(`${String(key)}[]`, v))
        } else if (value !== undefined && value !== null) {
            formData.append(String(key), value as any)
        }
    }

    return await api.post(`/stock/${uid}`, formData, { headers: { 'Content-Type': 'multipart/form-data' } })
}

export const destroy = async (uid: string) => {
    return await api.delete(`/stock/${uid}`)
}