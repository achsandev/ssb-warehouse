import { array, date, number, object, string } from 'yup'

export const stockAdjustmentSchema = (t: Function) =>
    object({
        adjustment_date: date()
            .required(t('fieldRequired', { field: t('adjustmentDate') }))
            .typeError(t('fieldRequired', { field: t('adjustmentDate') })),
        stock_opname_uid: string().nullable().default(null),
        notes: string().nullable().default(null),
        details: array()
            .of(
                object().shape({
                    stock_unit_uid: string()
                        .nullable()
                        .required(t('fieldRequired', { field: t('stock') })),
                    adjustment_qty: number()
                        .nullable()
                        .required(t('fieldRequired', { field: t('adjustmentQty') }))
                        .notOneOf([0], t('fieldMustBeGreaterThan', { field: t('adjustmentQty'), value: 0 }))
                        .typeError(t('fieldRequired', { field: t('adjustmentQty') })),
                    notes: string().nullable().default(null),
                }),
            )
            .min(1, t('fieldMinItem', { field: t('details'), length: 1 }))
            .max(200, t('fieldMaxItem', { field: t('details'), length: 200 }))
            .required(),
    })
