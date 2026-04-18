import { defineStore } from "pinia"
import { useMessageStore } from "../message"
import { get, show } from "@/api/lookup/item_request.api"

const message = useMessageStore()

export const useItemRequestLookupStore = defineStore('itemRequestLookup', {
    state: () => ({
        data: [] as ItemRequestList[],
        current_data: [] as ItemRequestList[],
        loading: false
    }),
    actions: {
        async fetch() {
            this.loading = true
            try {
                const result = await get()
                this.data = result.data
            } catch (err: any) {
                message.setMessage({
                    text: err?.response?.statusText || err?.message || 'Fetch failed',
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
                this.current_data = await show(uid)
            } catch (err: any) {
                message.setMessage({
                    text: err?.response?.statusText || err?.message || 'Fetch failed',
                    timeout: 3000,
                    color: 'error'
                })
            } finally {
                this.loading = false
            }
        }
    }
})