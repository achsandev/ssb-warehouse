import { formatNpwp, unformatNpwp } from "@/utils/npwp"
import { api } from "./api"

export const get = async (payload: { page: number, per_page: number, sort_by: string, sort_dir: string, search: string }) => {
    const { data: _data } = await api.get('/supplier', { params: payload })
    const { data: rawData, ...meta } = _data

    const data = rawData.map((item: SupplierList) => ({
        uid: item.uid,
        name: item.name,
        address: item.address,
        phone_number: item.phone_number,
        npwp_number: item.npwp_number ? formatNpwp(item.npwp_number) : '-',
        pic_name: item.pic_name,
        email: item.email,
        payment_method: item.payment_method,
        payment_duration: item.payment_duration,
        tax_types: item.tax_types,
        additional_info: item.additional_info,
        created_at: item.created_at,
        updated_at: item.updated_at,
        created_by_name: item.created_by_name,
        updated_by_name: item.updated_by_name
    }))

    return {
        data,
        ...meta
    }
}

export const create = async (payload: SupplierForm) => {
    const data = {
        name: payload.name,
        address: payload.address,
        phone_number: payload.phone_number,
        npwp_number: payload.npwp_number ? unformatNpwp(payload.npwp_number) : '-',
        pic_name: payload.pic_name,
        email: payload.email,
        payment_method_uid: payload.payment_method_uid,
        payment_duration_uid: payload.payment_duration_uid,
        tax_type_uid: payload.tax_type_uid,
        additional_info: payload?.additional_info
    }

    return api.post('/supplier', data)
}

export const update = async (uid: string, payload: SupplierForm) => {
    const data = {
        name: payload.name,
        address: payload.address,
        phone_number: payload.phone_number,
        npwp_number: payload.npwp_number ? unformatNpwp(payload.npwp_number) : '-',
        pic_name: payload.pic_name,
        email: payload.email,
        payment_method_uid: payload.payment_method_uid,
        payment_duration_uid: payload.payment_duration_uid,
        tax_type_uid: payload.tax_type_uid,
        additional_info: payload?.additional_info
    }

    return api.put(`/supplier/${uid}`, data)
}

export const destroy = async (uid: string) => {
    return api.delete(`/supplier/${uid}`)
}