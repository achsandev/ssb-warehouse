<script lang="ts" setup>
import { computed, reactive, ref } from 'vue'
import type { DataTableHeader } from 'vuetify'
// Components
import BaseSearchInput from '@/components/base/BaseSearchInput.vue'
import BaseRefreshButton from '@/components/base/BaseRefreshButton.vue'
import BaseCreateButton from '@/components/base/BaseCreateButton.vue'
import ActionsMenu from '@/components/common/ActionsMenu.vue'
import ConfirmApproveDialog from '@/components/common/ConfirmApproveDialog.vue'
import ConfirmRejectDialog from '@/components/common/ConfirmRejectDialog.vue'
import ConfirmDeleteDialog from '@/components/common/ConfirmDeleteDialog.vue'
import CashPurchaseForm from '../components/CashPurchaseForm.vue'
import CashPurchaseDetail from '../components/CashPurchaseDetail.vue'
// Stores
import { useCashPurchaseStore } from '@/stores/cash_purchase'
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
import FluentNotepad24Filled from '~icons/fluent/notepad-24-filled'
import MaterialSymbolsLightDraftRounded from '~icons/material-symbols-light/draft-rounded'

const t         = useTranslate()
const store     = useCashPurchaseStore()
const authStore = useAuthStore()
const { can }   = useAbility()

// =========================
// State
// =========================
const formDialog           = ref(false)
const detailDialog         = ref(false)
const confirmDialogDelete  = ref(false)
const confirmDialogApprove = ref(false)
const confirmDialogReject  = ref(false)
const formAction           = ref<'create' | 'update' | 'revised'>('create')

const formField = reactive<CashPurchaseList>({
    uid:             '',
    purchase_number: '',
    purchase_date:   new Date() as any,
    warehouse_uid:   '',
    warehouse_name:  '',
    cash_balance:    0,
    po_uid:          '',
    po_number:       '',
    po_total_amount: 0,
    po_details:      [],
    notes:           null,
    status:          '',
    created_at:      '',
    updated_at:      null,
    created_by_name: '',
    updated_by_name: null,
})

const formTitle = computed(() => {
    if (formAction.value === 'create')  return t('createDataDialogTitle')
    if (formAction.value === 'revised') return t('revisedDataDialogTitle')
    return t('updateDataDialogTitle')
})

// =========================
// Table Options
// =========================
const itemPerPageOptions = [
    { title: '5',   value: 5   },
    { title: '10',  value: 10  },
    { title: '25',  value: 25  },
    { title: '50',  value: 50  },
    { title: '100', value: 100 },
    { title: 'All', value: -1  },
]

const options = ref<TableParams>({
    page:         1,
    itemsPerPage: 10,
    sortBy:       [{ key: 'created_at', order: 'desc' }],
    search:       '',
})

const headers = computed<DataTableHeader[]>(() => [
    { title: t('actions'),       key: 'action',          align: 'center', width: '20', sortable: false },
    { title: t('purchaseNumber'), key: 'purchase_number', align: 'start',  nowrap: true },
    { title: t('purchaseDate'),   key: 'purchase_date',   align: 'start',  nowrap: true },
    { title: t('warehouse'),      key: 'warehouse_name',  align: 'start',  nowrap: true },
    { title: t('poNumber'),       key: 'po_number',       align: 'start',  nowrap: true },
    { title: t('poTotalAmount'),  key: 'po_total_amount', align: 'end',    nowrap: true },
    { title: t('status'),         key: 'status',          align: 'start',  nowrap: true },
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
        default:                 return 'grey'
    }
}

const statusIcon = (status: string) => {
    switch ((status ?? '').toLowerCase()) {
        case 'waiting approval': return MingcuteTimeLine
        case 'approved':         return UisCheck
        case 'rejected':         return CodexCross
        case 'revised':          return FluentNotepad24Filled
        default:                 return MaterialSymbolsLightDraftRounded
    }
}

const isCreator = (item: CashPurchaseList) =>
    !!authStore.user?.name && item.created_by_name === authStore.user.name

const isAdmin = () => can('manage', 'all')

const actionMenus = (item: CashPurchaseList): string[] => {
    const menus: string[] = []
    const status    = (item.status ?? '').toLowerCase()
    const canApprove = can('approve', 'cash_purchase')
    const canManage  = isAdmin() || isCreator(item)

    menus.push('detail')

    if (canApprove && (status === 'waiting approval' || status === 'revised')) {
        menus.push('approve', 'reject')
    }

    if (canManage && status === 'waiting approval') {
        menus.push('update')
    }

    if (canManage && status === 'rejected') {
        menus.push('revised')
    }

    if (canManage && (status === 'waiting approval' || status === 'revised')) {
        menus.push('delete')
    }

    return menus
}

// =========================
// State Management
// =========================
const resetForm = () => {
    Object.assign(formField, {
        uid:             '',
        purchase_number: '',
        purchase_date:   new Date(),
        warehouse_uid:   '',
        warehouse_name:  '',
        cash_balance:    0,
        po_uid:          '',
        po_number:       '',
        po_total_amount: 0,
        po_details:      [],
        notes:           null,
        status:          '',
        created_at:      '',
        updated_at:      null,
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

const handleActionMenu = (action: string, data: CashPurchaseList) => {
    const map: Record<string, () => void> = {
        detail:  () => handleOpenDetail(data),
        update:  () => handleOpenForm(data, 'update'),
        revised: () => handleOpenForm(data, 'revised'),
        approve: () => handleOpenApprove(data),
        reject:  () => handleOpenReject(data),
        delete:  () => handleOpenDelete(data.uid),
    }
    map[action]?.()
}

const handleOpenForm = (
    item: CashPurchaseList | null,
    action: 'create' | 'update' | 'revised' = 'create',
) => {
    if (item) {
        formAction.value = action
        Object.assign(formField, item)
    } else {
        formAction.value = 'create'
        resetForm()
    }
    formDialog.value = true
}

const handleOpenDetail = (item: CashPurchaseList) => {
    Object.assign(formField, item)
    detailDialog.value = true
}

const handleOpenApprove = (item: CashPurchaseList) => {
    Object.assign(formField, { ...item, status: 'Approved' })
    confirmDialogApprove.value = true
}

const handleOpenReject = (item: CashPurchaseList) => {
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
const handleSubmit = async (value: CashPurchaseForm) => {
    if (formAction.value === 'create') {
        await store.create(value)
    } else if (formField.uid) {
        const status = formAction.value === 'revised' ? 'Revised' : 'Waiting Approval'
        await store.update(formField.uid, { ...value, status })
    }
    if (!store.error) handleClose()
}

const buildPayload = (status: string): CashPurchaseForm & { status: string } => ({
    purchase_date:  formField.purchase_date,
    warehouse_uid:  formField.warehouse_uid,
    po_uid:         formField.po_uid,
    notes:          formField.notes,
    status,
})

const handleApprove = async () => {
    if (formField.uid) await store.update(formField.uid, buildPayload('Approved'))
    if (!store.error) handleClose()
}

const handleReject = async () => {
    if (formField.uid) await store.update(formField.uid, buildPayload('Rejected'))
    if (!store.error) handleClose()
}

const handleDelete = async () => {
    if (formField.uid) await store.delete(formField.uid)
    handleClose()
}
</script>

<template>
    <v-card>
        <v-card-title class="d-flex flex-column flex-sm-row-reverse justify-space-between align-sm-center">
            <base-create-button
                v-if="can('create', 'cash_purchase')"
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

                <template #item.purchase_date="{ item }">
                    {{ formatDate(item.purchase_date as string) }}
                </template>

                <template #item.po_total_amount="{ item }">
                    Rp {{ Number(item.po_total_amount ?? 0).toLocaleString('id-ID') }}
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

    <!-- Form Dialog -->
    <cash-purchase-form
        v-if="formDialog"
        v-model="formDialog"
        :title="formTitle"
        :data="formField"
        :saving="store.loading"
        @submit="handleSubmit"
        @close="handleClose"
    />

    <!-- Detail Drawer -->
    <cash-purchase-detail
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
