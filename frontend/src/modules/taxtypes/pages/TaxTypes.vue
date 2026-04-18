<script lang="ts" setup>
import { computed, reactive, ref } from 'vue'
// Import Types
import type { DataTableHeader } from 'vuetify'
// Import Components
import BaseSearchInput from '@/components/base/BaseSearchInput.vue'
import BaseRefreshButton from '@/components/base/BaseRefreshButton.vue'
import BaseCreateButton from '@/components/base/BaseCreateButton.vue'
import ActionsMenu from '@/components/common/ActionsMenu.vue'
import TaxTypesForm from '@/modules/taxtypes/components/TaxTypesForm.vue'
import ConfirmDeleteDialog from '@/components/common/ConfirmDeleteDialog.vue'
import DetailList from '@/components/common/DetailList.vue'
// Import Stores
import { useTaxTypesStore } from '@/stores/tax_types'
// Import Composables
import { useTranslate } from '@/composables/useTranslate'
// Import Utils
import { formatDate } from '@/utils/date'

const t = useTranslate()

const store = useTaxTypesStore()

const formTitle = computed(() => {
    return formAction.value === 'create' ? t('createDataDialogTitle') : t('updateDataDialogTitle')
})

const formDialog = ref<boolean>(false)
const formAction = ref<'create' | 'update'>('create')
const formFields = reactive<TaxTypesForm>({
    id: 0,
    uid: '',
    name: '',
    description: '',
    formula_type: 'percentage',
    formula: '',
    uses_dpp: false
})
const confirmDialog = ref<boolean>(false)
const detailDialog = ref<boolean>(false)
const detailData = ref<KeyValueItem[]>([])

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
    { title: 'No', key: 'no', align: 'center', width: '20', sortable: false },
    { title: t('typeName'), key: 'name', align: 'start' },
    { title: t('formulaType'), key: 'formula_type', align: 'start' },
    { title: t('formula'), key: 'formula', align: 'start' },
    { title: 'DPP', key: 'uses_dpp', align: 'center', sortable: false },
    { title: t('description'), key: 'description', align: 'start' },
    { title: t('actions'), key: 'action', align: 'center', width: '20', sortable: false }
])

const loadData = () => {
    store.fetch(options.value)
}

const handleActionMenu = (action: string, data: TaxTypesList) => {
    if (action === 'detail') {
        return handleOpenDetailDialog(data)
    }

    if (action === 'update') {
        return handleOpenDialog(data)
    }

    return handleOpenConfirmDialog(data.uid)
}

const handleOpenDialog = (item: TaxTypesList | null) => {
    if (item) {
        formAction.value = 'update'
        formFields.id = item.id
        formFields.uid = item.uid
        formFields.name = item.name
        formFields.description = item.description
        formFields.formula_type = item.formula_type ?? 'percentage'
        formFields.formula = item.formula ?? ''
        formFields.uses_dpp = !!item.uses_dpp
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

const handleOpenDetailDialog = (item: TaxTypesList) => {
    detailData.value = []
    resetForm()
    try {
        Object.assign(formFields, item)
        detailData.value = [
            { key: 'name', value: item.name },
            { key: 'description', value: item.description },
            { key: 'formulaType', value: item.formula_type },
            { key: 'formula', value: item.formula ?? '-' },
            { key: 'useDpp', value: item.uses_dpp ? t('yes') : t('no') },
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

const handleSubmit = () => {
    const data: TaxTypesForm = {
        name: formFields.name,
        description: formFields.description,
        formula_type: formFields.formula_type,
        formula: formFields.formula || null,
        uses_dpp: !!formFields.uses_dpp,
    }

    if (formAction.value === 'create') {
        store.create(data)
    } else if (formFields.uid) {
        store.update(formFields.uid, data)
    }

    handleClose()
}

const handleDelete = () => {
    if (formFields.uid) {
        store.delete(formFields.uid)
    }

    handleClose()
}

const resetForm = () => {
    formFields.id = 0
    formFields.uid = ''
    formFields.name = ''
    formFields.description = ''
    formFields.formula_type = 'percentage'
    formFields.formula = ''
    formFields.uses_dpp = false
}

const handleClose = () => {
    resetForm()
    formDialog.value = false
    confirmDialog.value = false
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
                <template v-slot:item.formula_type="{ item }">
                    <v-chip
                        size="x-small"
                        variant="tonal"
                        :color="item.formula_type === 'formula' ? 'primary' : item.formula_type === 'percentage' ? 'success' : 'grey'"
                        class="text-uppercase font-weight-bold"
                    >
                        {{ item.formula_type }}
                    </v-chip>
                </template>

                <template v-slot:item.formula="{ item }">
                    <span v-if="item.formula" class="font-mono text-caption">{{ item.formula }}</span>
                    <span v-else class="text-medium-emphasis">-</span>
                </template>

                <template v-slot:item.uses_dpp="{ item }">
                    <v-chip
                        size="x-small"
                        variant="tonal"
                        :color="item.uses_dpp ? 'success' : 'grey'"
                        class="font-weight-bold"
                    >
                        {{ item.uses_dpp ? t('yes') : t('no') }}
                    </v-chip>
                </template>

                <template v-slot:item.action="{ item }">
                    <actions-menu
                        :menus="['detail', 'update', 'delete']"
                        :data="item"
                        @click="handleActionMenu"
                    />
                </template>
            </v-data-table-server>
        </v-card-text>
    </v-card>

    <tax-types-form
        v-model="formDialog"
        :title="formTitle"
        :data="formFields"
        :saving="store.loading"
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

<style scoped>
.font-mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
}
</style>