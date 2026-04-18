import { object, string } from 'yup'

export const requestTypesSchema = (t: Function) => object({
    name: string()
        .required(
            t('fieldRequired', {
                field: t('name')
            })
        )
})