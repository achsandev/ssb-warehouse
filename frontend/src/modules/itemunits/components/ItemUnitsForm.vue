<script lang="ts" setup>
import { ref } from 'vue'
// Import Types
import type { ValidationRule } from 'vuetify'
// Import Composables
import { useTranslate } from '@/composables/useTranslate'

const t = useTranslate()

const props = defineProps<{
    modelValue: boolean,
    title: string,
    data: ItemUnitsForm
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void,
    (e: 'submit'): void,
    (e: 'close'): void
}>()

const formRef = ref()

const rules: Record<string, ValidationRule> = {
    required: (v: string) => !!v || 'Field Required'
}

const handleSubmit = async () => {
    if (!formRef.value) return

    const result = await formRef.value.validate()
    if (result.valid) {
        emit('submit')
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
        max-width="400"
        :model-value="props.modelValue"
        @update:model-value="value => emit('update:modelValue', value)"
    >
        <v-card class="rounded-lg">
            <v-card-title>{{ props.title }}</v-card-title>
            <v-form ref="formRef" @submit.prevent="handleSubmit">
                <v-card-text>
                    <v-row>
                        <v-col cols="12">
                            <v-text-field
                                :label="t('unitName')"
                                :model-value="props.data.name"
                                variant="outlined"
                                density="comfortable"
                                :rules="[rules.required]"
                                @update:model-value="value => props.data.name = value"
                            />
                        </v-col>
                        <v-col cols="12" class="mt-n3">
                            <v-text-field
                                :label="t('symbol')"
                                :model-value="props.data.symbol"
                                variant="outlined"
                                density="comfortable"
                                :rules="[rules.required]"
                                @update:model-value="value => props.data.symbol = value"
                            />
                        </v-col>
                    </v-row>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn :text="t('cancel')" @click="handleClose" />
                    <v-btn type="submit" :text="t('save')" color="success" />
                </v-card-actions>
            </v-form>
        </v-card>
    </v-dialog>
</template>

<style></style>