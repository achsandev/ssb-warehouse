<script lang="ts" setup>
import { computed, reactive, ref } from 'vue'
// Import Types
import type { DataTableHeader } from 'vuetify'
// Import Components
import BaseSearchInput from '@/components/base/BaseSearchInput.vue'
import BaseRefreshButton from '@/components/base/BaseRefreshButton.vue'
import BaseCreateButton from '@/components/base/BaseCreateButton.vue'
import ActionsMenu from '@/components/common/ActionsMenu.vue'
import ValuationMethodsForm from '@/modules/valuationmethods/components/ValuationMethodsForm.vue'
import ConfirmDeleteDialog from '@/components/common/ConfirmDeleteDialog.vue'
import DetailList from '@/components/common/DetailList.vue'
// Import Stores
import { useValuationMethodsStore } from '@/stores/valuation_methods'
// Import Composables
import { useTranslate } from '@/composables/useTranslate'
// Import Utils
import { formatDate } from '@/utils/date'

const t = useTranslate()

const store = useValuationMethodsStore()

const formTitle = computed(() => {
    return formAction.value === 'create' ? t('createDataDialogTitle') : t('updateDataDialogTitle')
})

const formDialog = ref<boolean>(false)
const formAction = ref<'create' | 'update'>('create')
const formFields = reactive<ValuationMethodsForm>({
    id: 0,
    uid: '',
    name: '',
    description: ''
})
const confirmDialog = ref<boolean>(false)
const detailDialog = ref<boolean>(false)
const detailData = ref<KeyValueItem[]>([])

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
    { title: t('methodName'), key: 'name', align: 'start' },
    { title: t('description'), key: 'description', align: 'start' },
    { title: t('actions'), key: 'action', align: 'center', width: '20', sortable: false }
])

const loadData = () => {
    store.fetch(options.value)
}

const handleActionMenu = (action: string, data: ValuationMethodsList) => {
    if (action === 'detail') {
        return handleOpenDetailDialog(data)
    }

    if (action === 'update') {
        return handleOpenDialog(data)
    }

    return handleOpenConfirmDialog(data.uid)
}

const handleOpenDialog = (item: ValuationMethodsList | null) => {
    if (item) {
        formAction.value = 'update'
        formFields.id = item.id
        formFields.uid = item.uid
        formFields.name = item.name
        formFields.description = item.description
    } else {
        formAction.value = 'create'
        formFields.id = 0
        formFields.uid = ''
        formFields.name = ''
        formFields.description = ''
    }

    formDialog.value = true
}

const handleOpenDetailDialog = (item: ValuationMethodsList) => {
    detailData.value = []
    resetForm()
    try {
        Object.assign(formFields, item)
        detailData.value = [
            { key: 'name', value: item.name },
            { key: 'description', value: item.description },
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

const handleOpenConfirmDialog = (uid: string) => {
    formFields.uid = uid
    confirmDialog.value = true
}

const handleSubmit = () => {
    let data

    if (formAction.value === 'create') {
        data = {
            name: formFields.name,
            description: formFields.description
        }

        store.create(data)
    } else {
        data = {
            name: formFields.name,
            description: formFields.description
        }

        if (formFields.uid) {
            store.update(formFields.uid, data)
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

const resetForm = () => {
    formFields.id = 0
    formFields.uid = ''
    formFields.name = ''
    formFields.description = ''
}

const handleClose = () => {
    resetForm()
    formDialog.value = false
    confirmDialog.value = false
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

    <valuation-methods-form
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