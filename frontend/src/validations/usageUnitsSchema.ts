import { object, string } from 'yup'

export const usageUnitsSchema = (t: Function) => object({
    name: string()
        .required(
            t('fieldRequired', {
                field: t('name')
            })
        )
})