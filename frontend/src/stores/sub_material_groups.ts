import { create, destroy, get, getByMaterialGroupUid, getByUid, update } from '@/api/sub_material_groups'
import { defineStore } from 'pinia'
import { useMessageStore } from './message'

const message = useMessageStore()

export const useSubMaterialGroupsStore = defineStore('subMaterialGroups', {
    state: () => ({
        data: [] as RackList[],
        pagination: {
            page: 1,
            per_page: 5,
            last_page: 1
        },
        total: 0,
        loading: false
    }),
    actions: {
        async fetch(payload?: TableParams) {
            this.loading = true
            try {
                const params: any = {
                    page: payload?.page || 1,
                    per_page: payload?.itemsPerPage || 5,
                    sort_by: payload?.sortBy[0]?.key || 'created_at',
                    sort_dir: payload?.sortBy[0]?.order || 'desc',
                    search: payload?.search || ''
                }

                const data = await get(params)

                this.data = data.data
                this.pagination = data.meta
                this.total = data.meta?.total ?? 0
            } catch (err: any) {
                message.setMessage({
                    text: err.response.statusText,
                    timeout: 3000,
                    color: 'error'
                })
            } finally {
                this.loading = false
            }
        },
        async getByUid(uid: string | string[]) {
            this.loading = true
            try {
                const data = await getByUid(uid)

                this.data = data.data
                this.pagination = data.meta
                this.total = data.meta?.total ?? 0
            } catch (err: any) {
                message.setMessage({
                    text: err.response.statusText,
                    timeout: 3000,
                    color: 'error'
                })
            } finally {
                this.loading = false
            }
        },
        async getByMaterialGroupUid(uid: string | string[], payload?: TableParams) {
            this.loading = true
            try {
                const params: any = {
                    page: payload?.page || 1,
                    per_page: payload?.itemsPerPage || 5,
                    sort_by: payload?.sortBy[0]?.key || 'created_at',
                    sort_dir: payload?.sortBy[0]?.order || 'desc',
                    search: payload?.search || ''
                }

                const data = await getByMaterialGroupUid(uid, params)

                this.data = data.data
                this.pagination = data.meta
                this.total = data.meta?.total ?? 0
            } catch (err: any) {
                message.setMessage({
                    text: err.response.statusText,
                    timeout: 3000,
                    color: 'error'
                })
            } finally {
                this.loading = false
            }
        },
        async create(payload: SubMaterialGroupsForm) {
            this.loading = true
            try {
                const { data } = await create(payload)

                message.setMessage({
                    text: data.message,
                    timeout: 1500,
                    color: 'success'
                })
            } catch (err: any) {
                message.setMessage({
                    text: err.response.data.message,
                    timeout: 3000,
                    color: 'error'
                })
            } finally {
                this.loading = false
            }
        },
        async update(uid: string, payload: SubMaterialGroupsForm) {
            this.loading = true
            try {
                const { data } = await update(uid, payload)

                message.setMessage({
                    text: data.message,
                    timeout: 1500,
                    color: 'success'
                })
            } catch (err: any) {
                message.setMessage({
                    text: err.response.data.message,
                    timeout: 3000,
                    color: 'error'
                })
            } finally {
                this.loading = false
            }
        },
        async delete(uid: string) {
            this.loading = true
            try {
                const { data } = await destroy(uid)

                message.setMessage({
                    text: data.message,
                    timeout: 1500,
                    color: 'success'
                })
            } catch (err: any) {
                message.setMessage({
                    text: err.response.data.message,
                    timeout: 3000,
                    color: 'error'
                })
            } finally {
                this.loading = false
            }
        }
    }
})