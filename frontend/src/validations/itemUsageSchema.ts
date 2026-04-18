import { array, date, number, object, string } from 'yup'

export const itemUsageSchema = (t: Function) => object({
    item_request_uid: string()
        .required(t('fieldRequired', { field: t('itemRequest') })),
    usage_date: date()
        .required(t('fieldRequired', { field: t('usageDate') }))
        .typeError(t('fieldRequired', { field: t('usageDate') })),
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
                usage_qty: number()
                    .nullable()
                    .required(t('fieldRequired', { field: t('usageQty') }))
                    .min(0.01, t('fieldMustBeGreaterThan', { field: t('usageQty'), value: 0 }))
                    .typeError(t('fieldRequired', { field: t('usageQty') })),
                description: string().default(''),
            })
        )
        .min(1, t('fieldMinItem', { field: t('details'), length: 1 }))
        .required(),
})
