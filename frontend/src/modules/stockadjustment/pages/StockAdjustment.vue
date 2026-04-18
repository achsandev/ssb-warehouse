<script lang="ts" setup>
import { computed, reactive, ref } from 'vue'
import type { DataTableHeader } from 'vuetify'
// Components
import BaseSearchInput from '@/components/base/BaseSearchInput.vue'
import BaseRefreshButton from '@/components/base/BaseRefreshButton.vue'
import ActionsMenu from '@/components/common/ActionsMenu.vue'
import ConfirmApproveDialog from '@/components/common/ConfirmApproveDialog.vue'
import ConfirmRejectDialog from '@/components/common/ConfirmRejectDialog.vue'
import ConfirmDeleteDialog from '@/components/common/ConfirmDeleteDialog.vue'
import StockAdjustmentForm from '../components/StockAdjustmentForm.vue'
import StockAdjustmentDetail from '../components/StockAdjustmentDetail.vue'
// Stores
import { useStockAdjustmentStore } from '@/stores/stock_adjustment'
import { useAuthStore } from '@/stores/auth'
// Composables
import { useTranslate } from '@/composables/useTranslate'
import { useAbility } from '@/composables/useAbility'
// Utils
import { formatDate } from '@/utils/date'
// Icons
import MingcuteTimeLine from '~icons/mingcute/time-line'
import UisCheck from '~icons/uis/check'
import CodexCross from '~icons/codex/cross'
import MaterialSymbolsLightDraftRounded from '~icons/material-symbols-light/draft-rounded'
import MaterialSymbolsEditRounded from '~icons/material-symbols/edit-rounded'

const t = useTranslate()
const store = useStockAdjustmentStore()
const authStore = useAuthStore()
const { can } = useAbility()

// =========================
// State
// =========================
const formDialog           = ref(false)
const detailDialog         = ref(false)
const confirmDialogDelete  = ref(false)
const confirmDialogApprove = ref(false)
const confirmDialogReject  = ref(false)
const formAction = ref<'create' | 'update'>('create')

const formField = reactive<StockAdjustmentList>({
    uid: '',
    adjustment_number: '',
    adjustment_date: new Date(),
    stock_opname_uid: null,
    stock_opname_number: null,
    notes: null,
    status: '',
    details: [],
    created_at: '',
    updated_at: null,
    created_by_name: '',
    updated_by_name: null,
})

const formTitle = computed(() =>
    formAction.value === 'create' ? t('createDataDialogTitle') : t('updateDataDialogTitle'),
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
    sortBy: [{ key: 'created_at', order: 'desc' }],
    search: '',
})

const headers = computed<DataTableHeader[]>(() => [
    { title: t('actions'),          key: 'action',            align: 'center', width: '20', sortable: false },
    { title: t('adjustmentNumber'), key: 'adjustment_number', align: 'start', nowrap: true },
    { title: t('adjustmentDate'),   key: 'adjustment_date',   align: 'start', nowrap: true },
    { title: t('opnameReference'),  key: 'stock_opname_number', align: 'start' },
    { title: t('status'),           key: 'status',            align: 'start', nowrap: true },
    { title: t('notes'),            key: 'notes',             align: 'start' },
])

// =========================
// Helpers
// =========================
const statusColor = (status: string) => {
    switch ((status ?? '').toLowerCase()) {
        case 'waiting approval': return 'warning'
        case 'approved':         return 'success'
        case 'rejected':         return 'red'
        case 'revised':          return 'info'
        default:                 return 'grey'
    }
}

const statusIcon = (status: string) => {
    switch ((status ?? '').toLowerCase()) {
        case 'waiting approval': return MingcuteTimeLine
        case 'approved':         return UisCheck
        case 'rejected':         return CodexCross
        case 'revised':          return MaterialSymbolsEditRounded
        default:                 return MaterialSymbolsLightDraftRounded
    }
}

const isCreator = (item: StockAdjustmentList) =>
    !!authStore.user?.name && item.created_by_name === authStore.user.name

const isAdmin = () => can('manage', 'all')

const actionMenus = (item: StockAdjustmentList): string[] => {
    const menus: string[] = []
    const status     = (item.status ?? '').toLowerCase()
    const canApprove = can('approve', 'stock_adjustment')
    const canManage  = isAdmin() || isCreator(item)

    menus.push('detail')

    if (canManage && (status === 'draft' || status === 'revised')) {
        menus.push('update', 'submit')
    }

    if (canApprove && status === 'waiting approval') {
        menus.push('approve', 'reject')
    }

    if (canManage && status === 'rejected') {
        menus.push('revise')
    }

    if (canManage && status === 'draft') {
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
        adjustment_number: '',
        adjustment_date: new Date(),
        stock_opname_uid: null,
        stock_opname_number: null,
        notes: null,
        status: '',
        details: [],
        created_at: '',
        updated_at: null,
        created_by_name: '',
        updated_by_name: null,
    })
}

const handleClose = () => {
    resetForm()
    formDialog.value           = false
    detailDialog.value         = false
    confirmDialogDelete.value  = false
    confirmDialogApprove.value = false
    confirmDialogReject.value  = false
}

// =========================
// Actions
// =========================
const loadData = () => store.fetch(options.value)

const handleActionMenu = (action: string, data: StockAdjustmentList) => {
    const actionMap: Record<string, () => void> = {
        detail:  () => handleOpenDetail(data),
        update:  () => handleOpenForm(data, 'update'),
        submit:  () => handleOpenSubmit(data),
        approve: () => handleOpenApprove(data),
        reject:  () => handleOpenReject(data),
        revise:  () => handleRevise(data),
        delete:  () => handleOpenDelete(data.uid),
    }
    actionMap[action]?.()
}

const handleOpenForm = (item: StockAdjustmentList | null, action: 'create' | 'update' = 'create') => {
    if (item) {
        formAction.value = action
        Object.assign(formField, item)
    } else {
        formAction.value = 'create'
        resetForm()
    }
    formDialog.value = true
}

const handleOpenDetail = (item: StockAdjustmentList) => {
    Object.assign(formField, item)
    detailDialog.value = true
}

const handleOpenSubmit = (item: StockAdjustmentList) => {
    Object.assign(formField, { ...item, status: 'Waiting Approval' })
    confirmDialogApprove.value = true
}

const handleOpenApprove = (item: StockAdjustmentList) => {
    Object.assign(formField, { ...item, status: 'Approved' })
    confirmDialogApprove.value = true
}

const handleOpenReject = (item: StockAdjustmentList) => {
    Object.assign(formField, { ...item, status: 'Rejected' })
    confirmDialogReject.value = true
}

const handleRevise = async (item: StockAdjustmentList) => {
    if (item.uid) {
        await store.update(item.uid, {
            adjustment_date:  item.adjustment_date,
            stock_opname_uid: item.stock_opname_uid ?? null,
            notes:            item.notes,
            status:           'Revised',
            details: (item.details ?? []).map((d) => ({
                stock_unit_uid: d.stock_unit_uid ?? null,
                adjustment_qty: d.adjustment_qty ?? null,
                notes:          d.notes,
            })),
        })
    }
}

const handleOpenDelete = (uid: string) => {
    formField.uid = uid
    confirmDialogDelete.value = true
}

// =========================
// Submit Handlers
// =========================
const handleSubmit = async (value: StockAdjustmentForm) => {
    if (formAction.value === 'create') {
        await store.create(value)
    } else {
        if (formField.uid) {
            await store.update(formField.uid, { ...value, status: formField.status || 'Draft' })
        }
    }
    if (!store.error) handleClose()
}

const handleApprove = async () => {
    if (formField.uid) {
        await store.update(formField.uid, {
            adjustment_date:  formField.adjustment_date,
            stock_opname_uid: formField.stock_opname_uid ?? null,
            notes:            formField.notes,
            status:           formField.status, // 'Approved' or 'Waiting Approval' depending on action
            details: (formField.details ?? []).map((d) => ({
                stock_unit_uid: d.stock_unit_uid ?? null,
                adjustment_qty: d.adjustment_qty ?? null,
                notes:          d.notes,
            })),
        })
    }
    if (!store.error) handleClose()
}

const handleReject = async () => {
    if (formField.uid) {
        await store.update(formField.uid, {
            adjustment_date:  formField.adjustment_date,
            stock_opname_uid: formField.stock_opname_uid ?? null,
            notes:            formField.notes,
            status:           'Rejected',
            details: (formField.details ?? []).map((d) => ({
                stock_unit_uid: d.stock_unit_uid ?? null,
                adjustment_qty: d.adjustment_qty ?? null,
                notes:          d.notes,
            })),
        })
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
        <v-card-title class="d-flex align-center">
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

                <template #item.adjustment_date="{ item }">
                    {{ formatDate(item.adjustment_date as string) }}
                </template>

                <template #item.stock_opname_number="{ item }">
                    {{ item.stock_opname_number || '-' }}
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

                <template #item.notes="{ item }">
                    {{ item.notes || '-' }}
                </template>
            </v-data-table-server>
        </v-card-text>
    </v-card>

    <!-- Create / Update Form -->
    <stock-adjustment-form
        v-if="formDialog"
        v-model="formDialog"
        :title="formTitle"
        :data="formField"
        :saving="store.loading"
        @submit="handleSubmit"
        @close="handleClose"
    />

    <!-- Detail Drawer -->
    <stock-adjustment-detail
        v-if="detailDialog"
        v-model="detailDialog"
        :item="formField"
        @close="handleClose"
    />

    <!-- Approve / Submit Confirmation -->
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
