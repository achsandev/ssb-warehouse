import { api } from './api'
import type { SettingApproverItemRequest } from '@/types/setting_approver_item_request'

export async function getSettingApproverItemRequests() {
  const res = await api.get<{data: SettingApproverItemRequest[]}>('/setting_approver_item_request')
  return res.data.data
}

export async function createSettingApproverItemRequest(payload: Partial<SettingApproverItemRequest>) {
  const res = await api.post<SettingApproverItemRequest>('/setting_approver_item_request', payload)
  return res.data
}

export async function updateSettingApproverItemRequest(uid: string, payload: Partial<SettingApproverItemRequest>) {
  const res = await api.put<SettingApproverItemRequest>(`/setting_approver_item_request/${uid}`, payload)
  return res.data
}

export async function deleteSettingApproverItemRequest(uid: string) {
  await api.delete(`/setting_approver_item_request/${uid}`)
}
