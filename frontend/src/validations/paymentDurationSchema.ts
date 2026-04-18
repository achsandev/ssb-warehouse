import { object, string } from 'yup'

export const paymentDurationSchema = (t: Function) => object({
    name: string()
        .required(
            t('fieldRequired', {
                field: t('name')
            })
        )
})