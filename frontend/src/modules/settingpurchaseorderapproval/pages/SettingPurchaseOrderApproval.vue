<script lang="ts" setup>
import { computed, reactive, ref } from 'vue'
import type { DataTableHeader } from 'vuetify'
// Import Components
import BaseSearchInput from '@/components/base/BaseSearchInput.vue'
import BaseRefreshButton from '@/components/base/BaseRefreshButton.vue'
import BaseCreateButton from '@/components/base/BaseCreateButton.vue'
import ActionsMenu from '@/components/common/ActionsMenu.vue'
import ConfirmDeleteDialog from '@/components/common/ConfirmDeleteDialog.vue'
import SettingPoApprovalForm from '../components/SettingPoApprovalForm.vue'
// Import Stores
import { useSettingPoApprovalStore } from '@/stores/setting_po_approval'
// Import Composables
import { useTranslate } from '@/composables/useTranslate'
import { useAbility } from '@/composables/useAbility'
// Import Utils
import { formatRupiah } from '@/utils/currency'

const t = useTranslate()
const store = useSettingPoApprovalStore()
const { can } = useAbility()

// =========================
// State
// =========================
const formDialog = ref(false)
const confirmDialogDelete = ref(false)
const formAction = ref<'create' | 'update'>('create')

const formField = reactive<SettingPoApprovalForm>({
    level: null,
    role_uid: null,
    min_amount: null,
    description: null,
    is_active: true,
})

const selectedUid = ref<string>('')

const formTitle = computed(() =>
    formAction.value === 'create' ? t('createDataDialogTitle') : t('updateDataDialogTitle')
)

// =========================
// Table Options
// =========================
const itemPerPageOptions = [
    { title: '5',   value: 5 },
    { title: '10',  value: 10 },
    { title: '25',  value: 25 },
    { title: '50',  value: 50 },
    { title: '100', value: 100 },
    { title: 'All', value: -1 },
]

const options = ref<TableParams>({
    page: 1,
    itemsPerPage: 10,
    sortBy: [{ key: 'level', order: 'asc' }],
    search: '',
})

const headers = computed<DataTableHeader[]>(() => [
    { title: t('actions'),     key: 'action',     align: 'center', width: '20', sortable: false },
    { title: t('level'),       key: 'level',      align: 'center', nowrap: true },
    { title: t('roleName'),    key: 'role.name',  align: 'start',  nowrap: true },
    { title: t('minAmount'),   key: 'min_amount', align: 'end',    nowrap: true },
    { title: t('description'), key: 'description', align: 'start', nowrap: true },
    { title: t('status'),      key: 'is_active',  align: 'center', nowrap: true, sortable: false },
])

// =========================
// Helpers
// =========================
const actionMenus = (): string[] => {
    const menus: string[] = []
    if (can('update', 'setting_po_approval')) menus.push('update')
    if (can('delete', 'setting_po_approval')) menus.push('delete')
    return menus
}

// =========================
// State Management
// =========================
const resetForm = () => {
    Object.assign(formField, {
        level: null,
        role_uid: null,
        min_amount: null,
        description: null,
        is_active: true,
    })
    selectedUid.value = ''
}

const handleClose = () => {
    resetForm()
    formDialog.value = false
    confirmDialogDelete.value = false
}

// =========================
// Actions
// =========================
const loadData = () => store.fetch(options.value)

const handleActionMenu = (action: string, data: SettingPoApprovalList) => {
    const actionMap: Record<string, () => void> = {
        update: () => handleOpenForm(data),
        delete: () => handleOpenDelete(data.uid),
    }
    actionMap[action]?.()
}

const handleOpenForm = (item: SettingPoApprovalList | null) => {
    if (item) {
        formAction.value = 'update'
        selectedUid.value = item.uid
        Object.assign(formField, {
            level: item.level,
            role_uid: item.role?.uid ?? null,
            min_amount: item.min_amount,
            description: item.description,
            is_active: item.is_active,
        })
    } else {
        formAction.value = 'create'
        resetForm()
    }
    formDialog.value = true
}

const handleOpenDelete = (uid: string) => {
    selectedUid.value = uid
    confirmDialogDelete.value = true
}

// =========================
// Submit handlers
// =========================
const handleSubmit = async (value: SettingPoApprovalForm) => {
    if (formAction.value === 'create') {
        await store.create(value)
    } else if (selectedUid.value) {
        await store.update(selectedUid.value, value)
    }
    if (!store.error) handleClose()
}

const handleDelete = async () => {
    if (selectedUid.value) {
        await store.delete(selectedUid.value)
    }
    handleClose()
}
</script>

<template>
    <v-card>
        <v-card-title class="d-flex flex-column flex-sm-row-reverse justify-space-between align-sm-center">
            <base-create-button
                v-if="can('create', 'setting_po_approval')"
                :loading="store.loading"
                :disabled="store.loading"
                @click="handleOpenForm(null)"
            />
            <div class="d-flex align-center">
                <base-search-input
                    v-model="options.search"
                    :loading="store.loading"
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
                <template #item.action="{ item }">
                    <actions-menu
                        :menus="actionMenus()"
                        :data="item"
                        @click="handleActionMenu"
                    />
                </template>

                <template #item.min_amount="{ item }">
                    {{ item.min_amount !== null ? formatRupiah(item.min_amount) : '-' }}
                </template>

                <template #item.description="{ item }">
                    {{ item.description ?? '-' }}
                </template>

                <template #item.is_active="{ item }">
                    <v-chip
                        :color="item.is_active ? 'success' : 'grey'"
                        variant="outlined"
                        size="small"
                    >
                        {{ item.is_active ? t('active') : t('inactive') }}
                    </v-chip>
                </template>
            </v-data-table-server>
        </v-card-text>
    </v-card>

    <!-- Create / Update Form -->
    <setting-po-approval-form
        v-if="formDialog"
        v-model="formDialog"
        :title="formTitle"
        :data="formField"
        :saving="store.loading"
        @submit="handleSubmit"
        @close="handleClose"
    />

    <!-- Delete Confirmation -->
    <confirm-delete-dialog
        v-model="confirmDialogDelete"
        @delete="handleDelete"
        @close="handleClose"
    />
</template>

<style scoped></style>
