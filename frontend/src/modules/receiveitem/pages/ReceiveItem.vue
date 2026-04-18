<script lang="ts" setup>
import { computed, reactive, ref } from 'vue'
// Import Types
import type { DataTableHeader } from 'vuetify'
// Import Components
import BaseSearchInput from '@/components/base/BaseSearchInput.vue'
import BaseRefreshButton from '@/components/base/BaseRefreshButton.vue'
import BaseCreateButton from '@/components/base/BaseCreateButton.vue'
import ActionsMenu from '@/components/common/ActionsMenu.vue'
import ReceiveItemDetail from '@/modules/receiveitem/components/ReceiveItemDetail.vue'
import ReceiveItemForm from '@/modules/receiveitem/components/ReceiveItemForm.vue'
import ConfirmApproveDialog from '@/components/common/ConfirmApproveDialog.vue'
import ConfirmRejectDialog from '@/components/common/ConfirmRejectDialog.vue'
import ConfirmDeleteDialog from '@/components/common/ConfirmDeleteDialog.vue'
// Import Stores
import { useReceiveItemStore } from '@/stores/receive_item'
import { useAuthStore } from '@/stores/auth'
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

const t = useTranslate()
const store = useReceiveItemStore()
const { can } = useAbility()
const authStore = useAuthStore()

// =========================
// State
// =========================
const formDialog = ref<boolean>(false)
const confirmDialog = ref<boolean>(false)
const confirmDialogDelete = ref<boolean>(false)
const confirmDialogApprove = ref<boolean>(false)
const confirmDialogReject = ref<boolean>(false)
const detailDialog = ref<boolean>(false)
const detailData = ref<any[]>([])
const formAction = ref<'create' | 'update' | 'revision'>('create')

const formField = reactive<ReceiveItemList>({
    uid: '',
    receipt_number: '',
    receipt_date: new Date(),
    project_name: '',
    purchase_order: null,
    warehouse: null,
    shipping_cost: 0,
    additional_info: '',
    details: [] as ReceiveItemDetail[] | null,
    created_at: '',
    updated_at: '',
    created_by_name: '',
    updated_by_name: '',
    status: ''
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
    { title: t('receiptNumber'), key: 'receipt_number', align: 'start', nowrap: true },
    { title: t('receiptDate'), key: 'receipt_date', align: 'start', nowrap: true },
    { title: t('status'), key: 'status', align: 'start', nowrap: true }
])

// =========================
// Methods
// =========================
const loadData = () => store.fetch(options.value)

const resetForm = () => {
    Object.assign(formField, {
        uid: '',
        receipt_number: '',
        receipt_date: new Date(),
        project_name: '',
        purchase_order: null,
        warehouse: null,
        additional_info: '',
        details: [] as ReceiveItemDetail[] | null,
        created_at: '',
        updated_at: '',
        created_by_name: '',
        updated_by_name: '',
        status: ''

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

/** True jika user saat ini adalah pembuat dokumen. */
const isCreator = (item: ReceiveItemList) =>
    !!authStore.user?.name && item.created_by_name === authStore.user.name

const isAdmin = () => can('manage', 'all')

/**
 * Menyusun daftar aksi per baris berdasarkan:
 * - Role: admin, approver (permission approve), atau creator.
 * - Status: menentukan aksi mana yang masih valid.
 */
const actionMenus = (item: ReceiveItemList): string[] => {
    const menus: string[] = []
    const status = (item.status ?? '').toLowerCase()
    const canApprove = can('approve', 'receive_item')
    const canManage = isAdmin() || isCreator(item)

    menus.push('detail')

    // Approve / Reject: user dengan permission approve saat status menunggu atau sudah direvisi.
    if (canApprove && (status === 'waiting approval' || status === 'revised')) {
        menus.push('approve', 'reject')
    }

    // Update: admin atau creator saat draft / waiting approval.
    if (canManage && (status === 'draft' || status === 'waiting approval')) {
        menus.push('update')
    }

    // Revision: admin atau creator saat rejected.
    if (canManage && status === 'rejected') {
        menus.push('revision')
    }

    // Delete: admin atau creator saat masih bisa dimutasi.
    if (canManage && (status === 'draft' || status === 'waiting approval')) {
        menus.push('delete')
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
const handleActionMenu = (action: string, data: ReceiveItemList) => {
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

const handleOpenDialog = (item: ReceiveItemList | null) => {
    if (item && item.uid) {
        if (item.status && item.status.toLowerCase() === 'rejected') {
            formAction.value = 'revision'
        } else {
            formAction.value = 'update'
        }
        Object.assign(formField, item)
    } else {
        formAction.value = 'create'
    }
    formDialog.value = true
}

const handleOpenDeleteDialog = (item: ReceiveItemList | null) => {
    if (item && item.uid) {
        formField.uid = item.uid
        confirmDialogDelete.value = true
    }
}

const handleOpenDialogApprove = (item: ReceiveItemList | null) => {
    Object.assign(formField, {
        ...item,
        status: 'Approved',
        details: item && item.details ? item.details.map(d => ({
            item: d.item ?? null,
            unit: d.unit ?? null,
            supplier: d.supplier ?? null,
            qty: d.qty ?? 0,
            price: d.price ?? 0,
            total: d.total ?? 0,
            qty_received: d.qty_received ?? 0
        })) : null
    })
    confirmDialogApprove.value = true
}

const handleOpenDialogReject = (item: ReceiveItemList | null) => {
    Object.assign(formField, {
        ...item,
        status: 'Rejected',
        details: item && item.details ? item.details.map(d => ({
            item: d.item ?? null,
            unit: d.unit ?? null,
            supplier: d.supplier ?? null,
            qty: d.qty ?? 0,
            price: d.price ?? 0,
            total: d.total ?? 0,
            qty_received: d.qty_received ?? 0
        })) : null
    })
    confirmDialogReject.value = true
}

const handleOpenConfirmDialog = (uid: string) => {
    formField.uid = uid
    confirmDialog.value = true
}

const handleOpenDetailDialog = (item: ReceiveItemList) => {
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
                total: d.total ?? 0,
                qty_received: d.qty_received ?? 0
            })) : []
        })
        detailDialog.value = true
    } catch (err) {
        console.error("Error: ", err)
    }
}

const handleSubmit = (value: ReceiveItemForm) => {
    if (formAction.value === 'create') {
            const payload = {
                receipt_date: value.receipt_date,
                project_name: value.project_name,
                purchase_order_id: value.purchase_order_id,
                warehouse_id: value.warehouse_id,
                shipping_cost: value.shipping_cost ?? 0,
                additional_info: value.additional_info,
                details: value.details.map(d => ({
                    item_id: d.item_id,
                    unit_id: d.unit_id,
                    supplier_id: d.supplier_id,
                    qty: d.qty,
                    price: d.price,
                    total: d.total,
                    qty_received: d.qty_received
                }))
            }
            store.create(payload)
    } else {
        if (formField.uid) {
            const payload = {
                uid: formField.uid,
                receipt_date: value.receipt_date,
                project_name: value.project_name,
                purchase_order_id: value.purchase_order_id,
                warehouse_id: value.warehouse_id,
                shipping_cost: value.shipping_cost ?? 0,
                status: formAction.value === 'revision' ? 'Revised' : value.status,
                additional_info: value.additional_info,
                details: value.details.map(d => ({
                    item_id: d.item_id,
                    unit_id: d.unit_id,
                    supplier_id: d.supplier_id,
                    qty: d.qty,
                    price: d.price,
                    total: d.total,
                    qty_received: d.qty_received
                }))
            }
            store.update(formField.uid, payload)
        }
    }
    handleClose()
}

const handleApprove = () => {
    if (formField.uid && formField.details) {
        const payload = {
            uid: formField.uid,
            receipt_date: formField.receipt_date,
            project_name: formField.project_name,
            purchase_order_id: formField.purchase_order?.uid ?? null,
            warehouse_id: formField.warehouse?.uid ?? null,
            shipping_cost: formField.shipping_cost ?? 0,
            status: formField.status,
            additional_info: formField.additional_info,
            details: formField.details.map(d => ({
                item_id: d.item?.uid ?? null,
                unit_id: d.unit?.uid ?? null,
                supplier_id: d.supplier?.uid ?? null,
                qty: d.qty,
                price: d.price,
                total: d.total,
                qty_received: d.qty_received
            }))
        }
        store.update(formField.uid ?? '', payload)
    }
    handleClose()
}

const handleReject = (reason: string) => {
    if (!formField.uid || !formField.details) return
    const trimmed = (reason || '').trim()
    if (!trimmed) return

    const payload = {
        uid: formField.uid,
        receipt_date: formField.receipt_date,
        project_name: formField.project_name,
        purchase_order_id: formField.purchase_order?.uid ?? null,
        warehouse_id: formField.warehouse?.uid ?? null,
        shipping_cost: formField.shipping_cost ?? 0,
        status: 'Rejected',
        reject_reason: trimmed,
        additional_info: formField.additional_info,
        details: formField.details.map(d => ({
            item_id: d.item?.uid ?? null,
            unit_id: d.unit?.uid ?? null,
            supplier_id: d.supplier?.uid ?? null,
            qty: d.qty,
            price: d.price,
            total: d.total,
            qty_received: d.qty_received
        }))
    }
    store.update(formField.uid, payload)
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
                :loading="store.loading"
                :disabled="store.loading"
                @click="handleOpenDialog(null)"
            />
            <!-- handleOpenDialog(formField) -->

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

                <!-- <template v-slot:item.total_amount="{ item }">
                    {{ formatRupiah(item.total_amount) }}
                </template> -->

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

    <receive-item-form
        v-if="formDialog"
        v-model="formDialog"
        :title="formTitle"
        :data="formField"
        @submit="handleSubmit"
        @close="handleClose"
    />

    <confirm-approve-dialog
        v-model="confirmDialogApprove"
        @approve="handleApprove"
        @close="handleClose"
    />

    <confirm-reject-dialog
        v-model="confirmDialogReject"
        with-notes
        @reject="handleReject"
        @close="handleClose"
    />

    <confirm-delete-dialog
        v-model="confirmDialogDelete"
        @delete="handleDelete"
        @close="handleClose"
    />

    <receive-item-detail
        v-model="detailDialog"
        :items="formField"
        @close="handleClose"
    />
</template>

<style scoped></style>