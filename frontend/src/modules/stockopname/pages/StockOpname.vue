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
import ConfirmReviseDialog from '@/components/common/ConfirmReviseDialog.vue'
import ConfirmDeleteDialog from '@/components/common/ConfirmDeleteDialog.vue'
import StockOpnameForm from '../components/StockOpnameForm.vue'
import StockOpnameCountForm from '../components/StockOpnameCountForm.vue'
import StockOpnameDetail from '../components/StockOpnameDetail.vue'
import StockOpnamePrint from '../components/StockOpnamePrint.vue'
// Stores
import { useStockOpnameStore } from '@/stores/stock_opname'
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
import MdiPencil from '~icons/mdi/pencil'

const t = useTranslate()
const store = useStockOpnameStore()
const authStore = useAuthStore()
const { can } = useAbility()

// =========================
// State
// =========================
const formDialog        = ref(false)
const detailDialog      = ref(false)
const countDialog       = ref(false)
const printDialog       = ref(false)
const confirmDialogDelete  = ref(false)
const confirmDialogApprove = ref(false)
const confirmDialogReject  = ref(false)
const confirmDialogRevise  = ref(false)
const formAction = ref<'create' | 'update'>('create')

const formField = reactive<StockOpnameList>({
    uid: '',
    opname_number: '',
    opname_date: new Date(),
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
    { title: t('actions'),      key: 'action',        align: 'center', width: '20', sortable: false },
    { title: t('opnameNumber'), key: 'opname_number',  align: 'start', nowrap: true },
    { title: t('opnameDate'),   key: 'opname_date',    align: 'start', nowrap: true },
    { title: t('status'),       key: 'status',         align: 'start', nowrap: true },
    { title: t('notes'),        key: 'notes',          align: 'start' },
])

// =========================
// Helpers
// =========================
const statusColor = (status: string) => {
    switch ((status ?? '').toLowerCase()) {
        case 'waiting approval': return 'warning'
        case 'approved':         return 'success'
        case 'rejected':         return 'red'
        case 'revised':          return 'orange'
        default:                 return 'grey'
    }
}

const statusIcon = (status: string) => {
    switch ((status ?? '').toLowerCase()) {
        case 'waiting approval': return MingcuteTimeLine
        case 'approved':         return UisCheck
        case 'rejected':         return CodexCross
        case 'revised':          return MdiPencil
        default:                 return MaterialSymbolsLightDraftRounded
    }
}

const isCreator = (item: StockOpnameList) =>
    !!authStore.user?.name && item.created_by_name === authStore.user.name

const isAdmin = () => can('manage', 'all')

const actionMenus = (item: StockOpnameList): string[] => {
    const menus: string[] = []
    const status   = (item.status ?? '').toLowerCase()
    const canApprove = can('approve', 'stock_opname')
    const canManage  = isAdmin() || isCreator(item)

    menus.push('detail', 'print')

    if (canManage && status === 'draft') {
        menus.push('update', 'enterCount')
    }

    if (canManage && status === 'waiting approval') {
        menus.push('enterCount')
    }

    if (canApprove && status === 'waiting approval') {
        menus.push('approve', 'reject')
    }

    if (canManage && status === 'rejected') {
        menus.push('revise')
    }

    if (canManage && status === 'revised') {
        menus.push('enterCount')
    }

    if (canManage && (status === 'draft' || status === 'waiting approval' || status === 'revised')) {
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
        opname_number: '',
        opname_date: new Date(),
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
    countDialog.value          = false
    printDialog.value          = false
    confirmDialogDelete.value  = false
    confirmDialogApprove.value = false
    confirmDialogReject.value  = false
    confirmDialogRevise.value  = false
}

// =========================
// Actions
// =========================
const loadData = () => store.fetch(options.value)

const handleActionMenu = (action: string, data: StockOpnameList) => {
    const actionMap: Record<string, () => void> = {
        detail:     () => handleOpenDetail(data),
        print:      () => handleOpenPrint(data),
        update:     () => handleOpenForm(data, 'update'),
        enterCount: () => handleOpenCount(data),
        approve:    () => handleOpenApprove(data),
        reject:     () => handleOpenReject(data),
        revise:     () => handleOpenRevise(data),
        delete:     () => handleOpenDelete(data.uid),
    }
    actionMap[action]?.()
}

const handleOpenForm = (item: StockOpnameList | null, action: 'create' | 'update' = 'create') => {
    if (item) {
        formAction.value = action
        Object.assign(formField, item)
    } else {
        formAction.value = 'create'
        resetForm()
    }
    formDialog.value = true
}

const handleOpenDetail = (item: StockOpnameList) => {
    Object.assign(formField, item)
    detailDialog.value = true
}

const handleOpenPrint = (item: StockOpnameList) => {
    Object.assign(formField, item)
    printDialog.value = true
}

const handleOpenCount = (item: StockOpnameList) => {
    Object.assign(formField, item)
    countDialog.value = true
}

const handleOpenApprove = (item: StockOpnameList) => {
    Object.assign(formField, { ...item, status: 'Approved' })
    confirmDialogApprove.value = true
}

const handleOpenReject = (item: StockOpnameList) => {
    Object.assign(formField, { ...item, status: 'Rejected' })
    confirmDialogReject.value = true
}

const handleOpenRevise = (item: StockOpnameList) => {
    Object.assign(formField, item)
    confirmDialogRevise.value = true
}

const handleOpenDelete = (uid: string) => {
    formField.uid = uid
    confirmDialogDelete.value = true
}

// =========================
// Submit Handlers
// =========================
const handleSubmit = async (value: StockOpnameForm) => {
    if (formAction.value === 'create') {
        await store.create(value)
    } else {
        if (formField.uid) {
            await store.update(formField.uid, { ...value, status: 'Draft' })
        }
    }
    if (!store.error) handleClose()
}

const handleCountSubmit = async (value: StockOpnameCountForm) => {
    if (formField.uid) {
        await store.enterCount(formField.uid, value)
    }
    if (!store.error) handleClose()
}

const handleApprove = async () => {
    if (formField.uid) {
        await store.update(formField.uid, {
            opname_date: formField.opname_date,
            notes: formField.notes,
            status: 'Approved',
            details: (formField.details ?? []).map((d) => ({
                stock_unit_uid: d.stock_unit_uid ?? null,
                notes: d.notes,
            })),
        })
    }
    if (!store.error) handleClose()
}

const handleReject = async () => {
    if (formField.uid) {
        await store.update(formField.uid, {
            opname_date: formField.opname_date,
            notes: formField.notes,
            status: 'Rejected',
            details: (formField.details ?? []).map((d) => ({
                stock_unit_uid: d.stock_unit_uid ?? null,
                notes: d.notes,
            })),
        })
    }
    if (!store.error) handleClose()
}

const handleRevise = async () => {
    if (formField.uid) {
        await store.revise(formField.uid)
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

                <template #item.opname_date="{ item }">
                    {{ formatDate(item.opname_date as string) }}
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
    <stock-opname-form
        v-if="formDialog"
        v-model="formDialog"
        :title="formTitle"
        :data="formField"
        :saving="store.loading"
        @submit="handleSubmit"
        @close="handleClose"
    />

    <!-- Enter Count Form -->
    <stock-opname-count-form
        v-if="countDialog"
        v-model="countDialog"
        :data="formField"
        :saving="store.loading"
        @submit="handleCountSubmit"
        @close="handleClose"
    />

    <!-- Detail Drawer -->
    <stock-opname-detail
        v-if="detailDialog"
        v-model="detailDialog"
        :item="formField"
        @close="handleClose"
    />

    <!-- Print Dialog -->
    <stock-opname-print
        v-if="printDialog"
        v-model="printDialog"
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

    <!-- Revise Confirmation -->
    <confirm-revise-dialog
        v-model="confirmDialogRevise"
        @revise="handleRevise"
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
