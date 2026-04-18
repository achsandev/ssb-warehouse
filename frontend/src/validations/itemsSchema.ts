import { array, mixed, number, object, string } from 'yup'

export const itemsSchema = (t: Function) => object({
    name: string()
        .required(
            t('fieldRequired', {
                field: t('name')
            })
        ),
    brand_uid: string()
        .required(
            t('fieldRequired', {
                field: t('brand')
            })
        ),
    category_uid: string()
        .required(
            t('fieldRequired', {
                field: t('category')
            })
        ),
    unit_uid: string()
        .required(
            t('fieldRequired', {
                field: t('unit')
            })
        ),
    movement_category_uid: string()
        .required(
            t('fieldRequired', {
                field: t('movementCategory')
            })
        ),
    valuation_method_uid: string()
        .required(
            t('fieldRequired', {
                field: t('valuationMethod')
            })
        ),
    material_group_uid: string()
        .required(
            t('fieldRequired', {
                field: t('materialGroup')
            })
        ),
    sub_material_group_uid: string()
        .required(
            t('fieldRequired', {
                field: t('subMaterialGroup')
            })
        ),
    request_types_uid: array()
        .of(string().required())
        .required(
            t('fieldRequired', {
                field: t('requestTypes')
            })
        ),
    unit_types_uid: array()
        .of(string().required())
        .required(
            t('fieldRequired', {
                field: t('unitTypes')
            })
        ),
    min_qty: number()
        .required(
            t('fieldRequired', {
                field: t('minQty')
            })
        ),
    part_number: string()
        .required(
            t('fieldRequired', {
                field: t('partNumber')
            })
        ),
    interchange_part: string().nullable(),
    image: mixed<File | File[] | string>().test('is-valid-file', 'File harus berupa gambar (.png, .jpg, .jpeg, .jpe, .img)', value => {
        if (typeof value === 'string') return true

        const ALLOWED_MIME = ['image/png', 'image/jpeg', 'image/jpg']
        const ALLOWED_EXT  = ['.png', '.jpg', '.jpeg', '.jpe', '.img']

        if (value instanceof File) {
            const ext = '.' + value.name.split('.').pop()!.toLowerCase()
            return ALLOWED_MIME.includes(value.type) || ALLOWED_EXT.includes(ext)
        }

        return false
    }).nullable(),
    supplier_uid: string().nullable(),
    price: string().nullable(),
    exp_date: string().nullable(),
    additional_info: string().nullable(),
    // Field stok (opsional — hanya divalidasi jika user mengisi warehouse)
    stock_warehouse_uid: string().nullable(),
    stock_rack_uid: string().nullable(),
    stock_tank_uid: string().nullable(),
    stock_qty: number()
        .nullable()
        .transform((value, original) => (original === '' || original === null ? null : value))
        .min(0, t('fieldMin', { field: t('qty'), min: 0 }))
        .when('stock_warehouse_uid', {
            is: (val: string | null) => !!val,
            then: (s) => s.required(t('fieldRequired', { field: t('qty') })),
            otherwise: (s) => s.nullable()
        })
})