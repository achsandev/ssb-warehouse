<script lang="ts" setup>
// Import Icons
import GridiconsAdd from '~icons/gridicons/add'
// Import Compossables
import { useTranslate } from '@/composables/useTranslate'

const t = useTranslate()

const props = defineProps<{
    loading: boolean,
    disabled: boolean
}>()

const emit = defineEmits<{
    (e: 'click', data: any): void
}>()

const handleClick = (data: any) => {
    emit('click', data)
}
</script>

<template>
    <v-btn
        :loading="props.loading"
        :text="t('create')"
        color="success"
        rounded="lg"
        :append-icon="GridiconsAdd"
        :disabled="props.disabled"
        @click="handleClick"
    />
</template>

<style scoped></style>