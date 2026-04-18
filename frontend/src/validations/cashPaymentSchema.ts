import { date, mixed, number, object, string } from 'yup'

export const cashPaymentSchema = (t: Function, isEdit = false) =>
    object({
        payment_date: date()
            .required(t('fieldRequired', { field: t('paymentDate') }))
            .typeError(t('fieldRequired', { field: t('paymentDate') })),
        warehouse_uid: string()
            .nullable()
            .required(t('fieldRequired', { field: t('warehouse') })),
        description: string()
            .nullable()
            .required(t('fieldRequired', { field: t('paymentDescription') })),
        amount: number()
            .nullable()
            .required(t('fieldRequired', { field: t('paymentAmount') }))
            .min(1, t('fieldMustBeGreaterThan', { field: t('paymentAmount'), value: 0 }))
            .typeError(t('fieldRequired', { field: t('paymentAmount') })),
        spk: mixed<File | File[]>()
            .test('is-pdf', t('fileMustBePdf', { field: t('spkDocument') }), (value) => {
                if (!value) return true
                const ALLOWED_MIME = ['application/pdf']
                const ALLOWED_EXT  = ['.pdf']
                if (value instanceof File) {
                    const ext = '.' + value.name.split('.').pop()!.toLowerCase()
                    return ALLOWED_MIME.includes(value.type) || ALLOWED_EXT.includes(ext)
                }
                return false
            })
            .when([], {
                is:        () => !isEdit,
                then:      (s) => s.required(t('fieldRequired', { field: t('spkDocument') })),
                otherwise: (s) => s.nullable(),
            }),
        attachment: mixed<File | File[]>()
            .test('is-pdf', t('fileMustBePdf', { field: t('attachment') }), (value) => {
                if (!value) return true
                const ALLOWED_MIME = ['application/pdf']
                const ALLOWED_EXT  = ['.pdf']
                if (value instanceof File) {
                    const ext = '.' + value.name.split('.').pop()!.toLowerCase()
                    return ALLOWED_MIME.includes(value.type) || ALLOWED_EXT.includes(ext)
                }
                return false
            })
            .when([], {
                is:        () => !isEdit,
                then:      (s) => s.required(t('fieldRequired', { field: t('attachment') })),
                otherwise: (s) => s.nullable(),
            }),
        notes: string().nullable().default(null),
    })
