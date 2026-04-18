<script lang="ts" setup>
import { computed, reactive, ref } from 'vue'
import type { DataTableHeader } from 'vuetify'
// Import Components
import BaseSearchInput from '@/components/base/BaseSearchInput.vue'
import BaseRefreshButton from '@/components/base/BaseRefreshButton.vue'
import BaseCreateButton from '@/components/base/BaseCreateButton.vue'
import ActionsMenu from '@/components/common/ActionsMenu.vue'
import ConfirmApproveDialog from '@/components/common/ConfirmApproveDialog.vue'
import ConfirmRejectDialog from '@/components/common/ConfirmRejectDialog.vue'
import ConfirmDeleteDialog from '@/components/common/ConfirmDeleteDialog.vue'
import ItemUsageForm from '../components/ItemUsageForm.vue'
import ItemUsageDetail from '../components/ItemUsageDetail.vue'
// Import Stores
import { useItemUsageStore } from '@/stores/item_usage'
import { useAuthStore } from '@/stores/auth'
// Import Composables
import { useTranslate } from '@/composables/useTranslate'
import { useAbility } from '@/composables/useAbility'
// Import Utils
import { formatDate } from '@/utils/date'
// Import Icons
import MingcuteTimeLine from '~icons/mingcute/time-line'
import UisCheck from '~icons/uis/check'
import CodexCross from '~icons/codex/cross'
import FluentNotepad24Filled from '~icons/fluent/notepad-24-filled'
import CiCheckAll from '~icons/ci/check-all'
import MaterialSymbolsLightDraftRounded from '~icons/material-symbols-light/draft-rounded'

const t = useTranslate()
const store = useItemUsageStore()
const authStore = useAuthStore()
const { can } = useAbility()

// =========================
// State
// =========================
const formDialog = ref(false)
const detailDialog = ref(false)
const confirmDialogDelete = ref(false)
const confirmDialogApprove = ref(false)
const confirmDialogReject = ref(false)
const formAction = ref<'create' | 'update' | 'revised'>('create')

const formField = reactive<ItemUsageList>({
    uid: '',
    usage_number: '',
    usage_date: new Date(),
    item_request: null,
    project_name: null,
    status: '',
    details: [],
    created_at: '',
    updated_at: '',
    created_by_name: '',
    updated_by_name: '',
})

const formTitle = computed(() => {
    if (formAction.value === 'create') return t('createDataDialogTitle')
    if (formAction.value === 'revised') return t('revisedDataDialogTitle')
    return t('updateDataDialogTitle')
})

// =========================
// Table Options
// =========================
const itemPerPageOptions = [
    { title: '5', value: 5 },
    { title: '10', value: 10 },
    { title: '25', value: 25 },
    { title: '50', value: 50 },
    { title: '100', value: 100 },
    { title: 'All', value: -1 },
]

const options = ref<TableParams>({
    page: 1,
    itemsPerPage: 10,
    sortBy: [{ key: 'created_at', order: 'desc' }],
    search: '',
})

const headers = computed<DataTableHeader[]>(() => [
    { title: t('actions'), key: 'action', align: 'center', width: '20', sortable: false },
    { title: t('usageNumber'), key: 'usage_number', align: 'start', nowrap: true },
    { title: t('usageDate'), key: 'usage_date', align: 'start', nowrap: true },
    { title: t('itemRequest'), key: 'item_request', align: 'start', nowrap: true },
    { title: t('projectName'), key: 'project_name', align: 'start', nowrap: true },
    { title: t('status'), key: 'status', align: 'start', nowrap: true },
])

// =========================
// Helpers
// =========================
const statusColor = (status: string) => {
    switch ((status ?? '').toLowerCase()) {
        case 'waiting approval': return 'warning'
        case 'approved':         return 'success'
        case 'rejected':         return 'red'
        case 'revised':          return 'deep-orange'
        case 'converted':        return 'blue'
        default:                 return 'grey'
    }
}

const statusIcon = (status: string) => {
    switch ((status ?? '').toLowerCase()) {
        case 'waiting approval': return MingcuteTimeLine
        case 'approved':         return UisCheck
        case 'rejected':         return CodexCross
        case 'revised':          return FluentNotepad24Filled
        case 'converted':        return CiCheckAll
        default:                 return MaterialSymbolsLightDraftRounded
    }
}

/** True when the current user is the creator of the record */
const isCreator = (item: ItemUsageList) =>
    !!authStore.user?.name && item.created_by_name === authStore.user.name

/** True when user has admin-level access */
const isAdmin = () => can('manage', 'all')

/**
 * Build the action menu per row based on:
 * - Role: admin, Kepala Gudang (approve/reject), or creator
 * - Status: controls which actions make sense
 */
const actionMenus = (item: ItemUsageList): string[] => {
    const menus: string[] = []
    const status = (item.status ?? '').toLowerCase()
    const canApprove = can('approve', 'item_usage')
    const canManage = isAdmin() || isCreator(item)

    menus.push('detail')

    // Approve / Reject: only Kepala Gudang when status is waiting approval or revised
    if (canApprove && (status === 'waiting approval' || status === 'revised')) {
        menus.push('approve', 'reject')
    }

    // Update: admin or creator when draft or waiting approval
    if (canManage && (status === 'draft' || status === 'waiting approval')) {
        menus.push('update')
    }

    // Revised: admin or creator when rejected
    if (canManage && status === 'rejected') {
        menus.push('revised')
    }

    // Delete: admin or creator when still mutable
    if (canManage && (status === 'draft' || status === 'waiting approval')) {
        menus.push('delete')
    }

    return menus
}

// =========================
// State Management
// =========================
const resetForm = () => {
    Object.assign(formField, {
        uid: '',
        usage_number: '',
        usage_date: new Date(),
        item_request: null,
        project_name: null,
        status: '',
        details: [],
        created_at: '',
        updated_at: '',
        created_by_name: '',
        updated_by_name: '',
    })
}

const handleClose = () => {
    resetForm()
    formDialog.value = false
    detailDialog.value = false
    confirmDialogDelete.value = false
    confirmDialogApprove.value = false
    confirmDialogReject.value = false
}

// =========================
// Actions
// =========================
const loadData = () => store.fetch(options.value)

const handleActionMenu = (action: string, data: ItemUsageList) => {
    const actionMap: Record<string, () => void> = {
        detail:  () => handleOpenDetail(data),
        update:  () => handleOpenForm(data, 'update'),
        revised: () => handleOpenForm(data, 'revised'),
        approve: () => handleOpenApprove(data),
        reject:  () => handleOpenReject(data),
        delete:  () => handleOpenDelete(data.uid),
    }
    actionMap[action]?.()
}

const handleOpenForm = (item: ItemUsageList | null, action: 'create' | 'update' | 'revised' = 'create') => {
    if (item) {
        formAction.value = action
        Object.assign(formField, item)
    } else {
        formAction.value = 'create'
        resetForm()
    }
    formDialog.value = true
}

const handleOpenDetail = (item: ItemUsageList) => {
    Object.assign(formField, item)
    detailDialog.value = true
}

const handleOpenApprove = (item: ItemUsageList) => {
    Object.assign(formField, { ...item, status: 'Approved' })
    confirmDialogApprove.value = true
}

const handleOpenReject = (item: ItemUsageList) => {
    Object.assign(formField, { ...item, status: 'Rejected' })
    confirmDialogReject.value = true
}

const handleOpenDelete = (uid: string) => {
    formField.uid = uid
    confirmDialogDelete.value = true
}

// =========================
// Submit handlers
// =========================
const handleSubmit = async (value: ItemUsageForm) => {
    if (formAction.value === 'create') {
        await store.create(value)
    } else {
        if (formField.uid) {
            const status = formAction.value === 'revised' ? 'Revised' : 'Waiting Approval'
            await store.update(formField.uid, { ...value, status })
        }
    }
    if (!store.error) handleClose()
}

const buildPayload = (status: string): ItemUsageForm => ({
    item_request_uid: formField.item_request?.uid ?? '',
    usage_date: formField.usage_date,
    project_name: formField.project_name,
    status,
    details: (formField.details ?? []).map((d) => ({
        item_uid: d.item?.uid ?? null,
        unit_uid: d.unit?.uid ?? null,
        qty: d.qty,
        usage_qty: d.usage_qty,
        description: d.description,
    })),
})

const handleApprove = async () => {
    if (formField.uid) {
        await store.update(formField.uid, buildPayload('Approved'))
    }
    if (!store.error) handleClose()
}

const handleReject = async () => {
    if (formField.uid) {
        await store.update(formField.uid, buildPayload('Rejected'))
    }
    if (!store.error) handleClose()
}

const handleDelete = async () => {
    if (formField.uid) {
        await store.delete(formField.uid)
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
                        :menus="actionMenus(item)"
                        :data="item"
                        @click="handleActionMenu"
                    />
                </template>

                <template #item.item_request="{ item }">
                    {{ item.item_request?.request_number ?? '-' }}
                </template>

                <template #item.project_name="{ item }">
                    {{ item.project_name ?? '-' }}
                </template>

                <template #item.usage_date="{ item }">
                    {{ formatDate(item.usage_date as string) }}
                </template>

                <template #item.status="{ item }">
                    <v-chip
                        :prepend-icon="statusIcon(item.status)"
                        :color="statusColor(item.status)"
                        variant="outlined"
                        size="small"
                    >
                        {{ item.status }}
                    </v-chip>
                </template>
            </v-data-table-server>
        </v-card-text>
    </v-card>

    <!-- Create / Update / Revision Form -->
    <item-usage-form
        v-if="formDialog"
        v-model="formDialog"
        :title="formTitle"
        :data="formField"
        :saving="store.loading"
        @submit="handleSubmit"
        @close="handleClose"
    />

    <!-- Detail Drawer -->
    <item-usage-detail
        v-if="detailDialog"
        v-model="detailDialog"
        :item="formField"
        @close="handleClose"
    />

    <!-- Approve Confirmation -->
    <confirm-approve-dialog
        v-model="confirmDialogApprove"
        @approve="handleApprove"
        @close="handleClose"
    />

    <!-- Reject Confirmation -->
    <confirm-reject-dialog
        v-model="confirmDialogReject"
        @reject="handleReject"
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
