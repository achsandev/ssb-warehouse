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
import RackForm from '@/modules/rack/components/RackForm.vue'
import ConfirmDeleteDialog from '@/components/common/ConfirmDeleteDialog.vue'
// Import Stores
import { useRackStore } from '@/stores/rack'
// Import Composables
import { useTranslate } from '@/composables/useTranslate'
// Import Icons
import SystemUiconsClose from '~icons/system-uicons/close'

const t = useTranslate()
const store = useRackStore()
const route = useRoute()

// =========================
// State
// =========================
const formDialog = ref<boolean>(false)
const confirmDialog = ref<boolean>(false)
const detailDialog = ref<boolean>(false)
const formAction = ref<'create' | 'update'>('create')

const formFields = reactive<RackList>({
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
    { title: t('rackName'), key: 'name', align: 'start', nowrap: true },
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
    detailDialog.value = false
    confirmDialog.value = false
}

// =========================
// Functions
// =========================
const handleActionMenu = (action: string, data: RackList) => {
    if (action === 'detail') {
        return handleOpenDetailDialog(data)
    }
    if (action === 'update') {
        return handleOpenDialog(data)
    }
    return handleOpenConfirmDialog(data.uid)
}

const handleOpenDialog = (item: RackList | null) => {
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

const handleOpenDetailDialog = (item: RackList) => {
    Object.assign(formFields, item)
    detailDialog.value = true
}

const handleSubmit = (values: RackForm) => {
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

    <rack-form
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

    <v-overlay v-model="detailDialog" class="custom-overlay" persistent @click:outside="handleClose" />

    <v-navigation-drawer v-model="detailDialog" class="custom" location="right" width="450" temporary :scrim="false">
        <v-toolbar density="compact">
            <v-toolbar-title :text="t('detail')" class="text-body-2 font-weight-bold" />
            <template v-slot:append>
                <v-btn :icon="SystemUiconsClose" density="compact" @click="handleClose" />
            </template>
        </v-toolbar>
        <v-table class="no-border" density="compact">
            <thead>
                <tr>
                    <th colspan="3">{{ t('dataForm', { field: t('rack') }) }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ t('warehouseName') }}</td>
                    <td>:</td>
                    <td>{{ formFields.warehouse_name }}</td>
                </tr>
                <tr>
                    <td>{{ t('name') }}</td>
                    <td>:</td>
                    <td>{{ formFields.name }}</td>
                </tr>
                <tr>
                    <td>{{ t('additionalInfo') }}</td>
                    <td>:</td>
                    <td>{{ formFields.additional_info }}</td>
                </tr>
                <tr>
                    <td>{{ t('createdBy') }}</td>
                    <td>:</td>
                    <td>{{ formFields.created_by_name }}</td>
                </tr>
                <tr>
                    <td>{{ t('updatedBy') }}</td>
                    <td>:</td>
                    <td>{{ formFields.updated_by_name }}</td>
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
</style>