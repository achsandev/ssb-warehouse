type PaymentMethodsList = {
    id: number,
    uid: string,
    name: string,
    description: string,
    created_at: Date | string,
    updated_at: Date | string,
    created_by_name: string,
    updated_by_name: string
}

type PaymentMethodsForm = {
    id?: number,
    uid?: string,
    name: string,
    description?: string
}