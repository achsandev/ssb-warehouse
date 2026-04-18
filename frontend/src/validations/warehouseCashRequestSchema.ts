import { date, mixed, number, object, string } from 'yup'

export const warehouseCashRequestSchema = (t: Function) =>
    object({
        request_date: date()
            .required(t('fieldRequired', { field: t('requestDate') }))
            .typeError(t('fieldRequired', { field: t('requestDate') })),
        warehouse_uid: string()
            .nullable()
            .required(t('fieldRequired', { field: t('warehouse') })),
        amount: number()
            .nullable()
            .required(t('fieldRequired', { field: t('requestAmount') }))
            .min(1, t('fieldMustBeGreaterThan', { field: t('requestAmount'), value: 0 }))
            .typeError(t('fieldRequired', { field: t('requestAmount') })),
        notes: string().nullable().default(null),
        attachment: mixed<File | File[] | string>()
            .test(
                'is-valid-file',
                'File harus berupa .pdf, .jpg, .jpeg, atau .png',
                (value) => {
                    if (!value) return true
                    if (typeof value === 'string') return true

                    const ALLOWED_MIME = ['application/pdf', 'image/png', 'image/jpeg', 'image/jpg']
                    const ALLOWED_EXT  = ['.pdf', '.png', '.jpg', '.jpeg']

                    if (value instanceof File) {
                        const ext = '.' + value.name.split('.').pop()!.toLowerCase()
                        return ALLOWED_MIME.includes(value.type) || ALLOWED_EXT.includes(ext)
                    }

                    return false
                },
            )
            .nullable(),
    })
