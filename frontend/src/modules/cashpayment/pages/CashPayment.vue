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
import CashPaymentForm from '../components/CashPaymentForm.vue'
import CashPaymentDetail from '../components/CashPaymentDetail.vue'
// Stores
import { useCashPaymentStore } from '@/stores/cash_payment'
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

const t         = useTranslate()
const store     = useCashPaymentStore()
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
const formAction = ref<'create' | 'update'>('create')

const emptyFormField: CashPaymentList = {
    uid:               '',
    payment_number:    '',
    payment_date:      new Date() as unknown as string,
    warehouse_uid:     null,
    warehouse_name:    '',
    cash_balance:      0,
    description:       null,
    amount:            null,
    spk_path:          null,
    spk_name:          null,
    spk_url:           null,
    attachment_path:   null,
    attachment_name:   null,
    attachment_url:    null,
    notes:             null,
    status:            '',
    created_at:        '',
    updated_at:        null,
    created_by_name:   '',
    updated_by_name:   null,
}

const formField = reactive<CashPaymentList>({ ...emptyFormField })

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
    { title: t('actions'),       key: 'action',          align: 'center', width: '20', sortable: false },
    { title: t('paymentNumber'), key: 'payment_number',  align: 'start', nowrap: true },
    { title: t('paymentDate'),   key: 'payment_date',    align: 'start', nowrap: true },
    { title: t('warehouse'),     key: 'warehouse_name',  align: 'start' },
    { title: t('paymentDescription'), key: 'description', align: 'start' },
    { title: t('paymentAmount'), key: 'amount',          align: 'end', nowrap: true },
    { title: t('status'),        key: 'status',          align: 'start', nowrap: true },
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

const isCreator = (item: CashPaymentList) =>
    !!authStore.user?.name && item.created_by_name === authStore.user.name

const isAdmin = () => can('manage', 'all')

const actionMenus = (item: CashPaymentList): string[] => {
    const menus: string[]  = []
    const status           = (item.status ?? '').toLowerCase()
    const canApprove       = can('approve', 'cash_payment')
    const canManage        = isAdmin() || isCreator(item)

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
    Object.assign(formField, { ...emptyFormField })
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

const handleActionMenu = (action: string, data: CashPaymentList) => {
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

const handleOpenForm = (item: CashPaymentList | null, action: 'create' | 'update' = 'create') => {
    if (item) {
        formAction.value = action
        Object.assign(formField, item)
    } else {
        formAction.value = 'create'
        resetForm()
    }
    formDialog.value = true
}

const handleOpenDetail = (item: CashPaymentList) => {
    Object.assign(formField, item)
    detailDialog.value = true
}

const handleOpenSubmit = (item: CashPaymentList) => {
    Object.assign(formField, { ...item, status: 'Waiting Approval' })
    confirmDialogApprove.value = true
}

const handleOpenApprove = (item: CashPaymentList) => {
    Object.assign(formField, { ...item, status: 'Approved' })
    confirmDialogApprove.value = true
}

const handleOpenReject = (item: CashPaymentList) => {
    Object.assign(formField, { ...item, status: 'Rejected' })
    confirmDialogReject.value = true
}

const handleRevise = async (item: CashPaymentList) => {
    if (item.uid) {
        await store.update(item.uid, {
            payment_date:  item.payment_date,
            warehouse_uid: item.warehouse_uid ?? null,
            description:   item.description,
            amount:        item.amount,
            notes:         item.notes,
            status:        'Revised',
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
const handleSubmit = async (value: CashPaymentForm) => {
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
            payment_date:  formField.payment_date,
            warehouse_uid: formField.warehouse_uid ?? null,
            description:   formField.description,
            amount:        formField.amount,
            notes:         formField.notes,
            status:        formField.status, // 'Approved' or 'Waiting Approval'
        })
    }
    if (!store.error) handleClose()
}

const handleReject = async () => {
    if (formField.uid) {
        await store.update(formField.uid, {
            payment_date:  formField.payment_date,
            warehouse_uid: formField.warehouse_uid ?? null,
            description:   formField.description,
            amount:        formField.amount,
            notes:         formField.notes,
            status:        'Rejected',
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
            <!-- Search + Add button -->
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
            <v-spacer />
            <base-create-button
                v-if="can('create', 'cash_payment')"
                :loading="store.loading"
                :disabled="store.loading"
                @click="handleOpenForm(null)"
            />
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
                <!-- Action -->
                <template #item.action="{ item }">
                    <actions-menu
                        :menus="actionMenus(item)"
                        :data="item"
                        @click="handleActionMenu"
                    />
                </template>

                <!-- Payment Date -->
                <template #item.payment_date="{ item }">
                    {{ formatDate(item.payment_date as string) }}
                </template>

                <!-- Amount -->
                <template #item.amount="{ item }">
                    Rp {{ Number(item.amount ?? 0).toLocaleString('id-ID') }}
                </template>

                <!-- Description (truncated) -->
                <template #item.description="{ item }">
                    <span class="text-truncate d-inline-block" style="max-width:200px" :title="item.description ?? ''">
                        {{ item.description || '-' }}
                    </span>
                </template>

                <!-- Status chip -->
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

    <!-- Create / Update Form -->
    <cash-payment-form
        v-if="formDialog"
        v-model="formDialog"
        :title="formTitle"
        :data="formField"
        :saving="store.loading"
        @submit="handleSubmit"
        @close="handleClose"
    />

    <!-- Detail Drawer -->
    <cash-payment-detail
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
