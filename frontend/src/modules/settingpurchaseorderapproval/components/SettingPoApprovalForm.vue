<script lang="ts" setup>
import { ref, onMounted } from 'vue'
import type { ValidationRule } from 'vuetify'
import { useTranslate } from '@/composables/useTranslate'
import { get as getRoleLookup } from '@/api/lookup/role.api'

const t = useTranslate()

const props = defineProps<{
    modelValue: boolean
    title: string
    data: SettingPoApprovalForm
    saving?: boolean
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'submit', value: SettingPoApprovalForm): void
    (e: 'close'): void
}>()

const formRef = ref()
const roles = ref<{ id: number; uid: string; name: string }[]>([])

onMounted(async () => {
    try {
        const res = await getRoleLookup()
        roles.value = res.data ?? []
    } catch {}
})

const rules: Record<string, ValidationRule> = {
    required: (v: any) => (v !== null && v !== undefined && v !== '') || t('fieldRequired', { field: '' }),
    numeric:  (v: any) => v === null || v === '' || !isNaN(Number(v)) || t('fieldNumeric', { field: '' }),
    positive: (v: any) => v === null || v === '' || Number(v) >= 0 || t('fieldMustBeGreaterThan', { field: '', value: 0 }),
    integer:  (v: any) => v === null || v === '' || Number.isInteger(Number(v)) || t('fieldInvalid'),
}

const handleSubmit = async () => {
    if (!formRef.value) return
    const result = await formRef.value.validate()
    if (result.valid) {
        emit('submit', {
            level: props.data.level,
            role_uid: props.data.role_uid,
            min_amount: (props.data.min_amount !== null && props.data.min_amount !== '') ? Number(props.data.min_amount) : null,
            description: props.data.description || null,
            is_active: props.data.is_active,
        })
    }
}

const handleClose = () => {
    formRef.value?.reset()
    formRef.value?.resetValidation()
    emit('close')
}
</script>

<template>
    <v-dialog
        max-width="500"
        :model-value="props.modelValue"
        @update:model-value="value => emit('update:modelValue', value)"
    >
        <v-card class="rounded-lg">
            <v-card-title>{{ props.title }}</v-card-title>
            <v-form ref="formRef" @submit.prevent="handleSubmit">
                <v-card-text>
                    <v-row>
                        <v-col cols="12" md="4">
                            <v-text-field
                                :label="t('level')"
                                :model-value="props.data.level"
                                variant="outlined"
                                density="comfortable"
                                type="number"
                                :rules="[rules.required, rules.integer]"
                                @update:model-value="value => props.data.level = value ? Number(value) : null"
                            />
                        </v-col>
                        <v-col cols="12" md="8">
                            <v-autocomplete
                                :label="t('roleName')"
                                :model-value="props.data.role_uid"
                                :items="roles"
                                item-title="name"
                                item-value="uid"
                                variant="outlined"
                                density="comfortable"
                                clearable
                                :rules="[rules.required]"
                                @update:model-value="value => props.data.role_uid = value ?? null"
                            />
                        </v-col>
                        <v-col cols="12" class="mt-n3">
                            <v-text-field
                                :label="t('minAmount')"
                                :model-value="props.data.min_amount"
                                variant="outlined"
                                density="comfortable"
                                type="number"
                                :hint="t('minAmountHint')"
                                persistent-hint
                                :rules="[rules.numeric, rules.positive]"
                                @update:model-value="value => props.data.min_amount = value !== '' ? value : null"
                            />
                        </v-col>
                        <v-col cols="12" class="mt-n3">
                            <v-textarea
                                :label="t('description')"
                                :model-value="props.data.description"
                                variant="outlined"
                                density="comfortable"
                                rows="2"
                                auto-grow
                                @update:model-value="value => props.data.description = value || null"
                            />
                        </v-col>
                        <v-col cols="12" class="mt-n3">
                            <v-switch
                                :label="t('isActive')"
                                :model-value="props.data.is_active"
                                color="success"
                                density="comfortable"
                                hide-details
                                @update:model-value="value => props.data.is_active = !!value"
                            />
                        </v-col>
                    </v-row>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn :text="t('cancel')" @click="handleClose" />
                    <v-btn
                        type="submit"
                        :text="t('save')"
                        color="success"
                        :loading="props.saving"
                        :disabled="props.saving"
                    />
                </v-card-actions>
            </v-form>
        </v-card>
    </v-dialog>
</template>

<style scoped></style>
