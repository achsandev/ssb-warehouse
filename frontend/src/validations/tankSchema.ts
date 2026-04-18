import { object, string } from 'yup'

export const tankSchema = (t: Function) => object({
    name: string()
        .required(
            t('fieldRequired', {
                field: t('name')
            })
        ),
    additional_info: string().nullable()
})