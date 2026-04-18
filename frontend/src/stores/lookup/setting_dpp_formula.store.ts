import { defineStore } from 'pinia'
import { useMessageStore } from '../message'
import { get } from '@/api/lookup/setting_dpp_formula.api'

const message = useMessageStore()

export const useSettingDppFormulaLookupStore = defineStore('settingDppFormulaLookup', {
    state: () => ({
        data: [] as SettingDppFormulaList[],
        loading: false,
        loaded: false,
    }),
    actions: {
        async fetch(force = false) {
            if (this.loaded && !force) return
            this.loading = true
            try {
                const result = await get()
                this.data = result.data
                this.loaded = true
            } catch (err: any) {
                message.setMessage({
                    text: err?.response?.statusText || err?.message || 'Fetch failed',
                    timeout: 3000,
                    color: 'error',
                })
            } finally {
                this.loading = false
            }
        },
    },
})
