import { defineStore } from "pinia"
import { useMessageStore } from "../message"
import { get } from "@/api/lookup/purchase_order.api"

const message = useMessageStore()

export const usePurchaseOrderLookupStore = defineStore('purchaseOrderLookup', {
    state: () => ({
        data: [] as PurchaseOrderList[],
        loading: false
    }),
    actions: {
        async fetch() {
            this.loading = true
            try {
                const result = await get()
                this.data = result.data
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
        }
    }
})