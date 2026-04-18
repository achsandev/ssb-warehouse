<script lang="ts" setup>
import { computed, reactive, ref } from 'vue'
// Types
import type { DataTableHeader } from 'vuetify'
// Components
import BaseSearchInput from '@/components/base/BaseSearchInput.vue'
import BaseRefreshButton from '@/components/base/BaseRefreshButton.vue'
import BaseCreateButton from '@/components/base/BaseCreateButton.vue'
import ActionsMenu from '@/components/common/ActionsMenu.vue'
import ConfirmDeleteDialog from '@/components/common/ConfirmDeleteDialog.vue'
import DetailList from '@/components/common/DetailList.vue'
import SettingDppFormulaForm from '@/modules/settingdppformula/components/SettingDppFormulaForm.vue'
// Stores
import { useSettingDppFormulaStore } from '@/stores/setting_dpp_formula'
// Composables
import { useTranslate } from '@/composables/useTranslate'
import { useAbility } from '@/composables/useAbility'
// Utils
import { formatDate } from '@/utils/date'

const t = useTranslate()
const store = useSettingDppFormulaStore()
const { can } = useAbility()

const formTitle = computed(() =>
    formAction.value === 'create' ? t('createDataDialogTitle') : t('updateDataDialogTitle'),
)

const formDialog = ref<boolean>(false)
const formAction = ref<'create' | 'update'>('create')
const formFields = reactive<SettingDppFormulaForm>({
    uid: '',
    name: '',
    formula: '',
    description: '',
    is_active: true,
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
    { title: 'All', value: -1 },
]

const options = ref<TableParams>({
    page: 1,
    itemsPerPage: 5,
    sortBy: [{ key: 'created_at', order: 'desc' }],
    search: '',
})

const headers = computed<DataTableHeader[]>(() => [
    { title: t('actions'), key: 'action', align: 'center', width: '20', sortable: false },
    { title: t('formulaName'), key: 'name', align: 'start' },
    { title: t('formula'), key: 'formula', align: 'start' },
    { title: t('description'), key: 'description', align: 'start' },
    { title: t('status'), key: 'is_active', align: 'center', width: '120' },
])

const availableMenus = computed<('detail' | 'update' | 'delete')[]>(() => {
    const menus: ('detail' | 'update' | 'delete')[] = ['detail']
    if (can('update', 'setting_dpp_formula')) menus.push('update')
    if (can('delete', 'setting_dpp_formula')) menus.push('delete')

    return menus
})

const loadData = () => {
    store.fetch(options.value)
}

const handleActionMenu = (action: string, data: SettingDppFormulaList) => {
    if (action === 'detail') return handleOpenDetailDialog(data)
    if (action === 'update') return handleOpenDialog(data)

    return handleOpenConfirmDialog(data.uid)
}

const handleOpenDialog = (item: SettingDppFormulaList | null) => {
    if (item) {
        formAction.value = 'update'
        formFields.uid = item.uid
        formFields.name = item.name
        formFields.formula = item.formula ?? ''
        formFields.description = item.description ?? ''
        formFields.is_active = !!item.is_active
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

const handleOpenDetailDialog = (item: SettingDppFormulaList) => {
    detailData.value = [
        { key: 'formulaName', value: item.name },
        { key: 'formula', value: item.formula ?? '-' },
        { key: 'description', value: item.description ?? '-' },
        { key: 'status', value: item.is_active ? t('active') : t('inactive') },
        { key: 'createdAt', value: formatDate(item.created_at) },
        { key: 'createdBy', value: item.created_by_name },
        { key: 'updatedAt', value: formatDate(item.updated_at) },
        { key: 'updatedBy', value: item.updated_by_name },
    ]
    detailDialog.value = true
}

const handleSubmit = () => {
    const payload: SettingDppFormulaForm = {
        name: (formFields.name ?? '').trim(),
        formula: (formFields.formula ?? '').trim(),
        description: (formFields.description ?? '').toString().trim() || null,
        is_active: !!formFields.is_active,
    }

    if (formAction.value === 'create') {
        store.create(payload)
    } else if (formFields.uid) {
        store.update(formFields.uid, payload)
    }

    handleClose()
}

const handleDelete = () => {
    if (formFields.uid) store.delete(formFields.uid)
    handleClose()
}

const resetForm = () => {
    formFields.uid = ''
    formFields.name = ''
    formFields.formula = ''
    formFields.description = ''
    formFields.is_active = true
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
                v-if="can('create', 'setting_dpp_formula')"
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
                <template v-slot:item.formula="{ item }">
                    <span v-if="item.formula" class="font-mono text-caption">{{ item.formula }}</span>
                    <span v-else class="text-medium-emphasis">-</span>
                </template>

                <template v-slot:item.description="{ item }">
                    <span v-if="item.description" class="text-body-2">{{ item.description }}</span>
                    <span v-else class="text-medium-emphasis">-</span>
                </template>

                <template v-slot:item.is_active="{ item }">
                    <v-chip
                        size="x-small"
                        variant="tonal"
                        :color="item.is_active ? 'success' : 'grey'"
                        class="font-weight-bold"
                    >
                        {{ item.is_active ? t('active') : t('inactive') }}
                    </v-chip>
                </template>

                <template v-slot:item.action="{ item }">
                    <actions-menu
                        :menus="availableMenus"
                        :data="item"
                        @click="handleActionMenu"
                    />
                </template>
            </v-data-table-server>
        </v-card-text>
    </v-card>

    <setting-dpp-formula-form
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
