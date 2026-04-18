type ValuationMethodsList = {
    id: number,
    uid: string,
    name: string,
    description: string,
    created_at: Date,
    updated_at: Date,
    created_by_name: string,
    updated_by_name: string
}

type ValuationMethodsForm = {
    id?: number,
    uid?: string,
    name: string,
    description?: string
}