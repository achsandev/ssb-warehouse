<script lang="ts" setup>
import { computed, reactive, ref } from 'vue'
// Import Types
import type { DataTableHeader } from 'vuetify'
// Import Components
import BaseSearchInput from '@/components/base/BaseSearchInput.vue'
import BaseRefreshButton from '@/components/base/BaseRefreshButton.vue'
import BaseCreateButton from '@/components/base/BaseCreateButton.vue'
import ActionsMenu from '@/components/common/ActionsMenu.vue'
import DetailList from '@/components/common/DetailList.vue'
import RequestTypesForm from '@/modules/requesttypes/components/RequestTypesForm.vue'
import ConfirmDeleteDialog from '@/components/common/ConfirmDeleteDialog.vue'
// Import Stores
import { useRequestTypesStore } from '@/stores/request_types'
// Import Composables
import { useTranslate } from '@/composables/useTranslate'
// Import Utils
import { formatDate } from '@/utils/date'

const t = useTranslate()
const store = useRequestTypesStore()

// =========================
// State
// =========================
const formDialog = ref<boolean>(false)
const confirmDialog = ref<boolean>(false)
const detailDialog = ref<boolean>(false)
const detailData = ref<KeyValueItem[]>([])
const formAction = ref<'create' | 'update'>('create')

const formFields = reactive<RequestTypesList>({
    uid: '',
    name: '',
    created_at: '',
    updated_at: '',
    created_by_name: '',
    updated_by_name: ''
})

const formTitle = computed(() => 
    formAction.value === 'create'
        ? t('createDataDialogTitle')
        : t('updateDataDialogTitle')
)

// =========================
// Table Options
// =========================
const itemPerPageOptions = [
    { title: '5', value: 5 },
    { title: '10', value: 10 },
    { title: '25', value: 25 },
    { title: '50', value: 50 },
    { title: '100', value: 100 },
    { title: 'All', value: -1 }
]

const options = ref<TableParams>({
    page: 1,
    itemsPerPage: 5,
    sortBy: [{ key: 'created_at', order: 'desc' }],
    search: ''
})

const headers = computed<DataTableHeader[]>(() => [
    { title: 'No', key: 'no', align: 'center', width: '20', sortable: false },
    { title: t('name'), key: 'name', align: 'start', nowrap: true },
    { title: t('actions'), key: 'action', align: 'center', width: '20', sortable: false },
])

// =========================
// Methods
// =========================
const loadData = () => store.fetch(options.value)

const resetForm = () => {
    Object.assign(formFields, {
        uid: '',
        name: '',
        created_at: '',
        updated_at: '',
        created_by_name: '',
        updated_by_name: ''
    })
}

const handleClose = () => {
    resetForm()
    formDialog.value = false
    detailDialog.value = false
    confirmDialog.value = false
}

// =========================
// Functions
// =========================
const handleActionMenu = (action: string, data: RequestTypesList) => {
    if (action === 'detail') {
        return handleOpenDetailDialog(data)
    }
    if (action === 'update') {
        return handleOpenDialog(data)
    }
    return handleOpenConfirmDialog(data.uid)
}

const handleOpenDialog = (item: RequestTypesList | null) => {
    if (item) {
        formAction.value = 'update'
        Object.assign(formFields, item)
    } else {
        formAction.value = 'create'
        resetForm()
    }
    formDialog.value = true
}

const handleOpenConfirmDialog = (uid: string) => {
    formFields.uid = uid
    confirmDialog.value = true
}

const handleOpenDetailDialog = (item: RequestTypesList) => {
    detailData.value = []
    resetForm()
    try {
        Object.assign(formFields, item)
        detailData.value = [
            { key: 'name', value: item.name },
            { key: 'createdAt', value: formatDate(item.created_at) },
            { key: 'createdBy', value: item.created_by_name },
            { key: 'updatedAt', value: formatDate(item.updated_at) },
            { key: 'updatedBy', value: item.updated_by_name }
        ]
        detailDialog.value = true
    } catch (err) {
        console.error("Error: ", err)
    }
}

const handleSubmit = (values: RequestTypesForm) => {
    if (formAction.value === 'create') {
        store.create(values)
    } else {
        if (formFields.uid) {
            store.update(formFields.uid, values)
        }
    }
    handleClose()
}

const handleDelete = () => {
    if (formFields.uid) {
        store.delete(formFields.uid)
    }
    handleClose()
}
</script>

<template>
    <v-card>
        <v-card-title class="d-flex flex-column flex-sm-row-reverse justify-space-between align-sm-center">
            <base-create-button
                :loading="store.loading"
                :disabled="store.loading"
                @click="handleOpenDialog(null)"
            />

            <div class="d-flex align-center">
                <base-search-input
                    :loading="store.loading"
                    v-model="options.search"
                    @update:model-value="loadData"
                />

                <base-refresh-button
                    :loading="store.loading"
                    :disabled="store.loading"
                    @click="loadData"
                />
            </div>
        </v-card-title>
        <v-card-text>
            <v-data-table-server
                v-model:options="options"
                :loading="store.loading"
                :headers="headers"
                :items="store.data"
                :items-length="store.total"
                :items-per-page="options.itemsPerPage"
                :items-per-page-text="t('itemsPerPage')"
                :items-per-page-options="itemPerPageOptions"
                @update:options="loadData"
            >
                <template v-slot:item.action="{ item }">
                    <actions-menu
                        :menus="['detail', 'update', 'delete']"
                        :data="item"
                        @click="handleActionMenu"
                    />
                </template>
            </v-data-table-server>
        </v-card-text>
    </v-card>

    <request-types-form
        v-if="formDialog"
        v-model="formDialog"
        :title="formTitle"
        :data="formFields"
        @submit="handleSubmit"
        @close="handleClose"
    />

    <confirm-delete-dialog
        v-model="confirmDialog"
        @delete="handleDelete"
        @close="handleClose"
    />

    <detail-list v-model="detailDialog" :items="detailData" />
</template>

<style scoped></style>