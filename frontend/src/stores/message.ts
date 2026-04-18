import { defineStore } from 'pinia'
import type { SnackbarMessage } from 'vuetify/lib/components/VSnackbarQueue/VSnackbarQueue.mjs'

const wait = (ms: number) => {
    return new Promise((resolve) => setTimeout(resolve, ms))
}

export const useMessageStore = defineStore('message', {
    state: () => ({
        messages: [] as SnackbarMessage[]
    }),
    actions: {
        async setMessage(payload: { text: string, timeout: number, color: string }) {
            this.messages.push({
                text: payload.text,
                color: payload.color,
                timeout: payload.timeout
            })

            await wait(payload.timeout)

            return true
        },
        clearMessage() {
            this.messages.push({
                text: '',
                color: '',
                timeout: 0
            })
        }
    }
})