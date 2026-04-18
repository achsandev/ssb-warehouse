<script lang="ts" setup>
import { computed, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
// Import Types
import type { DataTableHeader } from 'vuetify'
// Import Components
import BaseSearchInput from '@/components/base/BaseSearchInput.vue'
import BaseRefreshButton from '@/components/base/BaseRefreshButton.vue'
import BaseCreateButton from '@/components/base/BaseCreateButton.vue'
import ActionsMenu from '@/components/common/ActionsMenu.vue'
import TankForm from '@/modules/warehouse/components/TankForm.vue'
import ConfirmDeleteDialog from '@/components/common/ConfirmDeleteDialog.vue'
// Import Stores
import { useTankStore } from '@/stores/tank'
// Import Composables
import { useTranslate } from '@/composables/useTranslate'
// Import Utils
import { formatDate } from '@/utils/date'

const t = useTranslate()
const store = useTankStore()
const route = useRoute()

// =========================
// State
// =========================
const emit = defineEmits<{
    (e: 'openDetailDialog', value: KeyValueItem[], isActive: boolean): void
}>()

const formDialog = ref<boolean>(false)
const confirmDialog = ref<boolean>(false)
const detailData = ref<KeyValueItem[]>([])
const formAction = ref<'create' | 'update'>('create')

const formFields = reactive<TankList>({
    uid: '',
    warehouse_uid: '',
    warehouse_name: '',
    name: '',
    additional_info: '',
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
    { title: t('actions'), key: 'action', align: 'center', width: '20', sortable: false },
    { title: t('tankName'), key: 'name', align: 'start', nowrap: true },
    { title: t('additionalInfo'), key: 'additional_info', align: 'start', nowrap: true }
])

// =========================
// Methods
// =========================
const loadData = () => store.getByUid(route.params.uid, options.value)

const resetForm = () => {
    Object.assign(formFields, {
        uid: '',
        warehouse_uid: '',
        warehouse_name: '',
        name: '',
        additional_info: ''
    })
}

const handleClose = () => {
    resetForm()
    formDialog.value = false
    confirmDialog.value = false
}

// =========================
// Functions
// =========================
const handleActionMenu = (action: string, data: TankList) => {
    if (action === 'detail') {
        return handleOpenDetailDialog(data)
    }
    if (action === 'update') {
        return handleOpenDialog(data)
    }
    return handleOpenConfirmDialog(data.uid)
}

const handleOpenDialog = (item: TankList | null) => {
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

const handleOpenDetailDialog = (item: TankList) => {
    detailData.value = []
    try {
        detailData.value = [
            { key: 'warehouseName', value: item.warehouse_name },
            { key: 'name', value: item.name },
            { key: 'additionalInfo', value: item.additional_info },
            { key: 'createdAt', value: formatDate(item.created_at) },
            { key: 'createdBy', value: item.created_by_name },
            { key: 'updatedAt', value: formatDate(item.updated_at) },
            { key: 'updatedBy', value: item.updated_by_name },
        ]
        emit('openDetailDialog', detailData.value, true)
    } catch (err) {
        console.error("Error: ", err)
    }
}

const handleSubmit = (values: TankForm) => {
    values.warehouse_uid = route.params.uid
    if (formAction.value === 'create') {
        store.create(values)
    } else {
        if (formFields.uid) {
            store.update(formFields.uid, values)
        }
    }
    loadData()
    handleClose()
}

const handleDelete = () => {
    if (formFields.uid) {
        store.delete(formFields.uid)
    }
    loadData()
    handleClose()
}
</script>

<template>
    <v-card-text>
        <div class="d-flex flex-column flex-sm-row-reverse justify-space-between align-sm-center">
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
        </div>
        <div>
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
        </div>
    </v-card-text>

    <tank-form
        v-if="formDialog"
        v-model="formDialog"
        :title="formTitle"
        :data="formFields"
        :saving="store.loading"
        @submit="handleSubmit"
        @close="handleClose"
    />

    <confirm-delete-dialog
        v-model="confirmDialog"
        @delete="handleDelete"
        @close="handleClose"
    />
</template>

<style scoped></style>