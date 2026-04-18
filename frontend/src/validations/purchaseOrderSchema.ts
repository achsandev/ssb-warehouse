import { array, date, number, object, string } from 'yup'

export const purchaseOrderSchema = (t: Function) => object({
    item_request_uid: string()
        .nullable()
        .required(t('fieldRequired', { field: t('itemRequest') })),
    po_number: string(),
    po_date: date()
        .required(t('fieldRequired', { field: t('poDate') }))
        .typeError(t('fieldRequired', { field: t('poDate') })),
    total_amount: number(),
    status: string(),
    details: array()
        .of(
            object().shape({
                item_uid: string()
                    .nullable()
                    .required(t('fieldRequired', { field: t('item') })),
                unit_uid: string()
                    .nullable()
                    .required(t('fieldRequired', { field: t('unit') })),
                supplier_uid: string()
                    .nullable()
                    .required(t('fieldRequired', { field: t('supplier') })),
                qty: number()
                    .nullable()
                    .required(t('fieldRequired', { field: t('quantity') }))
                    .min(1, t('fieldMin', { field: t('quantity'), min: 1 }))
                    .typeError(t('fieldRequired', { field: t('quantity') })),
                price: number()
                    .nullable()
                    .required(t('fieldRequired', { field: t('price') }))
                    .moreThan(0, t('fieldMustBeGreaterThan', { field: t('price'), value: 0 }))
                    .typeError(t('fieldRequired', { field: t('price') })),
                total: number().nullable(),
            })
        )
        .min(1, t('fieldMinItem', { field: t('items'), length: 1 }))
        .required(),
})
