import { defineStore } from 'pinia'
import type { SettingApproverItemRequest } from '@/types/setting_approver_item_request'
import {
  getSettingApproverItemRequests,
  createSettingApproverItemRequest,
  updateSettingApproverItemRequest,
  deleteSettingApproverItemRequest
} from '@/api/setting_approver_item_request'
import { lookupSettingApproverItemRequest } from '@/api/lookup'
import { getRoles } from '@/api/user'

export const useSettingApproverItemRequestStore = defineStore('settingApproverItemRequest', {
  state: () => ({
    items: [] as SettingApproverItemRequest[],
    roles: [] as any[],
    loading: false,
    error: null as string | null,
    selected: null as SettingApproverItemRequest | null
  }),
  actions: {
    async fetchAll() {
      this.loading = true
      try {
        this.items = await lookupSettingApproverItemRequest()
      } catch (e: any) {
        this.error = e.message
      } finally {
        this.loading = false
      }
    },
    async fetchRoles() {
      try {
        this.roles = await getRoles()
      } catch (e: any) {
        this.roles = []
      }
    },
    async create(payload: Partial<SettingApproverItemRequest>) {
      await createSettingApproverItemRequest(payload)
      await this.fetchAll()
    },
    async update(uid: string, payload: Partial<SettingApproverItemRequest>) {
      await updateSettingApproverItemRequest(uid, payload)
      await this.fetchAll()
    },
    async remove(uid: string) {
      await deleteSettingApproverItemRequest(uid)
      await this.fetchAll()
    },
    select(item: SettingApproverItemRequest) {
      this.selected = item
    },
    clearSelected() {
      this.selected = null
    }
  }
})
