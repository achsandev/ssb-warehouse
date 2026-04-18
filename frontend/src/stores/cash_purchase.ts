import { defineStore } from 'pinia'
import { useMessageStore } from './message'
import { create, destroy, get, update } from '@/api/cash_purchase.api'

const message = useMessageStore()

export const useCashPurchaseStore = defineStore('cashPurchase', {
    state: () => ({
        data:  [] as CashPurchaseList[],
        pagination: {
            page:      1,
            per_page:  10,
            last_page: 1,
        },
        total:   0,
        loading: false,
        error:   false,
    }),

    actions: {
        async fetch(payload?: TableParams) {
            this.loading = true
            try {
                const params: any = {
                    page:     payload?.page || 1,
                    per_page: payload?.itemsPerPage || 10,
                    sort_by:  payload?.sortBy[0]?.key  || 'created_at',
                    sort_dir: payload?.sortBy[0]?.order || 'desc',
                    search:   payload?.search || '',
                }
                const { data } = await get(params)
                this.data       = data.data
                this.pagination = data.meta
                this.total      = data.meta?.total ?? 0
            } catch (err: any) {
                message.setMessage({
                    text:    err.response?.data?.message ?? err.response?.statusText,
                    timeout: 3000,
                    color:   'error',
                })
            } finally {
                this.loading = false
            }
        },

        async create(payload: CashPurchaseForm) {
            this.loading = true
            this.error   = false
            try {
                const { data } = await create(payload)
                message.setMessage({ text: data.message, timeout: 1500, color: 'success' })
                this.fetch()
            } catch (err: any) {
                this.error = true
                message.setMessage({
                    text:    err.response?.data?.message ?? err.response?.statusText,
                    timeout: 3000,
                    color:   'error',
                })
            } finally {
                this.loading = false
            }
        },

        async update(uid: string, payload: Partial<CashPurchaseForm> & { status?: string }) {
            this.loading = true
            this.error   = false
            try {
                const { data } = await update(uid, payload)
                message.setMessage({ text: data.message, timeout: 1500, color: 'success' })
                this.fetch()
            } catch (err: any) {
                this.error = true
                message.setMessage({
                    text:    err.response?.data?.message ?? err.response?.statusText,
                    timeout: 3000,
                    color:   'error',
                })
            } finally {
                this.loading = false
            }
        },

        async delete(uid: string) {
            this.loading = true
            this.error   = false
            try {
                const { data } = await destroy(uid)
                message.setMessage({ text: data.message, timeout: 1500, color: 'success' })
                this.fetch()
            } catch (err: any) {
                this.error = true
                message.setMessage({
                    text:    err.response?.data?.message ?? err.response?.statusText,
                    timeout: 3000,
                    color:   'error',
                })
            } finally {
                this.loading = false
            }
        },
    },
})
