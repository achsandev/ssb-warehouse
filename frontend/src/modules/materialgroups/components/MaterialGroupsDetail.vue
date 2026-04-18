<script lang="ts" setup>
import { computed } from 'vue'
// Import Types
import type { DataTableHeader } from 'vuetify'
// Import Composables
import { useTranslate } from '@/composables/useTranslate'
// Import Utils
import { formatDate } from '@/utils/date'
// Import Unplugin Icons
import SystemUiconsClose from '~icons/system-uicons/close'

const model = defineModel<boolean>()

const props = defineProps<{
    items: MaterialGroupsList
}>()

const emit = defineEmits<{
    (e: 'openManageSubMaterialGroups', item: MaterialGroupsList): void
}>()

const t = useTranslate()

const headers = computed<DataTableHeader[]>(() => [
    { title: 'No', value: 'no', align: 'center', width: '20', sortable: false },
    { title: t('code'), value: 'code', align: 'start' },
    { title: t('name'), value: 'name', align: 'start' }
])

const handleOpenManageSubMaterialGroups = (item: MaterialGroupsList) => {
    emit('openManageSubMaterialGroups', item)
}

const handleClose = () => {
    model.value = false
}
</script>

<template>
    <v-overlay
        v-model="model"
        class="custom-overlay"
        persistent
        @click:outside="handleClose"
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
            <thead>
                <tr>
                    <th colspan="3">{{ t('dataForm', { field: t('materialGroups') }) }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ t('code') }}</td>
                    <td>:</td>
                    <td>{{ items.code }}</td>
                </tr>
                <tr>
                    <td>{{ t('name') }}</td>
                    <td>:</td>
                    <td>{{ items.name }}</td>
                </tr>
                <tr>
                    <td>{{ t('createdAt') }}</td>
                    <td>:</td>
                    <td>{{ formatDate(items.created_at) }}</td>
                </tr>
                <tr>
                    <td>{{ t('createdBy') }}</td>
                    <td>:</td>
                    <td>{{ items.created_by_name }}</td>
                </tr>
                <tr>
                    <td>{{ t('updatedBy') }}</td>
                    <td>:</td>
                    <td>{{ items.updated_by_name }}</td>
                </tr>
                <tr>
                    <td>{{ t('updatedAt') }}</td>
                    <td>:</td>
                    <td>{{ formatDate(items.updated_at) }}</td>
                </tr>
                <tr>
                    <td>{{ t('subMaterialGroups') }}</td>
                    <td colspan="2">:</td>
                </tr>
                <tr>
                    <td colspan="3">
                        <v-sheet>
                            <v-data-table-virtual
                                v-if="model"
                                class="no-border"
                                :headers="headers"
                                :items="items.sub_material_groups"
                                height="250"
                                fixed-header
                            >
                                <template v-slot:item.no="{ index }">
                                    {{ (index + 1) }}
                                </template>
                            </v-data-table-virtual>
                        </v-sheet>
                    </td>
                </tr>
            </tbody>
        </v-table>
        <template v-slot:append>
            <div class="d-flex flex-row justify-end align-center pa-2">
                <v-btn variant="text" @click="handleClose">
                    {{ t('close') }}
                </v-btn>
                <v-btn variant="text" color="blue-accent-2" @click="handleOpenManageSubMaterialGroups(props.items)">
                    {{ t('manageData', { field: t('subMaterialGroups') }) }}
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

.no-border {
    --v-border-color: transparent;
    border: none !important;
    box-shadow: none !important;
}

.no-border .v-table__wrapper table,
.no-border th,
.no-border td {
    border: none !important;
}

.no-border td .v-table__wrapper table {
    border-collapse: collapse !important;
}
</style>