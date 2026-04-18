type SettingDppFormulaList = {
    uid: string,
    name: string,
    formula: string,
    description: string | null,
    is_active: boolean,
    created_at: Date | string,
    updated_at: Date | string,
    created_by_name: string,
    updated_by_name: string
}

type SettingDppFormulaForm = {
    uid?: string,
    name: string,
    formula: string,
    description?: string | null,
    is_active?: boolean
}
