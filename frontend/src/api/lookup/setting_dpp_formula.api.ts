import { api } from '../api'

export const get = async () => {
    const { data: raw } = await api.get('/lookup/setting_dpp_formula')
    return {
        data: (raw?.data ?? []) as SettingDppFormulaList[],
    }
}
