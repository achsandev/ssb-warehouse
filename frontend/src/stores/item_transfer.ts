import { defineStore } from 'pinia'
import { useMessageStore } from './message'
import {
    approve as apiApprove,
    cancel as apiCancel,
    create,
    destroy,
    get,
    reject as apiReject,
    show,
    update,
} from '@/api/item_transfer.api'

const message = useMessageStore()

export const useItemTransferStore = defineStore('itemTransfer', {
    state: () => ({
        data: [] as ItemTransferList[],
        currentData: null as ItemTransferList | null,
        pagination: {
            page: 1,
            per_page: 5,
            last_page: 1,
        },
        total: 0,
        loading: false,
        error: false,
    }),

    actions: {
        // ─── Helpers ────────────────────────────────────────────────────────
        notifyError(err: any, fallback = 'Terjadi kesalahan') {
            const text = err?.response?.data?.message
                || err?.response?.statusText
                || err?.message
                || fallback
            message.setMessage({ text, timeout: 4000, color: 'error' })
        },
        notifySuccess(text: string) {
            message.setMessage({ text, timeout: 1800, color: 'success' })
        },

        // ─── List & Detail ──────────────────────────────────────────────────
        async fetch(payload?: TableParams) {
            this.loading = true
            try {
                const params: any = {
                    page: payload?.page || 1,
                    per_page: payload?.itemsPerPage || 5,
                    sort_by: payload?.sortBy?.[0]?.key || 'created_at',
                    sort_dir: payload?.sortBy?.[0]?.order || 'desc',
                    search: payload?.search || '',
                }
                const { data } = await get(params)
                this.data = data.data
                this.pagination = data.meta
                this.total = data.meta?.total ?? 0
            } catch (err: any) {
                this.notifyError(err)
            } finally {
                this.loading = false
            }
        },

        async fetchByUid(uid: string) {
            this.loading = true
            try {
                const { data } = await show(uid)
                this.currentData = data.data
                return data.data
            } catch (err: any) {
                this.notifyError(err)
                return null
            } finally {
                this.loading = false
            }
        },

        // ─── CRUD ───────────────────────────────────────────────────────────
        async create(payload: ItemTransferForm) {
            this.loading = true
            this.error = false
            try {
                const { data } = await create(payload)
                this.notifySuccess(data.message ?? 'Transfer berhasil dibuat')
                this.fetch()
                return data.data
            } catch (err: any) {
                this.error = true
                this.notifyError(err)
                return null
            } finally {
                this.loading = false
            }
        },

        async update(uid: string, payload: Partial<ItemTransferForm>) {
            this.loading = true
            this.error = false
            try {
                const { data } = await update(uid, payload)
                this.notifySuccess(data.message ?? 'Transfer berhasil diperbarui')
                this.fetch()
                return data.data
            } catch (err: any) {
                this.error = true
                this.notifyError(err)
                return null
            } finally {
                this.loading = false
            }
        },

        async delete(uid: string) {
            this.loading = true
            try {
                const { data } = await destroy(uid)
                this.notifySuccess(data.message ?? 'Transfer berhasil dihapus')
                this.fetch()
            } catch (err: any) {
                this.notifyError(err)
            } finally {
                this.loading = false
            }
        },

        // ─── Approval flow ──────────────────────────────────────────────────
        async approve(uid: string) {
            this.loading = true
            this.error = false
            try {
                const { data } = await apiApprove(uid)
                this.notifySuccess(data.message ?? 'Transfer disetujui — stok telah dipindahkan')
                this.fetch()
                return data.data
            } catch (err: any) {
                this.error = true
                // Tampilkan detail shortage jika ada
                const shortages = err?.response?.data?.errors?.shortages
                if (shortages?.length) {
                    const lines = shortages.map((s: any) =>
                        `• ${s.item_name}: minta ${s.requested_qty} ${s.unit_symbol}, tersedia ${s.available_qty}`
                    ).join('\n')
                    message.setMessage({
                        text: `Stok tidak mencukupi:\n${lines}`,
                        timeout: 6000,
                        color: 'error',
                    })
                } else {
                    this.notifyError(err)
                }
                return null
            } finally {
                this.loading = false
            }
        },

        async reject(uid: string, rejectNotes: string) {
            this.loading = true
            this.error = false
            try {
                const { data } = await apiReject(uid, rejectNotes)
                this.notifySuccess(data.message ?? 'Transfer ditolak')
                this.fetch()
                return data.data
            } catch (err: any) {
                this.error = true
                this.notifyError(err)
                return null
            } finally {
                this.loading = false
            }
        },

        async cancel(uid: string, notes?: string) {
            this.loading = true
            this.error = false
            try {
                const { data } = await apiCancel(uid, notes)
                this.notifySuccess(data.message ?? 'Transfer dibatalkan')
                this.fetch()
                return data.data
            } catch (err: any) {
                this.error = true
                this.notifyError(err)
                return null
            } finally {
                this.loading = false
            }
        },
    },
})
