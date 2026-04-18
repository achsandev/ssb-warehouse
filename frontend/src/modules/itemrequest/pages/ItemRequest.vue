<script lang="ts" setup>
import { computed, reactive, ref } from 'vue'
// Import Types
import type { DataTableHeader } from 'vuetify'
// Import Components
import BaseSearchInput from '@/components/base/BaseSearchInput.vue'
import BaseRefreshButton from '@/components/base/BaseRefreshButton.vue'
import BaseCreateButton from '@/components/base/BaseCreateButton.vue'
import ActionsMenu from '@/components/common/ActionsMenu.vue'
import ItemRequestDetail from '@/modules/itemrequest/components/ItemRequestDetail.vue'
import ItemRequestForm from '@/modules/itemrequest/components/ItemRequestForm.vue'
import ConfirmApproveDialog from '@/components/common/ConfirmApproveDialog.vue'
import ConfirmRejectDialog from '@/components/common/ConfirmRejectDialog.vue'
import ConfirmDeleteDialog from '@/components/common/ConfirmDeleteDialog.vue'
// Import Stores
import { useItemRequestStore } from '@/stores/item_request'
import { useAuthStore } from '@/stores/auth'
import { useSettingApproverItemRequestStore } from '@/stores/setting_approver_item_request'
// Import Composables
import { useTranslate } from '@/composables/useTranslate'
import { useAbility } from '@/composables/useAbility'
// Import Utils
// import { formatRupiah } from '@/utils/currency'
// import { formatDate } from '@/utils/date'
// Import Icons
import MingcuteTimeLine from '~icons/mingcute/time-line'
import UisCheck from '~icons/uis/check'
import CodexCross from '~icons/codex/cross'
import CiCheckAll from '~icons/ci/check-all'
import FluentNotepad24Filled from '~icons/fluent/notepad-24-filled'
import MaterialSymbolsLightDraftRounded from '~icons/material-symbols-light/draft-rounded'

const t = useTranslate()
const store = useItemRequestStore()
const ability = useAbility()
const authStore = useAuthStore()
const settingApproverStore = useSettingApproverItemRequestStore()

// Fetch approver settings on mount
settingApproverStore.fetchAll()

// =========================
// State
// =========================
const formDialog = ref<boolean>(false)
const confirmDialog = ref<boolean>(false)
const confirmDialogApprove = ref<boolean>(false)
const confirmDialogReject = ref<boolean>(false)
const confirmDialogDelete = ref<boolean>(false)
const detailDialog = ref<boolean>(false)
const detailData = ref<any[]>([])
const formAction = ref<'create' | 'update' | 'revision'>('create')
const emptyForm = (): ItemRequestList => ({
    uid: '',
    requirement: null,
    request_number: '',
    request_date: '',
    unit_code: null,
    wo_number: null,
    warehouse: null,
    is_project: true,
    project_name: null,
    department_name: authStore.user?.department_name || '',
    status: '',
    details: [] as ItemRequestDetail[] | null,
    created_at: '',
    updated_at: '',
    created_by_name: '',
    updated_by_name: '',
})

const formField = reactive<ItemRequestList>(emptyForm())

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
    { title: t('requestNumber'), key: 'request_number', align: 'start', nowrap: true },
    { title: t('woNumber'), key: 'wo_number', align: 'start', nowrap: true },
    { title: t('projectName'), key: 'project_name', align: 'start', nowrap: true },
    { title: t('departmentName'), key: 'department_name', align: 'start', nowrap: true },
    { title: t('requestDate'), key: 'request_date', align: 'start', nowrap: true },
    { title: t('status'), key: 'status', align: 'start', nowrap: true }
])

// =========================
// Methods
// =========================
const loadData = () => store.fetch(options.value)

const resetForm = () => {
    Object.assign(formField, emptyForm())
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
    if (ability.can('manage', 'all')) {
        menus.push('detail')
        if (item.status && item.status.toLowerCase() === 'waiting approval') {
            menus.push('update', 'approve', 'reject', 'delete')
        }
        if (item.status && item.status.toLowerCase() === 'rejected') {
            menus.push('revision')
        }
        if (item.status && item.status.toLowerCase() === 'revised') {
            menus.push('approve', 'reject')
        }
        return menus
    }

    if (ability.can('read', 'item_request')) menus.push('detail')
    if (ability.can('delete', 'item_request')) {
        if (item && item.status && item.status.toLowerCase() === 'waiting approval') {
            menus.push('delete')
        }
    }
    if (ability.can('update', 'item_request')) {
        if (item && item.status && item.status.toLowerCase() === 'rejected') {
            menus.push('revision')
        }

        if (item && item.status && item.status.toLowerCase() === 'waiting approval') {
            menus.push('update')
        }
    }
    if (item && ability.can('approve', 'item_request')) {
        if (item.status && (item.status.toLowerCase() === 'waiting approval' || item.status.toLowerCase() === 'revised')) {
            const requesterRoleId = item.requester_role_id || item.created_by_role_id
            const approverSetting = settingApproverStore.items.find(
                (s) => s.requester_role_id === requesterRoleId
            )
            const userRoleId = authStore.user?.role_id
            if (
                approverSetting &&
                userRoleId &&
                approverSetting.approver_role_id === userRoleId
            ) {
                menus.push('approve', 'reject')
            }
        }
    }
    return menus;
}

const handleClose = () => {
    resetForm()
    formDialog.value = false
    detailDialog.value = false
    confirmDialog.value = false
    confirmDialogApprove.value = false
    confirmDialogReject.value = false
    confirmDialogDelete.value = false
}

// =========================
// Functions
// =========================
const handleActionMenu = (action: string, data: ItemRequestList) => {
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
        return handleOpenDelete(data)
    }
    return handleOpenConfirmDialog(data?.uid ?? '')
}

const handleOpenDialog = (item: ItemRequestList) => {
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
    formDialog.value = true
}

const handleOpenDelete = (item: ItemRequestList) => {
    Object.assign(formField, {
        ...item,
        details: item && item.details ? item.details.map(d => ({
            item_request: d.item_request ?? null,
            item: d.item ?? null,
            unit: d.unit ?? null,
            qty: d.qty ?? 0,
            description: d.description ?? ''
        })) : null,
        status: 'Deleted'
    })
    confirmDialogDelete.value = true
}

const handleOpenDialogApprove = (item: ItemRequestList | null) => {
    Object.assign(formField, {
        ...item,
        details: item && item.details ? item.details.map(d => ({
            item_request: d.item_request ?? null,
            item: d.item ?? null,
            unit: d.unit ?? null,
            qty: d.qty ?? 0,
            description: d.description ?? ''
        })) : null,
        status: 'Approved'
    })
    confirmDialogApprove.value = true
}

const handleOpenDialogReject = (item: ItemRequestList | null) => {
    Object.assign(formField, {
        ...item,
        details: item && item.details ? item.details.map(d => ({
            item_request: d.item_request ?? null,
            item: d.item ?? null,
            unit: d.unit ?? null,
            qty: d.qty ?? 0,
            description: d.description ?? ''
        })) : null,
        status: 'Rejected'
    })
    confirmDialogReject.value = true
}

const handleOpenConfirmDialog = (uid: string) => {
    if (formField) {
        formField.uid = uid
        confirmDialog.value = true
    }
}

const handleOpenDetailDialog = (item: ItemRequestList) => {
    detailData.value = []
    resetForm()
    try {
        Object.assign(formField, {
            ...item,
            details: item && item.details ? item.details.map(d => ({
                item_request: d.item_request ?? null,
                item: d.item ?? null,
                unit: d.unit ?? null,
                qty: d.qty ?? 0,
                description: d.description ?? ''
            })) : null
        })
        detailDialog.value = true
    } catch (err) {
        console.error("Error: ", err)
    }
}

/** Build payload umum dari values form (create/update/revision). */
const buildFormPayload = (value: ItemRequestForm): ItemRequestForm => {
    const isProject = !!value.is_project
    return {
        requirement: value.requirement,
        request_date: value.request_date,
        unit_code: value.unit_code ?? null,
        wo_number: value.wo_number ?? null,
        warehouse_uid: value.warehouse_uid ?? null,
        is_project: isProject,
        // Non-project: paksa null supaya BE tidak perlu rely pada client discipline.
        project_name: isProject ? (value.project_name ?? null) : null,
        department_name: value.department_name,
        status: value.status,
        details: value.details.map(d => ({
            item_uid: d.item_uid ?? null,
            unit_uid: d.unit_uid ?? null,
            qty: d.qty ?? 0,
            description: d.description ?? null,
        })),
    }
}

/** Build payload dari formField (untuk approve/reject yang tidak lewat form submit). */
const buildFieldPayload = (): ItemRequestForm => {
    const isProject = !!formField.is_project
    return {
        requirement: formField.requirement,
        request_date: formField.request_date,
        unit_code: formField.unit_code ?? null,
        wo_number: formField.wo_number ?? null,
        warehouse_uid: formField.warehouse?.uid ?? null,
        is_project: isProject,
        project_name: isProject ? (formField.project_name ?? null) : null,
        department_name: formField.department_name,
        status: formField.status,
        details: (formField.details ?? []).map(d => ({
            item_uid: d.item?.uid ?? null,
            unit_uid: d.unit?.uid ?? null,
            qty: (d as any).qty ?? 0,
            description: (d as any).description ?? null,
        })),
    }
}

const handleSubmit = (value: ItemRequestForm) => {
    const payload = buildFormPayload(value)
    if (formAction.value === 'create') {
        store.create(payload)
    } else if (formAction.value === 'update' && formField.uid) {
        store.update(formField.uid, payload)
    } else if (formField.uid) {
        store.revision(formField.uid, payload)
    }
    handleClose()
}

const handleApprove = () => {
    if (!formField.uid) return
    store.approve(formField.uid, buildFieldPayload())
    handleClose()
}

const handleReject = (reason: string) => {
    if (!formField.uid) return
    const trimmed = (reason || '').trim()
    if (!trimmed) return
    store.reject(formField.uid, {
        ...buildFieldPayload(),
        status: 'Rejected',
        reject_reason: trimmed,
    })
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
                v-if="ability.can('create', 'item_request')"
                :loading="store.loading"
                :disabled="store.loading"
                @click="handleOpenDialog(formField)"
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
                    {{ item.wo_number ?? '-' }}
                </template>

                <template v-slot:item.project_name="{ item }">
                    {{ item.project_name ?? '-' }}
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

    <item-request-form
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

    <item-request-detail
        v-model="detailDialog"
        :items="formField"
        @close="handleClose"
    />
</template>

<style scoped></style>