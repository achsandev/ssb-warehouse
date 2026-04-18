<script lang="ts" setup>
import { computed, reactive, ref } from 'vue'
// Import Types
import type { DataTableHeader } from 'vuetify'
// Import Components
import BaseSearchInput from '@/components/base/BaseSearchInput.vue'
import BaseRefreshButton from '@/components/base/BaseRefreshButton.vue'
import BaseCreateButton from '@/components/base/BaseCreateButton.vue'
import ActionsMenu from '@/components/common/ActionsMenu.vue'
import DetailList from '@/components/common/DetailList.vue'
import SupplierForm from '@/modules/supplier/components/SupplierForm.vue'
import ConfirmDeleteDialog from '@/components/common/ConfirmDeleteDialog.vue'
// Import Stores
import { useSupplierStore } from '@/stores/supplier'
// Import Composables
import { useTranslate } from '@/composables/useTranslate'
// Import Utils
import { formatDate } from '@/utils/date'
import { formatNpwp } from '@/utils/npwp'

const t = useTranslate()
const store = useSupplierStore()

// =========================
// State
// =========================
const formDialog = ref<boolean>(false)
const confirmDialog = ref<boolean>(false)
const detailDialog = ref<boolean>(false)
const detailData = ref<KeyValueItem[]>([])
const formAction = ref<'create' | 'update'>('create')

const formFields = reactive<SupplierList>({
    uid: '',
    name: '',
    address: '',
    phone_number: '',
    npwp_number: '',
    pic_name: '',
    email: '',
    payment_method: null,
    payment_duration: null,
    tax_types: null,
    additional_info: '',
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
    { title: t('supplierName'), key: 'name', align: 'start', nowrap: true },
    { title: t('address'), key: 'address', align: 'start', nowrap: true },
    { title: t('phoneNumber'), key: 'phone_number', align: 'start', nowrap: true },
    { title: t('npwpNumber'), key: 'npwp_number', align: 'start', nowrap: true },
    { title: t('picName'), key: 'pic_name', align: 'start', nowrap: true },
    { title: t('email'), key: 'email', align: 'start', nowrap: true },
    { title: t('paymentMethods'), key: 'payment_method', align: 'start', nowrap: true },
    { title: t('paymentDuration'), key: 'payment_duration', align: 'start', nowrap: true },
    { title: t('taxTypes'), key: 'tax_types', align: 'start', nowrap: true },
    { title: t('additionalInfo'), key: 'additional_info', align: 'start', nowrap: true }
])

// =========================
// Methods
// =========================
const loadData = () => store.fetch(options.value)

const resetForm = () => {
    Object.assign(formFields, {
        uid: '',
        name: '',
        address: '',
        phone_number: '',
        npwp_number: '',
        pic_name: '',
        email: '',
        payment_method: null,
        payment_duration: null,
        tax_types: null,
        additional_info: '',
        created_at: '',
        updated_at: '',
        created_by_name: '',
        updated_by_name: ''
    })
}

const handleClose = () => {
    resetForm()
    formDialog.value = false
    confirmDialog.value = false
}

// =========================
// Functions
// =========================
const handleActionMenu = (action: string, data: SupplierList) => {
    if (action === 'detail') {
        return handleOpenDetailDialog(data)
    }

    if (action === 'update') {
        return handleOpenDialog(data)
    }
    return handleOpenConfirmDialog(data.uid)
}

const handleOpenDialog = (item: SupplierList | null) => {
    if (item) {
        formAction.value = 'update'
        Object.assign(formFields, item)
    } else {
        formAction.value = 'create'
        resetForm()
    }
    formDialog.value = true
}

const handleOpenConfirmDialog = (uid: string) => {
    formFields.uid = uid
    confirmDialog.value = true
}

const handleOpenDetailDialog = (item: SupplierList) => {
    detailData.value = []
    resetForm()
    try {
        Object.assign(formFields, item)
        detailData.value = [
            { key: 'name', value: item.name },
            { key: 'address', value: item.address },
            { key: 'phoneNumber', value: item.phone_number },
            { key: 'npwpNumber', value: item.npwp_number ? formatNpwp(item.npwp_number) : '-' },
            { key: 'picName', value: item.pic_name },
            { key: 'paymentMethods', value: item.payment_method?.name },
            { key: 'paymentDuration', value: item.payment_duration?.name },
            { key: 'taxTypes', value: item.tax_types?.map(t => t.name).join(', ') },
            { key: 'additionalInfo', value: item.additional_info },
            { key: 'createdAt', value: formatDate(item.created_at) },
            { key: 'createdBy', value: item.created_by_name },
            { key: 'updatedAt', value: formatDate(item.updated_at) },
            { key: 'updatedBy', value: item.updated_by_name }
        ]
        detailDialog.value = true
    } catch (err) {
        console.error("Error: ", err)
    }
}

const handleSubmit = (values: SupplierForm) => {
    if (formAction.value === 'create') {
        store.create(values)
    } else {
        if (formFields.uid) {
            store.update(formFields.uid, values)
        }
    }
    handleClose()
}

const handleDelete = () => {
    if (formFields.uid) {
        store.delete(formFields.uid)
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
                        :menus="['detail', 'update', 'delete']"
                        :data="item"
                        @click="handleActionMenu"
                    />
                </template>

                <template v-slot:item.payment_method="{ item }">
                    {{ item.payment_method?.name }}
                </template>

                <template v-slot:item.payment_duration="{ item }">
                    {{ item.payment_duration?.name }}
                </template>

                <template v-slot:item.tax_types="{ item }">
                    {{ Array.isArray(item.tax_types) ? item.tax_types.map(t => t.name).join(', ') : '' }}
                </template>
            </v-data-table-server>
        </v-card-text>
    </v-card>

    <supplier-form
        v-if="formDialog"
        v-model="formDialog"
        :title="formTitle"
        :data="formFields"
        @submit="handleSubmit"
        @close="handleClose"
    />

    <confirm-delete-dialog
        v-model="confirmDialog"
        @delete="handleDelete"
        @close="handleClose"
    />

    <detail-list v-model="detailDialog" :items="detailData" />
</template>

<style scoped></style>