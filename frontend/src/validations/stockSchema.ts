import { number, object, string } from 'yup'

export const stockSchema = (t: Function) => object({
    item_uid: string()
        .required(t('fieldRequired', { field: t('item') })),
    warehouse_uid: string()
        .required(t('fieldRequired', { field: t('warehouse') })),
    rack_uid: string()
        .nullable()
        .default(null),
    tank_uid: string()
        .nullable()
        .default(null),
    unit_uid: string()
        .required(t('fieldRequired', { field: t('unit') })),
    qty: number()
        .required(t('fieldRequired', { field: t('qty') }))
        .min(0, t('fieldMin', { field: t('qty'), min: 0 }))
        .typeError(t('fieldRequired', { field: t('qty') }))
})