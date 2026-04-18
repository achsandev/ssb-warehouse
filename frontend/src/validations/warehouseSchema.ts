import { object, string } from 'yup'

export const warehouseSchema = (t: Function) => object({
    name: string()
        .required(
            t('fieldRequired', {
                field: t('name')
            })
        ),
    address: string()
        .required(
            t('fieldRequired', {
                field: t('address')
            })
        ),
    additional_info: string().nullable()
})