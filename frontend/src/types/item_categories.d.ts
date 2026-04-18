type ItemCategoriesList = {
    id: number,
    uid: string,
    name: string,
    created_by_id: number,
    created_by_name: string,
    updated_by_id: number,
    updated_by_name: string
}

type ItemCategoriesForm = {
    id?: number,
    uid?: string,
    name: string,
    created_by_id?: number,
    created_by_name?: string,
    updated_by_id?: number,
    updated_by_name?: string
}