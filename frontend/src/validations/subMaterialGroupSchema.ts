import { object, string } from 'yup'

export const subMaterialGroupSchema = (t: Function) => object({
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