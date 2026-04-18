type SupplierList = {
    uid: string,
    name: string,
    address: string,
    phone_number: string,
    npwp_number?: string,
    pic_name: string,
    email: string,
    payment_method: { uid: string, name: string } | null,
    payment_duration: { uid: string, name: string } | null,
    tax_types: { uid: string, name: string }[] | null,
    additional_info?: string,
    created_at: string,
    updated_at: string
    created_by_name: string,
    updated_by_name: string
}

type SupplierForm = {
    name: string,
    address: string,
    phone_number: string,
    npwp_number?: string,
    pic_name: string,
    email: string,
    payment_method_uid: string | null,
    payment_duration_uid: string | null,
    tax_type_uid: string[] | null,
    additional_info?: string
}