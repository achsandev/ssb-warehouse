<script lang="ts" setup>
import { useTranslate } from '@/composables/useTranslate'

const t = useTranslate()

const props = defineProps<{
    modelValue: boolean
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'revise'): void
    (e: 'close'): void
}>()

const handleRevise = () => emit('revise')
const handleClose  = () => emit('close')
</script>

<template>
    <v-dialog
        :model-value="props.modelValue"
        max-width="500"
        @update:model-value="value => emit('update:modelValue', value)"
    >
        <v-card class="rounded-lg">
            <v-card-title>{{ t('confirmReviseDialogTitle') }}</v-card-title>
            <v-card-text>
                <p>{{ t('confirmReviseDialogText') }}</p>
            </v-card-text>
            <v-card-actions>
                <v-spacer />
                <v-btn :text="t('cancel')" @click="handleClose" />
                <v-btn :text="t('revise')" color="orange" @click="handleRevise" />
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
