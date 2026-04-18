import { object, string } from 'yup'

export const materialGroupSchema = (t: Function) => object({
    code: string()
        .required(
            t('fieldRequired', {
                field: t('code')
            })
        ),
    name: string()
        .required(
            t('fieldRequired', {
                field: t('name')
            })
        )
})