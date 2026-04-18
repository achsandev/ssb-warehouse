import { object, string, array } from 'yup'

export const supplierSchema = (t: Function) => object({
    name: string()
        .required(
            t('fieldRequired', {
                field: t('name')
            })
        ),
    address: string()
        .required(
            t('fieldRequired', {
                field: t('address')
            })
        ),
    phone_number: string()
        .required(
            t('fieldRequired', {
                field: t('phoneNumber') 
            })
        )
        .matches(
            /^[0-9]+$/,
            t('fieldNumeric')
        )
        .max(
            15,
            t('fieldMaxChar', {
                field: t('phoneNumber'),
                length: 15
            })
        ),
    npwp_number: string()
        .max(
            27,
            t('fieldMaxChar', {
                field: t('npwpNumber'),
                length: 21
            })
        ),
    pic_name: string()
        .required(
            t('fieldRequired', {
                field: t('picName')
            })
        ),
    email: string()
        .email(t('fieldEmail'))
        .required(
            t('fieldRequired', {
                field: t('email')
            })
        ),
    payment_method_uid: string()
        .required(
            t('fieldRequired', {
                field: t('paymentMethods')
            })
        ),
    payment_duration_uid: string()
        .required(
            t('fieldRequired', {
                field: t('paymentDuration')
            })
        ),
    tax_type_uid: array()
        .of(string().required())
        .required(
            t('fieldRequired', {
                field: t('taxTypes')
            })
        ),
    additional_info: string().nullable()
})