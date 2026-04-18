import { array, boolean, date, number, object, string } from 'yup'

export const itemRequestSchema = (t: Function) => object({
    requirement: string()
        .nullable()
        .required(t('fieldRequired', { field: t('requirement') })),
    request_date: date()
        .required(t('fieldRequired', { field: t('requestDate') }))
        .typeError(t('fieldRequired', { field: t('requestDate') })),
    unit_code: string().nullable(),
    wo_number: string().nullable(),
    warehouse_uid: string()
        .nullable()
        .required(t('fieldRequired', { field: t('warehouse') })),
    is_project: boolean()
        .required(t('fieldRequired', { field: t('projectType') })),
    // Wajib diisi hanya saat is_project = true.
    project_name: string()
        .nullable()
        .when('is_project', {
            is: true,
            then: (schema) => schema.required(t('fieldRequired', { field: t('projectName') })),
            otherwise: (schema) => schema.nullable(),
        }),
    department_name: string()
        .required(t('fieldRequired', { field: t('departmentName') })),
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
                    .required(t('fieldRequired', { field: t('qty') }))
                    .min(1, t('fieldMin', { field: t('qty'), min: 1 }))
                    .typeError(t('fieldRequired', { field: t('qty') })),
                description: string().nullable(),
            })
        )
        .min(1, t('fieldMinItem', { field: t('items'), length: 1 }))
        .required(),
})
