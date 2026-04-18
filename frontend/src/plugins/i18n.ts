import { createI18n } from 'vue-i18n'
import en from '@/locales/en.json'
import id from '@/locales/id.json'

const browserLang = navigator.language.startsWith('id') ? 'id' : 'en'
export const i18n = createI18n({
    legacy: false,
    locale: browserLang,
    fallbackLocale: 'en',
    messages: { en, id }
})