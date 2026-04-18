import { array, date, number, object, string } from 'yup'

export const itemTransferSchema = (t: Function) => object({
    transfer_date: date()
        .required(t('fieldRequired', { field: t('transferDate') }))
        .typeError(t('fieldRequired', { field: t('transferDate') })),
    from_warehouse_uid: string()
        .nullable()
        .required(t('fieldRequired', { field: t('fromWarehouse') })),
    from_rack_uid: string().nullable().default(null),
    from_tank_uid: string().nullable().default(null),
    to_warehouse_uid: string()
        .nullable()
        .required(t('fieldRequired', { field: t('toWarehouse') })),
    to_rack_uid: string().nullable().default(null),
    to_tank_uid: string().nullable().default(null),
    notes: string()
        .nullable()
        .default(null),
    details: array()
        .of(
            object().shape({
                item_uid: string()
                    .nullable()
                    .required(t('fieldRequired', { field: t('item') })),
                unit_uid: string()
                    .nullable()
                    .required(t('fieldRequired', { field: t('unit') })),
                qty: number()
                    .nullable()
                    .required(t('fieldRequired', { field: t('qty') }))
                    .min(0.01, t('fieldMustBeGreaterThan', { field: t('qty'), value: 0 }))
                    .typeError(t('fieldRequired', { field: t('qty') })),
                description: string().nullable().default(null),
            })
        )
        .min(1, t('fieldMinItem', { field: t('details'), length: 1 }))
        .required(),
})
