import { Ability } from "@casl/ability"
import type { App } from "vue"

export const ability = new Ability([])

export default {
    install(app: App) {
        app.config.globalProperties.$ability = ability
        app.provide('ability', ability)
    }
}