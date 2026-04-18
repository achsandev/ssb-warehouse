import { date, object, string } from 'yup'

export const cashPurchaseSchema = (t: Function) =>
    object({
        purchase_date: date()
            .required(t('fieldRequired', { field: t('purchaseDate') }))
            .typeError(t('fieldRequired', { field: t('purchaseDate') })),

        warehouse_uid: string()
            .nullable()
            .required(t('fieldRequired', { field: t('warehouse') })),

        po_uid: string()
            .nullable()
            .required(t('fieldRequired', { field: t('selectPurchaseOrder') })),

        notes: string().nullable().default(null),
    })
