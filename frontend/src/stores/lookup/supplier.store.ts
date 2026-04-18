import { defineStore } from "pinia";
import { useMessageStore } from "../message";
import { get } from "@/api/lookup/supplier.api";

const message = useMessageStore()

export const useSupplierLookupStore = defineStore('supplierLookup', {
    state: () => ({
        data: [] as SupplierList[],
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