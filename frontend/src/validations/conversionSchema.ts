import { array, mixed, number, object, string } from 'yup'

export const conversionSchema = (t: Function) => object({
    stock_uid: string()
        .required(
            t('fieldRequired', {
                field: t('stock')
            })
        ),
    base_unit_uid: string()
        .required(
            t('fieldRequired', {
                field: t('unit')
            })
        ),
    derived_unit_uid: string()
        .required(
            t('fieldRequired', {
                field: t('unit')
            })
        ),
    convert_qty: number()
        .required(
            t('fieldRequired', {
                field: t('convertQty')
            })
        ),
    converted_qty: number()
        .required(
            t('fieldRequired', {
                field: t('convertedQty')
            })
        )
})