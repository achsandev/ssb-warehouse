import { array, date, number, object, string } from 'yup'

export const stockOpnameSchema = (t: Function) =>
    object({
        opname_date: date()
            .required(t('fieldRequired', { field: t('opnameDate') }))
            .typeError(t('fieldRequired', { field: t('opnameDate') })),
        notes: string().nullable().default(null),
        details: array()
            .of(
                object().shape({
                    stock_unit_uid: string()
                        .nullable()
                        .required(t('fieldRequired', { field: t('stock') })),
                    notes: string().nullable().default(null),
                }),
            )
            .min(1, t('fieldMinItem', { field: t('details'), length: 1 }))
            .max(200, t('fieldMaxItem', { field: t('details'), length: 200 }))
            .required(),
    })

export const stockOpnameCountSchema = (t: Function) =>
    object({
        details: array()
            .of(
                object().shape({
                    uid: string().required(),
                    actual_qty: number()
                        .nullable()
                        .required(t('fieldRequired', { field: t('actualQty') }))
                        .min(0, t('fieldMustBeGreaterThan', { field: t('actualQty'), value: -1 }))
                        .typeError(t('fieldRequired', { field: t('actualQty') })),
                    notes: string().nullable().default(null),
                }),
            )
            .min(1)
            .required(),
    })
