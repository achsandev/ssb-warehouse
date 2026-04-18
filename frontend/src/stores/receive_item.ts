import { defineStore } from "pinia";
import { useMessageStore } from "./message";
import { create, get, update } from "@/api/receive_item.api";

const message = useMessageStore()

export const useReceiveItemStore = defineStore("receiveItem", {
    state: () => ({
        data: [] as ReceiveItemList[],
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
                const { data } = await get(params)

                this.data = Array.isArray(data.data) ? data.data : []
                this.pagination = data.data.meta
                this.total = data.data.meta?.total ?? 0
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
        async create (payload: ReceiveItemForm) {
            this.loading = true
            try {
                const { data } = await create(payload)
                this.data = Array.isArray(data) ? data : []
                message.setMessage({
                    text: data?.message || 'Create success',
                    timeout: 1500,
                    color: 'success'
                })
                this.fetch()
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
        async update(uid: string, payload: ReceiveItemForm) {
            console.log(payload)
            this.loading = true
            try {
                const { data } = await update(uid, payload)
                this.data = Array.isArray(data) ? data : []
                message.setMessage({
                    text: data?.message || 'Update success',
                    timeout: 1500,
                    color: 'success'
                })
                this.fetch()
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
        async clear() {
            this.data = []
            this.pagination = {
                page: 1,
                per_page: 5,
                last_page: 1
            }
            this.total = 0
        },
        async delete(uid: number | string) {
            this.loading = true
            try {
                // await remove(uid)
                message.setMessage({
                    text: 'Delete success',
                    timeout: 1500,
                    color: 'success'
                })
                this.fetch()
            } catch (err: any) {
                message.setMessage({
                    text: err.response.statusText,
                    timeout: 3000,
                    color: 'error'
                })
            } finally {
                this.loading = false
            }
        }
    }
})