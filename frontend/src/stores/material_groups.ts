import { create, destroy, get, getBasicInfoByUid, update } from '@/api/material_groups'
import { defineStore } from 'pinia'
import { useMessageStore } from './message'

const message = useMessageStore()

export const useMaterialGroupsStore = defineStore('materialGroups', {
    state: () => ({
        currentMaterialGroups: null as { uid: string, name: string } | null,
        data: [] as MaterialGroupsList[],
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
        async getBasicInfoByUid(uid: string) {
            this.loading = true
            try {
                const { data } = await getBasicInfoByUid(uid)

                this.data = data.data
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
        async create(payload: MaterialGroupsForm) {
            this.loading = true
            try {
                const { data } = await create(payload)

                message.setMessage({
                    text: data.message,
                    timeout: 1500,
                    color: 'success'
                })
                
                this.fetch()
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
        async update(uid: string, payload: MaterialGroupsForm) {
            this.loading = true
            try {
                const { data } = await update(uid, payload)

                message.setMessage({
                    text: data.message,
                    timeout: 1500,
                    color: 'success'
                })

                this.fetch()
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

                this.fetch()
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
        setCurrentMaterialGroups(payload: { uid: string, name: string }) {
            this.currentMaterialGroups = payload
        },
        clearCurrentMaterialGroups() {
            this.currentMaterialGroups = null
            sessionStorage.removeItem('materialGroups')
        }
    },
    persist: {
        storage: sessionStorage,
        pick: ['currentMaterialGroups']
    }
})