type TaxFormulaType = 'formula' | 'percentage' | 'manual'

type TaxTypesList = {
    id: number,
    uid: string,
    name: string,
    description: string,
    formula_type: TaxFormulaType,
    formula: string | null,
    uses_dpp: boolean,
    created_at: Date,
    updated_at: Date,
    created_by_name: string,
    updated_by_name: string
}

type TaxTypesForm = {
    id?: number,
    uid?: string,
    name: string,
    description?: string,
    formula_type: TaxFormulaType,
    formula?: string | null,
    uses_dpp?: boolean
}
