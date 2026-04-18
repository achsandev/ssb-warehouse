import { approvePo, create, deletePurchaseOrder, get, getByUid, update } from "@/api/purchase_order.api"
import { defineStore } from "pinia"
import { useMessageStore } from "./message"

const message = useMessageStore()

export const usePurchaseOrderStore = defineStore('purchaseOrder', {
    state: () => ({
        data: [] as any[],
        current_data: [] as PurchaseOrderList[],
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
                const { data, meta } = await get(params)
                this.data = Array.isArray(data) ? data : []
                this.pagination = meta
                this.total = meta?.total ?? 0
            } catch (err: any) {
                let errorMsg = 'Approve failed.'
                if (err?.response?.statusText) {
                    errorMsg = err.response.statusText
                } else if (err?.message) {
                    errorMsg = err.message
                }
                message.setMessage({
                    text: errorMsg,
                    timeout: 3000,
                    color: 'error'
                })
            } finally {
                this.loading = false
            }
        },
        async fetchByUid(uid: string) {
            this.loading = true
            try {
                const data = await getByUid(uid)
                this.current_data = data?.data || data
            } catch (err: any) {
                let errorMsg = 'Fetch failed.'
                if (err?.response?.statusText) {
                    errorMsg = err.response.statusText
                } else if (err?.message) {
                    errorMsg = err.message
                }
                message.setMessage({
                    text: errorMsg,
                    timeout: 3000,
                    color: 'error'
                })
            } finally {
                this.loading = false
            }
        },
        async create (payload: any) {
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
                    text: err.response?.statusText || err.message || 'Create failed',
                    timeout: 3000,
                    color: 'error'
                })
            }
            finally {
                this.loading = false
            }
        },
        async update (uid: string, payload: any) {
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
                    text: err.response?.statusText || err.message || 'Update failed',
                    timeout: 3000,
                    color: 'error'
                })
            }
            finally {
                this.loading = false
            }
        },
        async delete (uid: string) {
            this.loading = true
            try {
                await deletePurchaseOrder(uid)
                message.setMessage({
                    text: 'Delete success',
                    timeout: 1500,
                    color: 'success'
                })
                this.fetch()
            }
            catch (err: any) {
                message.setMessage({
                    text: err.response.statusText,
                    timeout: 3000,
                    color: 'error'
                })
            }
            finally {
                this.loading = false
            }
        },
        async approve (uid: string, payload: { status: 'Approved' | 'Rejected', notes?: string }) {
            this.loading = true
            try {
                const { data } = await approvePo(uid, payload)
                message.setMessage({
                    text: data?.message || (payload.status === 'Approved' ? 'Approval berhasil' : 'PO berhasil ditolak'),
                    timeout: 1500,
                    color: payload.status === 'Approved' ? 'success' : 'warning'
                })
                this.fetch()
            } catch (err: any) {
                const errMsg = err?.response?.data?.message
                    || err?.response?.statusText
                    || err?.message
                    || 'Gagal melakukan approval'
                message.setMessage({
                    text: errMsg,
                    timeout: 4000,
                    color: 'error'
                })
            } finally {
                this.loading = false
            }
        }
    }
})