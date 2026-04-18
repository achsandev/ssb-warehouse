<script lang="ts" setup>
import { useTranslate } from '@/composables/useTranslate'
// Import Unplugin Icons
import SystemUiconsClose from '~icons/system-uicons/close'

const model = defineModel<boolean>()

const props = defineProps<{
    items: KeyValueItem[]
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void,
    (e: 'close'): void
}>()

const t = useTranslate()

const handleClose = () => {
    model.value = false
}
</script>

<template>
    <v-overlay
        v-model="model"
        class="custom-overlay"
        persistent @click:outside="handleClose"
    />

    <v-navigation-drawer
        v-model="model"
        class="custom"
        location="right"
        width="450"
        temporary
        :scrim="false"
    >
        <v-toolbar density="compact">
            <v-toolbar-title :text="t('detail')" class="text-body-2 font-weight-bold" />
            <template v-slot:append>
                <v-btn :icon="SystemUiconsClose" density="compact" @click="handleClose" />
            </template>
        </v-toolbar>
        <v-table density="compact">
            <tbody>
                <tr v-for="(item, index) in props.items" :key="index">
                    <td>{{ t(item.key) }}</td>
                    <td>:</td>
                    <td>{{ item.value }}</td>
                </tr>
            </tbody>
        </v-table>
        <template v-slot:append>
            <div class="d-flex flex-row justify-end align-center pa-2">
                <v-btn variant="text" @click="handleClose">
                    {{ t('close') }}
                </v-btn>
            </div>
        </template>
    </v-navigation-drawer>
</template>

<style scoped>
.custom {
    z-index: 1007 !important;
}

.custom-overlay {
    z-index: 1006 !important;
}
</style>