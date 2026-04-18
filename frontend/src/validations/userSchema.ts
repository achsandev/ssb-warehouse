import { number, object, string } from "yup"

export const userSchema = (t: Function) => object({
    name: string()
        .required(
            t('fieldRequired', {
                field: t('name')
            })
        ),
    email: string()
        .email(
            t('fieldInvalid')
        )
        .required(
            t('fieldRequired', {
                field: t('email')
            })
        ),
    password: string()
        .min(8,
            t('fieldMinChar', {
                field: t('password'),
                length: 8
            })
        )
        .required(
            t('fieldRequired', {
                field: t('password')
            })
        ),
    user_details: object({
        id_karyawan: number(),
        nik: string()
            .required(
                t('fieldRequired', {
                    field: 'nik'
                })
            ),
        name: string(),
        department: string()
            .required(
                t('fieldRequired', {
                    field: t('departmentName')
                })
            ),
        sub_department: string().optional().nullable(),
        position: string()
            .required(
                t('fieldRequired', {
                    field: t('position')
                })
            ),
        direct_supervisor_id: number().optional().nullable(),
        direct_supervisor_position: string().optional().nullable(),
    })
})