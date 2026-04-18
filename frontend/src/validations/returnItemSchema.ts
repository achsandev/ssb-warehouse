import { array, date, number, object, string } from 'yup'

export const returnItemSchema = (t: Function) => object({
    purchase_order_uid: string()
        .nullable()
        .required(t('fieldRequired', { field: t('purchaseOrder') })),
    return_date: date()
        .required(t('fieldRequired', { field: t('returnDate') }))
        .typeError(t('fieldRequired', { field: t('returnDate') })),
    project_name: string()
        .nullable()
        .default(null),
    details: array()
        .of(
            object().shape({
                item_uid: string()
                    .required(t('fieldRequired', { field: t('item') })),
                unit_uid: string()
                    .required(t('fieldRequired', { field: t('unit') })),
                qty: number()
                    .required()
                    .min(0)
                    .default(0)
                    .typeError(t('fieldRequired', { field: t('qty') })),
                return_qty: number()
                    .nullable()
                    .required(t('fieldRequired', { field: t('returnQty') }))
                    .min(0.01, t('fieldMustBeGreaterThan', { field: t('returnQty'), value: 0 }))
                    .typeError(t('fieldRequired', { field: t('returnQty') })),
                description: string().default(''),
            })
        )
        .min(1, t('fieldMinItem', { field: t('details'), length: 1 }))
        .required(),
})
