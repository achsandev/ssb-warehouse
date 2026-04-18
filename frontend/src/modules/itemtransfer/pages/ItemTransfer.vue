<script lang="ts" setup>
import { computed, reactive, ref } from 'vue'
import type { DataTableHeader } from 'vuetify'
// Components
import BaseSearchInput from '@/components/base/BaseSearchInput.vue'
import BaseRefreshButton from '@/components/base/BaseRefreshButton.vue'
import BaseCreateButton from '@/components/base/BaseCreateButton.vue'
import ActionsMenu from '@/components/common/ActionsMenu.vue'
import ConfirmDeleteDialog from '@/components/common/ConfirmDeleteDialog.vue'
import ItemTransferForm from '../components/ItemTransferForm.vue'
import ItemTransferDetail from '../components/ItemTransferDetail.vue'
import TransferActionDialog from '../components/TransferActionDialog.vue'
import DisplacementWizard from '../components/DisplacementWizard.vue'
// Stores
import { useItemTransferStore } from '@/stores/item_transfer'
import { useAuthStore } from '@/stores/auth'
// Composables
import { useTranslate } from '@/composables/useTranslate'
import { useAbility } from '@/composables/useAbility'
// Utils
import { formatDate } from '@/utils/date'
// Icons
import MdiClockOutline from '~icons/mdi/clock-outline'
import MdiCheckCircleOutline from '~icons/mdi/check-circle-outline'
import MdiCloseCircleOutline from '~icons/mdi/close-circle-outline'
import MdiCancel from '~icons/mdi/cancel'
import MdiLinkVariant from '~icons/mdi/link-variant'
import MdiHelpCircleOutline from '~icons/mdi/help-circle-outline'

const t = useTranslate()
const store = useItemTransferStore()
const authStore = useAuthStore()
const { can } = useAbility()

// =========================
// State
// =========================
const formDialog = ref(false)
const detailDialog = ref(false)
const confirmDialogDelete = ref(false)
const actionDialog = ref(false)
const actionMode = ref<'approve' | 'reject' | 'cancel'>('approve')
const formAction = ref<'create' | 'update'>('create')

// Displacement (chain) state
const displacementDialog = ref(false)
const displacementParent = ref<{
    uid: string
    transfer_number: string
    source: {
        warehouse_uid: string
        warehouse_name: string
        rack_uid: string | null
        rack_name: string | null
        tank_uid: string | null
        tank_name: string | null
    }
    occupants: Array<{
        item_uid: string
        item_name: string
        unit_uid: string
        unit_name: string
        unit_symbol: string
        qty: number
    }>
} | null>(null)

const emptyForm = (): ItemTransferList => ({
    uid: '',
    transfer_number: '',
    transfer_date: new Date(),
    from_warehouse: null, from_rack: null, from_tank: null,
    to_warehouse: null, to_rack: null, to_tank: null,
    notes: null, reject_notes: null, status: '',
    has_pending_displacement: false, parent_transfer: null, child_transfers: null,
    details: [], logs: null,
    approved_by_name: null, approved_at: null, cancelled_at: null,
    created_at: '', updated_at: null, created_by_name: '', updated_by_name: null,
})

const formField = reactive<ItemTransferList>(emptyForm())
const detailItem = ref<ItemTransferList | null>(null)

const formTitle = computed(() =>
    formAction.value === 'create' ? t('createDataDialogTitle') : t('updateDataDialogTitle')
)

// =========================
// Table
// =========================
const itemPerPageOptions = [
    { title: '5', value: 5 }, { title: '10', value: 10 }, { title: '25', value: 25 },
    { title: '50', value: 50 }, { title: '100', value: 100 }, { title: 'All', value: -1 },
]

const options = ref<TableParams>({
    page: 1,
    itemsPerPage: 10,
    sortBy: [{ key: 'created_at', order: 'desc' }],
    search: '',
})

const headers = computed<DataTableHeader[]>(() => [
    { title: t('actions'), key: 'action', align: 'center', width: '20', sortable: false },
    { title: t('transferNumber'), key: 'transfer_number', align: 'start', nowrap: true },
    { title: t('transferDate'), key: 'transfer_date', align: 'start', nowrap: true },
    { title: t('source'), key: 'from_warehouse', align: 'start', nowrap: true },
    { title: t('destination'), key: 'to_warehouse', align: 'start', nowrap: true },
    { title: t('totalItems'), key: 'detail_count', align: 'center', width: 80, sortable: false },
    { title: t('status'), key: 'status', align: 'start', nowrap: true },
])

// =========================
// Status visual mapping
// =========================
const statusInfo = (status: string) => {
    switch ((status ?? '').toLowerCase()) {
        case 'waiting approval':
            return { color: 'warning', icon: MdiClockOutline }
        case 'approved':
            return { color: 'success', icon: MdiCheckCircleOutline }
        case 'rejected':
            return { color: 'error', icon: MdiCloseCircleOutline }
        case 'cancelled':
            return { color: 'grey', icon: MdiCancel }
        case 'pending displacement':
            return { color: 'deep-orange', icon: MdiLinkVariant }
        default:
            return { color: 'grey', icon: MdiHelpCircleOutline }
    }
}

const formatLocation = (
    warehouse: { name: string } | null,
    rack: { name: string } | null,
    tank: { name: string } | null,
) => {
    if (!warehouse) return '-'
    const parts = [warehouse.name]
    if (rack) parts.push(`(${rack.name})`)
    if (tank) parts.push(`(${tank.name})`)
    return parts.join(' ')
}

// =========================
// Permission helpers
// =========================
const isCreator = (item: ItemTransferList) =>
    !!authStore.user?.name && item.created_by_name === authStore.user.name

const isAdmin = computed(() => can('manage', 'all'))

const actionMenus = (item: ItemTransferList): string[] => {
    const menus: string[] = []
    const status = (item.status ?? '').toLowerCase()
    const canApprove = can('approve', 'item_transfer')
    const canUpdate = can('update', 'item_transfer')
    const canDelete = can('delete', 'item_transfer')

    menus.push('detail')

    // Approve / Reject — hanya saat Waiting Approval & belum ada child pending
    if (canApprove && status === 'waiting approval' && !item.has_pending_displacement) {
        menus.push('approve', 'reject')
    }

    // Update / Revisi — saat Waiting Approval (creator) atau Rejected
    if ((isAdmin.value || canUpdate) && (isCreator(item) || isAdmin.value)) {
        if (status === 'waiting approval' || status === 'rejected') {
            menus.push('update')
        }
    }

    // Cancel — saat Waiting Approval / Rejected / Pending Displacement
    if ((isAdmin.value || canUpdate) && (isCreator(item) || isAdmin.value)) {
        if (['waiting approval', 'rejected', 'pending displacement'].includes(status)) {
            menus.push('cancel')
        }
    }

    // Delete — hanya Cancelled atau Rejected (yang sudah tidak aktif)
    if ((isAdmin.value || canDelete) && (isCreator(item) || isAdmin.value)) {
        if (['cancelled', 'rejected'].includes(status)) {
            menus.push('delete')
        }
    }

    return menus
}

// =========================
// Lifecycle
// =========================
const loadData = () => store.fetch(options.value)

const handleClose = () => {
    Object.assign(formField, emptyForm())
    detailItem.value = null
    formDialog.value = false
    detailDialog.value = false
    confirmDialogDelete.value = false
    actionDialog.value = false
}

// =========================
// Action menu dispatcher
// =========================
const handleActionMenu = (action: string, data: ItemTransferList) => {
    const map: Record<string, () => void> = {
        detail:  () => openDetail(data),
        update:  () => openForm(data, 'update'),
        approve: () => openAction(data, 'approve'),
        reject:  () => openAction(data, 'reject'),
        cancel:  () => openAction(data, 'cancel'),
        delete:  () => openDelete(data.uid),
    }
    map[action]?.()
}

const openForm = async (item: ItemTransferList | null, action: 'create' | 'update' = 'create') => {
    formAction.value = action
    if (item) {
        // Fetch fresh detail dengan logs/child untuk edit
        const fresh = await store.fetchByUid(item.uid)
        Object.assign(formField, fresh ?? item)
    } else {
        Object.assign(formField, emptyForm())
    }
    formDialog.value = true
}

const openDetail = async (item: ItemTransferList) => {
    detailDialog.value = true
    detailItem.value = item // tampilkan dulu seadanya
    const fresh = await store.fetchByUid(item.uid)
    if (fresh) detailItem.value = fresh // refresh dengan logs/child
}

const openAction = (item: ItemTransferList, mode: 'approve' | 'reject' | 'cancel') => {
    Object.assign(formField, item)
    actionMode.value = mode
    actionDialog.value = true
}

const openDelete = (uid: string) => {
    formField.uid = uid
    confirmDialogDelete.value = true
}

// =========================
// Submit handlers
// =========================
type SubmitOpts = {
    setupDisplacement?: boolean
    occupants?: Array<{
        item_uid: string; item_name: string
        unit_uid: string; unit_name: string; unit_symbol: string
        qty: number
    }>
}

const handleSubmit = async (value: ItemTransferForm, opts?: SubmitOpts) => {
    let created: any = null

    if (formAction.value === 'create') {
        created = await store.create(value)
    } else if (formField.uid) {
        created = await store.update(formField.uid, value)
    }

    if (store.error) return

    // Jika user klik "Simpan + Atur Pemindahan", buka wizard.
    if (opts?.setupDisplacement && opts.occupants?.length && created) {
        displacementParent.value = {
            uid: created.uid,
            transfer_number: created.transfer_number,
            source: {
                warehouse_uid: created.to_warehouse?.uid ?? value.to_warehouse_uid ?? '',
                warehouse_name: created.to_warehouse?.name ?? '',
                rack_uid: created.to_rack?.uid ?? value.to_rack_uid ?? null,
                rack_name: created.to_rack?.name ?? null,
                tank_uid: created.to_tank?.uid ?? value.to_tank_uid ?? null,
                tank_name: created.to_tank?.name ?? null,
            },
            occupants: opts.occupants,
        }
        // Tutup form, buka wizard
        formDialog.value = false
        displacementDialog.value = true
        return
    }

    handleClose()
}

const handleDisplacementSubmit = async (payload: ItemTransferForm) => {
    const created = await store.create(payload)
    if (store.error) return
    if (created) {
        // Sukses — tutup wizard & reset
        displacementDialog.value = false
        displacementParent.value = null
    }
}

const handleDisplacementClose = () => {
    displacementDialog.value = false
    displacementParent.value = null
}

const handleActionConfirm = async (notes: string) => {
    if (!formField.uid) return

    if (actionMode.value === 'approve') {
        await store.approve(formField.uid)
    } else if (actionMode.value === 'reject') {
        await store.reject(formField.uid, notes)
    } else if (actionMode.value === 'cancel') {
        await store.cancel(formField.uid, notes)
    }

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
                v-if="can('create', 'item_transfer') || isAdmin"
                :loading="store.loading"
                :disabled="store.loading"
                @click="openForm(null)"
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

                <template #item.transfer_number="{ item }">
                    <div class="d-flex align-center ga-1">
                        <span class="font-weight-medium">{{ item.transfer_number }}</span>
                        <v-tooltip
                            v-if="item.has_pending_displacement"
                            text="Memiliki displacement transfer yang belum selesai"
                            location="top"
                        >
                            <template #activator="{ props: tProps }">
                                <v-icon v-bind="tProps" :icon="MdiLinkVariant" color="deep-orange" size="14" />
                            </template>
                        </v-tooltip>
                        <v-tooltip
                            v-if="item.parent_transfer"
                            :text="`Displacement dari ${item.parent_transfer.transfer_number}`"
                            location="top"
                        >
                            <template #activator="{ props: tProps }">
                                <v-icon v-bind="tProps" :icon="MdiLinkVariant" color="info" size="14" />
                            </template>
                        </v-tooltip>
                    </div>
                </template>

                <template #item.transfer_date="{ item }">
                    {{ formatDate(item.transfer_date as string) }}
                </template>

                <template #item.from_warehouse="{ item }">
                    {{ formatLocation(item.from_warehouse, item.from_rack, item.from_tank) }}
                </template>

                <template #item.to_warehouse="{ item }">
                    {{ formatLocation(item.to_warehouse, item.to_rack, item.to_tank) }}
                </template>

                <template #item.detail_count="{ item }">
                    <v-chip size="x-small" variant="tonal">{{ item.details?.length ?? 0 }}</v-chip>
                </template>

                <template #item.status="{ item }">
                    <v-chip
                        :prepend-icon="statusInfo(item.status).icon"
                        :color="statusInfo(item.status).color"
                        variant="outlined"
                        size="small"
                        class="text-capitalize"
                    >
                        {{ item.status }}
                    </v-chip>
                </template>
            </v-data-table-server>
        </v-card-text>
    </v-card>

    <!-- Form (Create / Update) -->
    <item-transfer-form
        v-if="formDialog"
        v-model="formDialog"
        :title="formTitle"
        :data="formField"
        :saving="store.loading"
        @submit="handleSubmit"
        @close="handleClose"
    />

    <!-- Detail Drawer -->
    <item-transfer-detail
        v-if="detailDialog"
        v-model="detailDialog"
        :item="detailItem"
        @close="handleClose"
    />

    <!-- Approve / Reject / Cancel Dialog -->
    <transfer-action-dialog
        v-model="actionDialog"
        :mode="actionMode"
        :transfer-number="formField.transfer_number"
        :loading="store.loading"
        @confirm="handleActionConfirm"
        @close="handleClose"
    />

    <!-- Delete Confirmation -->
    <confirm-delete-dialog
        v-model="confirmDialogDelete"
        @delete="handleDelete"
        @close="handleClose"
    />

    <!-- Displacement Wizard (chain transfer) -->
    <displacement-wizard
        v-if="displacementDialog && displacementParent"
        v-model="displacementDialog"
        :parent-transfer-uid="displacementParent.uid"
        :parent-transfer-number="displacementParent.transfer_number"
        :source="displacementParent.source"
        :occupants="displacementParent.occupants"
        :saving="store.loading"
        @submit="handleDisplacementSubmit"
        @close="handleDisplacementClose"
    />
</template>

<style scoped></style>
