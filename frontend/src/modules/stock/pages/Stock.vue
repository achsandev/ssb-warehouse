<script lang="ts" setup>
import { computed, reactive, ref } from 'vue'
// Import Types
import type { DataTableHeader } from 'vuetify'
// Import Components
import BaseSearchInput from '@/components/base/BaseSearchInput.vue'
import BaseRefreshButton from '@/components/base/BaseRefreshButton.vue'
import BaseCreateButton from '@/components/base/BaseCreateButton.vue'
import ActionsMenu from '@/components/common/ActionsMenu.vue'
import ConversionForm from '../components/ConversionForm.vue'
import StockForm from '../components/StockForm.vue'
import ConfirmDeleteDialog from '@/components/common/ConfirmDeleteDialog.vue'
// Import Icons
import SystemUiconsClose from '~icons/system-uicons/close'
// Import Stores
import { useStockStore } from '@/stores/stock'
// Import Composables
import { useTranslate } from '@/composables/useTranslate'
// Import Utils
import { formatDate } from '@/utils/date'

const t = useTranslate()
const store = useStockStore()

// =========================
// State
// =========================
const formDialog = ref<boolean>(false)
const confirmDialog = ref<boolean>(false)
const detailDialog = ref<boolean>(false)
const conversionDialog = ref<boolean>(false)
const conversionData = ref<ConversionForm | null>(null)
const formAction = ref<'create' | 'update'>('create')

const formField = reactive<StockList>({
    uid: '',
    barcode: null,
    item: null,
    unit: null,
    rack: null,
    warehouse: null,
    tank: null,
    stock_units: null,
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
    { title: t('item'), key: 'item', align: 'start', nowrap: true },
    { title: t('warehouse'), key: 'warehouse', align: 'start', nowrap: true },
    { title: t('rack'), key: 'rack', align: 'start', nowrap: true },
    { title: t('tank'), key: 'tank', align: 'start', nowrap: true },
    { title: t('stock'), key: 'stock', align: 'start', nowrap: true }
])

// =========================
// Methods
// =========================
const loadData = () => store.fetch(options.value)

const resetForm = () => {
    Object.assign(formField, {
        uid: '',
        barcode: null,
        item: null,
        unit: null,
        warehouse: null,
        rack: null,
        tank: null,
        stock_units: null,
        created_at: '',
        updated_at: '',
        created_by_name: '',
        updated_by_name: ''
    })
}

const handleClose = () => {
    resetForm()
    formDialog.value = false
    detailDialog.value = false
    confirmDialog.value = false
    conversionDialog.value = false
}

// =========================
// Functions
// =========================
const handleActionMenu = (action: string, data: StockList) => {
    if (action === 'detail') {
        return handleOpenDetailDialog(data)
    }
    if (action === 'update') {
        return handleOpenDialog(data)
    }
    if (action === 'conversion') {
        return handleOpenConversionDialog(data)
    }
    return handleOpenConfirmDialog(data.uid)
}

const handleOpenDialog = (item: StockList | null) => {
    if (item) {
        formAction.value = 'update'
        Object.assign(formField, item)
    } else {
        formAction.value = 'create'
        resetForm()
    }
    formDialog.value = true
}

const handleOpenConfirmDialog = (uid: string) => {
    formField.uid = uid
    confirmDialog.value = true
}

const handleOpenDetailDialog = (item: StockList) => {
    resetForm()
    Object.assign(formField, item)
    detailDialog.value = true
}

const handleOpenConversionDialog = (item: StockList) => {
    conversionData.value = null
    try {
        conversionData.value = {
            item_name: item.item?.name,
            current_qty: item.stock_units ?? [],
            stock_uid: item.uid,
            base_unit_uid: null,
            derived_unit_uid: null,
            convert_qty: 0,
            converted_qty: 0,
        }

        conversionDialog.value = true
    } catch (err) {
        console.error("Error: ", err)
    }
}

const handleSubmit = async (value: StockForm) => {
    if (formAction.value === 'create') {
        await store.create(value)
    } else {
        if (formField.uid) {
            await store.update(formField.uid, value)
        }
    }
    if (!store.error) handleClose()
}

const handleConversion = (value: ConversionForm) => {
    store.conversion(value)
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
                        :menus="['detail', 'update', 'conversion', 'delete']"
                        :data="item"
                        @click="handleActionMenu"
                    />
                </template>

                <template v-slot:item.item="{ item }">
                    {{ item.item?.name }}
                </template>

                <template v-slot:item.warehouse="{ item }">
                    {{ item.warehouse?.name }}
                </template>

                <template v-slot:item.rack="{ item }">
                    {{ item.rack?.name }}
                </template>

                <template v-slot:item.tank="{ item }">
                    {{ item.tank?.name }}
                </template>

                <template v-slot:item.stock="{ item }">
                    {{
                        item.stock_units?.map((stock_unit) => {
                            return (stock_unit.qty + ' ' + stock_unit.unit_symbol)
                        }).join(', ')
                    }}
                </template>
            </v-data-table-server>
        </v-card-text>
    </v-card>

    <stock-form
        v-if="formDialog"
        v-model="formDialog"
        :title="formTitle"
        :data="formField"
        :saving="store.loading"
        @submit="handleSubmit"
        @close="handleClose"
    />

    <confirm-delete-dialog
        v-model="confirmDialog"
        @delete="handleDelete"
        @close="handleClose"
    />

    <!-- Stock Detail Drawer -->
    <v-navigation-drawer
        v-model="detailDialog"
        location="right"
        width="420"
        temporary
    >
        <v-list-item
            class="pa-4"
            :title="t('information', { field: t('stock') })"
        >
            <template #append>
                <v-btn
                    :icon="SystemUiconsClose"
                    variant="text"
                    size="small"
                    @click="detailDialog = false"
                />
            </template>
        </v-list-item>

        <v-divider />

        <div class="pa-4">
            <v-row dense class="mb-2">
                <v-col cols="5" class="text-caption text-medium-emphasis">{{ t('item') }}</v-col>
                <v-col cols="7" class="text-body-2 font-weight-medium">{{ formField.item?.name ?? '-' }}</v-col>

                <v-col cols="5" class="text-caption text-medium-emphasis">{{ t('warehouse') }}</v-col>
                <v-col cols="7" class="text-body-2">{{ formField.warehouse?.name ?? '-' }}</v-col>

                <v-col cols="5" class="text-caption text-medium-emphasis">{{ t('rack') }}</v-col>
                <v-col cols="7" class="text-body-2">{{ formField.rack?.name ?? '-' }}</v-col>

                <v-col cols="5" class="text-caption text-medium-emphasis">{{ t('tank') }}</v-col>
                <v-col cols="7" class="text-body-2">{{ formField.tank?.name ?? '-' }}</v-col>

                <v-col cols="5" class="text-caption text-medium-emphasis">{{ t('stock') }}</v-col>
                <v-col cols="7" class="text-body-2">
                    {{ formField.stock_units?.map(su => `${su.qty} ${su.unit_symbol}`).join(', ') ?? '-' }}
                </v-col>

                <v-col cols="5" class="text-caption text-medium-emphasis">{{ t('createdAt') }}</v-col>
                <v-col cols="7" class="text-body-2">{{ formatDate(formField.created_at) }}</v-col>

                <v-col cols="5" class="text-caption text-medium-emphasis">{{ t('createdBy') }}</v-col>
                <v-col cols="7" class="text-body-2">{{ formField.created_by_name }}</v-col>

                <v-col cols="5" class="text-caption text-medium-emphasis">{{ t('updatedAt') }}</v-col>
                <v-col cols="7" class="text-body-2">{{ formField.updated_at ? formatDate(formField.updated_at) : '-' }}</v-col>

                <v-col cols="5" class="text-caption text-medium-emphasis">{{ t('updatedBy') }}</v-col>
                <v-col cols="7" class="text-body-2">{{ formField.updated_by_name ?? '-' }}</v-col>
            </v-row>

            <!-- Barcode -->
            <template v-if="formField.barcode">
                <v-divider class="my-3" />
                <p class="text-caption text-medium-emphasis mb-2">{{ t('barcode') }}</p>
                <div class="d-flex justify-center">
                    <v-img
                        :src="formField.barcode"
                        max-width="200"
                        contain
                    />
                </div>
            </template>
        </div>
    </v-navigation-drawer>

    <conversion-form
        v-if="conversionDialog"
        v-model="conversionDialog"
        :data="conversionData"
        @submit="handleConversion"
        @close="handleClose"
    />
</template>

<style scoped></style>