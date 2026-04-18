import { get } from "@/api/lookup/item.api"
import { useMessageStore } from "../message"
import { defineStore } from "pinia"

const message = useMessageStore()

export const useItemLookupStore = defineStore('itemsLookup', {
    state: () => ({
        data: [] as ItemsList[],
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
        }
    }
})