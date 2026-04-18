import { configure, defineRule } from 'vee-validate'
import { email, max, min, numeric, required  } from '@vee-validate/rules'
import { useTranslate } from '@/composables/useTranslate'

defineRule('required', required)
defineRule('numeric', numeric)
defineRule('email', email)
defineRule('min', min)
defineRule('max', max)

configure({
    generateMessage: (ctx: any) => {
        const t = useTranslate()

        const messages: Record<string, string> = {
            required: t('fieldRequired', { field: ctx.field }),
            numeric: t('fieldNumeric', { field: ctx.field }),
            email: t('fieldEmail'),
            min: t('fieldMinChar', { field: ctx.field, length: ctx.rule?.params?.[0] }),
            max: t('fieldMaxChar', { field: ctx.field, length: ctx.rule?.params?.[0] })
        }

        return messages[ctx.rule?.name || ''] || t('fieldInvalid')
    },
    validateOnInput: true
})