import { approve, create, deleteItemRequest, get, getByUid, reject, revision, update } from "@/api/item_request"
import { defineStore } from "pinia"
import { useMessageStore } from "./message"

const message = useMessageStore()

export const useItemRequestStore = defineStore('itemRequest', {
    state: () => ({
        data: [] as ItemRequestList[],
        current_data: [] as ItemRequestList[],
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
                    sort_by: payload?.sortBy?.[0]?.key || 'created_at',
                    sort_dir: payload?.sortBy?.[0]?.order || 'desc',
                    search: payload?.search || ''
                }
                const { data, ...meta } = await get(params)

                this.data = Array.isArray(data) ? data : []
                this.pagination = meta.meta
                this.total = meta.meta?.total ?? 0
            } catch (err: any) {
                let errorMsg = 'Fetch failed.'
                if (err?.response?.data?.message) {
                    errorMsg = err.response.data.message
                } else if (err?.response?.statusText) {
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
                const { data } = await getByUid(uid)
                this.current_data = data?.data || data
            } catch (err: any) {
                let errorMsg = 'Fetch by UID failed.'
                if (err?.response?.data?.message) {
                    errorMsg = err.response.data.message
                } else if (err?.response?.statusText) {
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
        async create(payload: ItemRequestForm) {
            this.loading = true
            try {
                const { data } = await create(payload)
                message.setMessage({
                    text: data?.message || 'Create success',
                    timeout: 1500,
                    color: 'success'
                })
                this.fetch()
            } catch (err: any) {
                let errorMsg = 'Create failed.'
                if (err?.response?.data?.message) {
                    errorMsg = err.response.data.message
                } else if (err?.response?.statusText) {
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
        async update(uid: string, payload: ItemRequestForm) {
            this.loading = true
            try {
                const { data } = await update(uid, payload)
                message.setMessage({
                    text: data?.message || 'Update success',
                    timeout: 1500,
                    color: 'success'
                })
                this.fetch()
            } catch (err: any) {
                let errorMsg = 'Update failed.'
                if (err?.response?.data?.message) {
                    errorMsg = err.response.data.message
                } else if (err?.response?.statusText) {
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
        async revision(uid: string, payload: ItemRequestForm) {
            this.loading = true
            try {
                const { data } = await revision(uid, payload)
                message.setMessage({
                    text: data?.message || 'Update success',
                    timeout: 1500,
                    color: 'success'
                })
                this.fetch()
            } catch (err: any) {
                let errorMsg = 'Update failed.'
                if (err?.response?.data?.message) {
                    errorMsg = err.response.data.message
                } else if (err?.response?.statusText) {
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
        async approve(uid: string, payload: ItemRequestForm) {
            this.loading = true
            try {
                const { data } = await approve(uid, payload)
                message.setMessage({
                    text: data?.message || 'Approve success',
                    timeout: 1500,
                    color: 'success'
                })
                this.fetch()
            } catch (err: any) {
                let errorMsg = 'Approve failed.';
                if (err?.response?.data?.message) {
                    errorMsg = err.response.data.message;
                } else if (err?.response?.statusText) {
                    errorMsg = err.response.statusText;
                } else if (err?.message) {
                    errorMsg = err.message;
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
        async reject(uid: string, payload: ItemRequestForm) {
            this.loading = true
            try {
                const { data } = await reject(uid, payload)
                message.setMessage({
                    text: data?.message || 'Reject success',
                    timeout: 1500,
                    color: 'success'
                })
                this.fetch()
            } catch (err: any) {
                let errorMsg = 'Reject failed.';
                if (err?.response?.data?.message) {
                    errorMsg = err.response.data.message;
                } else if (err?.response?.statusText) {
                    errorMsg = err.response.statusText;
                } else if (err?.message) {
                    errorMsg = err.message;
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
        async delete(uid: string) {
            this.loading = true
            try {
                const { data } = await deleteItemRequest(uid)
                message.setMessage({
                    text: data?.message || 'Delete success',
                    timeout: 1500,
                    color: 'success'
                })
                this.fetch()
            } catch (err: any) {
                let errorMsg = 'Delete failed.'
                if (err?.response?.data?.message) {
                    errorMsg = err.response.data.message
                } else if (err?.response?.statusText) {
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
        }
    }
})
