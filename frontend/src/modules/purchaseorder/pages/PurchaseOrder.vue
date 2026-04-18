<script lang="ts" setup>
import { computed, reactive, ref } from 'vue'
// Import Types
import type { DataTableHeader } from 'vuetify'
// Import Components
import BaseSearchInput from '@/components/base/BaseSearchInput.vue'
import BaseRefreshButton from '@/components/base/BaseRefreshButton.vue'
import BaseCreateButton from '@/components/base/BaseCreateButton.vue'
import ActionsMenu from '@/components/common/ActionsMenu.vue'
import PurchaseOrderDetail from '@/modules/purchaseorder/components/PurchaseOrderDetail.vue'
import PurchaseOrderForm from '@/modules/purchaseorder/components/PuchaseOrderForm.vue'
import ConfirmApproveDialog from '@/components/common/ConfirmApproveDialog.vue'
import ConfirmRejectDialog from '@/components/common/ConfirmRejectDialog.vue'
import ConfirmDeleteDialog from '@/components/common/ConfirmDeleteDialog.vue'
// Import Stores
import { usePurchaseOrderStore } from '@/stores/purchase_order.store'
// Import Composables
import { useTranslate } from '@/composables/useTranslate'
import { useAbility } from '@/composables/useAbility'
// Import Utils
// import { formatRupiah } from '@/utils/currency'
import { formatDate } from '@/utils/date'
// Import Icons
import MingcuteTimeLine from '~icons/mingcute/time-line'
import UisCheck from '~icons/uis/check'
import CodexCross from '~icons/codex/cross'
import CiCheckAll from '~icons/ci/check-all'
import FluentNotepad24Filled from '~icons/fluent/notepad-24-filled'
import MaterialSymbolsLightDraftRounded from '~icons/material-symbols-light/draft-rounded'
import { formatRupiah } from '@/utils/currency'

const t = useTranslate()
const store = usePurchaseOrderStore()
const ability = useAbility()

// =========================
// State
// =========================
const formDialog = ref<boolean>(false)
// true selama data form sedang dimuat setelah user menekan tombol tambah/edit
const openingForm = ref<boolean>(false)
const confirmDialog = ref<boolean>(false)
const confirmDialogDelete = ref<boolean>(false)
const confirmDialogApprove = ref<boolean>(false)
const confirmDialogReject = ref<boolean>(false)
const detailDialog = ref<boolean>(false)
const detailData = ref<any[]>([])
const formAction = ref<'create' | 'update' | 'revision'>('create')
const formField = reactive<PurchaseOrderList>({
    uid: '',
    item_request: null,
    po_number: '',
    po_date: new Date(),
    project_name: '',
    total_amount: 0,
    status: '',
    details: [] as PurchaseOrderDetail[] | null,
    approval_logs: null,
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
    { title: t('poNumber'), key: 'po_number', align: 'start', nowrap: true },
    { title: t('woNumber'), key: 'wo_number', align: 'start', nowrap: true },
    { title: t('poDate'), key: 'po_date', align: 'start', nowrap: true },
    { title: t('totalAmount'), key: 'total_amount', align: 'start', nowrap: true },
    { title: t('status'), key: 'status', align: 'start', nowrap: true }
])

// =========================
// Methods
// =========================
const loadData = () => store.fetch(options.value)

const resetForm = () => {
    Object.assign(formField, {
        uid: '',
        item_request: null,
        po_number: '',
        po_date: new Date(),
        project_name: '',
        item_request_uid: '',
        total_amount: 0,
        status: '',
        details: [] as PurchaseOrderDetail[] | null,
        approval_logs: null,
        created_at: '',
        updated_at: '',
        created_by_name: '',
        updated_by_name: ''
    })
}

const statusColor = (status: string) => {
    switch (status.toLowerCase()) {
        case 'waiting approval':
            return 'warning'
        case 'approved':
            return 'success'
        case 'rejected':
            return 'red'
        case 'revised':
            return 'deep-orange'
        case 'converted':
            return 'blue'
        default:
            return 'grey'
    }
}

const statusIcon = (status: string) => {
    switch (status.toLowerCase()) {
        case 'waiting approval':
            return MingcuteTimeLine
        case 'approved':
            return UisCheck
        case 'rejected':
            return CodexCross
        case 'revised':
            return FluentNotepad24Filled
        case 'converted':
            return CiCheckAll
        default:
            return MaterialSymbolsLightDraftRounded
    }
}

const actionMenus = (item?: any) => {
    const menus = []
    const status = item?.status?.toLowerCase() ?? ''
    const isWaiting = status === 'waiting approval'
    const isRejected = status === 'rejected'
    const isRevised = status === 'revised'

    const canRead = ability.can('read', 'purchase_order')
    const canApprove = ability.can('approve', 'purchase_order')
    const canManage = ability.can('manage', 'all')

    if (canManage || canRead || canApprove) menus.push('detail')

    if (canManage || ability.can('update', 'purchase_order')) {
        if (isWaiting) menus.push('update')
        if (isRejected) menus.push('revision')
    }

    if (canManage || canApprove) {
        if (isWaiting || isRevised) menus.push('approve', 'reject')
    }

    if (canManage || ability.can('delete', 'purchase_order')) {
        if (isWaiting) menus.push('delete')
    }

    return menus
}

const handleClose = () => {
    resetForm()
    formDialog.value = false
    detailDialog.value = false
    confirmDialog.value = false
    confirmDialogDelete.value = false
    confirmDialogApprove.value = false
    confirmDialogReject.value = false
}

// =========================
// Functions
// =========================
const handleActionMenu = (action: string, data: PurchaseOrderList) => {
    if (action === 'detail') {
        return handleOpenDetailDialog(data)
    }
    if (action === 'update') {
        return handleOpenDialog(data)
    }
    if (action === 'approve') {
        return handleOpenDialogApprove(data)
    }
    if (action === 'reject') {
        return handleOpenDialogReject(data)
    }
    if (action === 'revision') {
        return handleOpenDialog(data)
    }
    if (action === 'delete') {
        return handleOpenDeleteDialog(data)
    }
    return handleOpenConfirmDialog(data?.uid ?? '')
}

const handleOpenDialog = (item: PurchaseOrderList | null) => {
    if (item && item.uid) {
        if (item.status && item.status.toLowerCase() === 'rejected') {
            formAction.value = 'revision'
        } else {
            formAction.value = 'update'
        }
        Object.assign(formField, item)
    } else {
        formAction.value = 'create'
        resetForm()
    }
    openingForm.value = true
    formDialog.value = true
}

const handleFormReady = () => {
    openingForm.value = false
}

const handleOpenDeleteDialog = (item: PurchaseOrderList | null) => {
    if (item && item.uid) {
        formField.uid = item.uid
        confirmDialogDelete.value = true
    }
}

const handleOpenDialogApprove = (item: PurchaseOrderList | null) => {
    Object.assign(formField, {
        ...item,
        details: item && item.details ? item.details.map(d => ({
            item: d.item ?? null,
            unit: d.unit ?? null,
            supplier: d.supplier ?? null,
            qty: d.qty ?? 0,
            price: d.price ?? 0,
            total: d.total ?? 0
        })) : null,
        status: 'Approved'
    })
    confirmDialogApprove.value = true
}

const handleOpenDialogReject = (item: PurchaseOrderList | null) => {
    Object.assign(formField, {
        ...item,
        details: item && item.details ? item.details.map(d => ({
            item: d.item ?? null,
            unit: d.unit ?? null,
            supplier: d.supplier ?? null,
            qty: d.qty ?? 0,
            price: d.price ?? 0,
            total: d.total ?? 0
        })) : null,
        status: 'Rejected'
    })
    confirmDialogReject.value = true
}

const handleOpenConfirmDialog = (uid: string) => {
    formField.uid = uid
    confirmDialog.value = true
}

const handleOpenDetailDialog = (item: PurchaseOrderList) => {
    detailData.value = []
    resetForm()
    try {
        Object.assign(formField, {
            ...item,
            details: item && item.details ? item.details.map(d => ({
                item: d.item ?? null,
                unit: d.unit ?? null,
                supplier: d.supplier ?? null,
                qty: d.qty ?? 0,
                price: d.price ?? 0,
                total: d.total ?? 0
            })) : null,
            approval_logs: item.approval_logs ?? null
        })
        detailDialog.value = true
    } catch (err) {
        console.error("Error: ", err)
    }
}

const handleSubmit = (value: PurchaseOrderForm) => {
    if (formAction.value === 'create') {
            const payload = {
                item_request_uid: value.item_request_uid,
                po_date: value.po_date,
                project_name: value.project_name,
                total_amount: value.total_amount,
                details: value.details.map(d => ({
                    item_uid: d.item_uid,
                    unit_uid: d.unit_uid,
                    supplier_uid: d.supplier_uid,
                    qty: d.qty,
                    price: d.price,
                    total: d.total
                }))
            }
            store.create(payload)
    } else {
        if (formField.uid) {
            const payload = {
                item_request_uid: value.item_request_uid,
                po_date: value.po_date,
                project_name: value.project_name,
                total_amount: value.total_amount,
                status: formAction.value === 'revision' ? 'Revised' : value.status,
                details: value.details.map(d => ({
                    item_uid: d.item_uid,
                    unit_uid: d.unit_uid,
                    supplier_uid: d.supplier_uid,
                    qty: d.qty ?? 0,
                    price: d.price ?? 0,
                    total: d.total ?? 0,
                }))
            }
            store.update(formField.uid, payload)
        }
    }
    handleClose()
}

const handleApprove = (notes: string = '') => {
    if (formField.uid) {
        store.approve(formField.uid, { status: 'Approved', notes: notes || undefined })
    }
    handleClose()
}

const handleReject = (notes: string = '') => {
    if (formField.uid) {
        store.approve(formField.uid, { status: 'Rejected', notes: notes || undefined })
    }
    handleClose()
}

const handleDelete = () => {
    if (formField.uid) {
        store.delete(formField.uid)
    }
    handleClose()
}
</script>

<template>
    <v-card>
        <v-card-title class="d-flex flex-column flex-sm-row-reverse justify-space-between align-sm-center">
            <base-create-button
                :loading="store.loading || openingForm"
                :disabled="store.loading || openingForm"
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
                        :menus="actionMenus(item)"
                        :data="item"
                        @click="handleActionMenu"
                    />
                </template>

                <template v-slot:item.wo_number="{ item }">
                    {{ item.wo_number ?? item.item_request?.wo_number ?? '-' }}
                </template>

                <template v-slot:item.total_amount="{ item }">
                    {{ formatRupiah(item.total_amount) }}
                </template>

                <template v-slot:item.status="{ item }">
                    <v-chip
                        :prepend-icon="statusIcon(item.status)"
                        :color="statusColor(item.status)"
                        variant="outlined"
                    >
                        {{ item.status.toLowerCase() }}
                    </v-chip>
                </template>
            </v-data-table-server>
        </v-card-text>
    </v-card>

    <purchase-order-form
        v-if="formDialog"
        v-model="formDialog"
        :title="formTitle"
        :data="formField"
        @submit="handleSubmit"
        @close="handleClose"
        @ready="handleFormReady"
    />

    <confirm-approve-dialog
        v-model="confirmDialogApprove"
        :with-notes="true"
        @approve="handleApprove"
        @close="handleClose"
    />

    <confirm-reject-dialog
        v-model="confirmDialogReject"
        :with-notes="true"
        @reject="handleReject"
        @close="handleClose"
    />

    <confirm-delete-dialog
        v-model="confirmDialogDelete"
        @delete="handleDelete"
        @close="handleClose"
    />

    <purchase-order-detail
        v-model="detailDialog"
        :items="formField"
        @close="handleClose"
    />
</template>

<style scoped></style>