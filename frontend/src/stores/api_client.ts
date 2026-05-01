import { defineStore } from 'pinia'
import { useMessageStore } from './message'
import {
    create,
    deleteToken,
    destroy,
    generateToken,
    get,
    update,
} from '@/api/api_client'

const message = useMessageStore()

export const useApiClientStore = defineStore('apiClient', {
    state: () => ({
        data: [] as ApiClientList[],
        pagination: { page: 1, per_page: 10, last_page: 1 },
        total: 0,
        loading: false,
        error: false,
        // Token plaintext yang baru di-generate — disimpan sementara di
        // memory store supaya UI bisa menampilkan dialog "copy now". Setelah
        // dialog ditutup, harus di-clear via `clearGeneratedToken()` agar
        // tidak bocor lewat Vue DevTools / state inspection.
        generatedToken: null as ApiClientGeneratedToken | null,
    }),

    actions: {
        async fetch(payload?: TableParams) {
            this.loading = true
            try {
                const params = {
                    page: payload?.page || 1,
                    per_page: payload?.itemsPerPage || 10,
                    sort_by: payload?.sortBy?.[0]?.key || 'created_at',
                    sort_dir: payload?.sortBy?.[0]?.order || 'desc',
                    search: payload?.search || '',
                }
                const { data } = await get(params)
                this.data = data.data
                this.pagination = data.meta
                this.total = data.meta?.total ?? 0
            } catch (err: any) {
                message.setMessage({
                    text: err.response?.data?.message ?? err.response?.statusText,
                    timeout: 3000,
                    color: 'error',
                })
            } finally {
                this.loading = false
            }
        },

        async create(payload: ApiClientForm) {
            this.loading = true
            this.error = false
            try {
                const { data } = await create(payload)
                message.setMessage({ text: data.message, timeout: 1500, color: 'success' })
                this.fetch()
            } catch (err: any) {
                this.error = true
                message.setMessage({
                    text: err.response?.data?.message ?? err.response?.statusText,
                    timeout: 3000,
                    color: 'error',
                })
            } finally {
                this.loading = false
            }
        },

        async update(uid: string, payload: ApiClientForm) {
            this.loading = true
            this.error = false
            try {
                const { data } = await update(uid, payload)
                message.setMessage({ text: data.message, timeout: 1500, color: 'success' })
                this.fetch()
            } catch (err: any) {
                this.error = true
                message.setMessage({
                    text: err.response?.data?.message ?? err.response?.statusText,
                    timeout: 3000,
                    color: 'error',
                })
            } finally {
                this.loading = false
            }
        },

        async delete(uid: string) {
            this.loading = true
            try {
                const { data } = await destroy(uid)
                message.setMessage({ text: data.message, timeout: 1500, color: 'success' })
                this.fetch()
            } catch (err: any) {
                message.setMessage({
                    text: err.response?.data?.message ?? err.response?.statusText,
                    timeout: 3000,
                    color: 'error',
                })
            } finally {
                this.loading = false
            }
        },

        async generateToken(uid: string, payload: ApiClientGenerateTokenForm) {
            this.loading = true
            try {
                const { data } = await generateToken(uid, payload)
                this.generatedToken = data.data as ApiClientGeneratedToken
                message.setMessage({ text: data.message, timeout: 4000, color: 'success' })
                this.fetch()
            } catch (err: any) {
                message.setMessage({
                    text: err.response?.data?.message ?? err.response?.statusText,
                    timeout: 3000,
                    color: 'error',
                })
            } finally {
                this.loading = false
            }
        },

        async deleteToken(uid: string) {
            this.loading = true
            try {
                const { data } = await deleteToken(uid)
                message.setMessage({ text: data.message, timeout: 1500, color: 'success' })
                this.fetch()
            } catch (err: any) {
                message.setMessage({
                    text: err.response?.data?.message ?? err.response?.statusText,
                    timeout: 3000,
                    color: 'error',
                })
            } finally {
                this.loading = false
            }
        },

        /** Clear plaintext token dari memory setelah admin selesai copy. */
        clearGeneratedToken() {
            this.generatedToken = null
        },
    },
})
