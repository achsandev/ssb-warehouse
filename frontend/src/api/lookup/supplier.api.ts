import { formatNpwp } from "@/utils/npwp"
import { api } from "../api"

export const get = async () => {
    const { data: rawData } = await api.get('/lookup/suppliers')
    const { data: _data, ...meta } = rawData

    const data = _data.map((item: SupplierList) => ({
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

export const show = async (uid: string) => {
    const { data: rawData } = await api.get(`/lookup/suppliers/${uid}`)
    return rawData?.data ?? rawData
}