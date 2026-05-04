type SettingMinWarehouseCashList = {
    uid: string,
    name: string,
    address: string | null,
    cash_balance: number,
    min_cash: number | null,
    is_below_min: boolean,
    updated_at: string | null,
}

type SettingMinWarehouseCashForm = {
    uid?: string,
    min_cash: number | null,
}
