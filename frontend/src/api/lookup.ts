import { api } from './api'

export async function lookupStocks(): Promise<any[]> {
    const res = await api.get<{ data: any[] }>('/lookup/stocks')
    return res.data.data
}

export async function lookupStockByUid(uid: string): Promise<any> {
    const res = await api.get<{ data: any }>(`/lookup/stocks/${uid}`)
    return res.data.data
}

export async function lookupUnits(): Promise<any[]> {
    const res = await api.get<{ data: any[] }>('/lookup/units')
    return res.data.data
}

export async function lookupStockUnits(stockUid: string): Promise<any[]> {
    const res = await api.get<{ data: any[] }>(`/lookup/stock_units/${stockUid}`)
    return res.data.data
}

export async function lookupSettingApproverItemRequest(): Promise<any[]> {
    const res = await api.get<{ data: any[] }>('/lookup/setting_approver_item_request')
    return res.data.data
}
