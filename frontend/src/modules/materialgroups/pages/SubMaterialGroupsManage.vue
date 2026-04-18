<script lang="ts" setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
// Import Types
import type { DataTableHeader } from 'vuetify'
// Import Composables
import { useTranslate } from '@/composables/useTranslate'
// Import Stores
import { useSubMaterialGroupsStore } from '@/stores/sub_material_groups'
import { useMaterialGroupsStore } from '@/stores/material_groups'
// Import Component
import DetailList from '@/components/common/DetailList.vue'
import BaseSearchInput from '@/components/base/BaseSearchInput.vue'
import BaseRefreshButton from '@/components/base/BaseRefreshButton.vue'
import BaseCreateButton from '@/components/base/BaseCreateButton.vue'
import ActionsMenu from '@/components/common/ActionsMenu.vue'
import SubMaterialGroupsForm from '@/modules/materialgroups/components/SubMaterialGroupsForm.vue'
import ConfirmDeleteDialog from '@/components/common/ConfirmDeleteDialog.vue'
// Import Utils
import { formatDate } from '@/utils/date'

const t = useTranslate()
const route = useRoute()
const materialGroupsStore = useMaterialGroupsStore()
const store = useSubMaterialGroupsStore()

const uid = route.params.uid.toString()
const formDialog = ref<boolean>(false)
const confirmDialog = ref<boolean>(false)
const detailDialog = ref<boolean>(false)
const detailData = ref<KeyValueItem[]>([])
const formAction = ref<'create' | 'update'>('create')

const formFields = reactive<SubMaterialGroupsList>({
    uid: '',
    material_group_uid: '',
    material_group_code: '',
    material_group_name: '',
    code: '',
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
    { title: t('actions'), key: 'action', align: 'center', width: '20', sortable: false },
    { title: t('code'), key: 'code', align: 'start', width: '100', nowrap: true },
    { title: t('name'), key: 'name', align: 'start', nowrap: true }
])

// =========================
// Methods
// =========================
const loadData = () => store.getByMaterialGroupUid(uid, options.value)

const resetForm = () => {
    Object.assign(formFields, {
        uid: '',
        material_group_uid: '',
        material_group_name: '',
        material_group_code: '',
        code: '',
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
const materialGroupsData = ref<MaterialGroupsBasicData | null>(null)

onMounted(async () => {
    await Promise.all([
        loadDataMaterialGroups()
    ])
})

const loadDataMaterialGroups = async () => {
    try {
        materialGroupsData.value = null
        await materialGroupsStore.getBasicInfoByUid(uid)
        materialGroupsData.value = Array.isArray(materialGroupsStore.data) ? materialGroupsStore.data[0] : null
    } catch (err) {
        console.error("Error :", err)
    }
}

const handleActionMenu = (action: string, data: SubMaterialGroupsList) => {
    if (action === 'detail') {
        return handleOpenDetailDialog(data)
    }
    if (action === 'update') {
        return handleOpenDialog(data)
    }
    return handleOpenConfirmDialog(data.uid)
}

const handleOpenDialog = (item: SubMaterialGroupsList | null) => {
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

const handleOpenDetailDialog = (item: SubMaterialGroupsList) => {
    detailData.value = []
    resetForm()
    try {
        Object.assign(formFields, item)
        detailData.value = [
            { key: 'material_group_uid', value: item.material_group_uid },
            { key: 'material_group_code', value: item.material_group_code },
            { key: 'material_group_name', value: item.material_group_name },
            { key: 'code', value: item.code },
            { key: 'name', value: item.name },
            { key: 'createdAt', value: formatDate(item.created_at) },
            { key: 'createdBy', value: item.created_by_name },
            { key: 'updatedAt', value: formatDate(item.updated_at) },
            { key: 'updatedBy', value: item.updated_by_name },
        ]
        detailDialog.value = true
    } catch (err) {
        console.error("Error: ", err)
    }
}

const handleSubmit = (values: SubMaterialGroupsForm) => {
    values.material_group_uid = uid
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
        <v-card-title>{{ t('information', { field: t('materialGroups') }) }}</v-card-title>
        <v-card-text>
            <v-table>
                <tbody>
                    <tr>
                        <th width="50">Code</th>
                        <td width="20">:</td>
                        <td>{{ materialGroupsData?.code }}</td>
                    </tr>
                    <tr>
                        <th>Name</th>
                        <td>:</td>
                        <td>{{ materialGroupsData?.name }}</td>
                    </tr>
                </tbody>
            </v-table>
        </v-card-text>
    </v-card>
    <v-card class="mt-5">
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

    <sub-material-groups-form
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