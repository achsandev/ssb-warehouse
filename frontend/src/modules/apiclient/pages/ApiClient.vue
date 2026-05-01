<script lang="ts" setup>
import { computed, reactive, ref } from 'vue'
import type { DataTableHeader } from 'vuetify'
// Components
import BaseSearchInput from '@/components/base/BaseSearchInput.vue'
import BaseRefreshButton from '@/components/base/BaseRefreshButton.vue'
import BaseCreateButton from '@/components/base/BaseCreateButton.vue'
import ActionsMenu from '@/components/common/ActionsMenu.vue'
import ConfirmDeleteDialog from '@/components/common/ConfirmDeleteDialog.vue'
import DetailList from '@/components/common/DetailList.vue'
import ApiClientForm from '@/modules/apiclient/components/ApiClientForm.vue'
import ApiClientTokenDialog from '@/modules/apiclient/components/ApiClientTokenDialog.vue'
// Stores
import { useApiClientStore } from '@/stores/api_client'
// Composables
import { useTranslate } from '@/composables/useTranslate'
import { useAbility } from '@/composables/useAbility'
// Utils
import { formatDate } from '@/utils/date'

const t = useTranslate()
const store = useApiClientStore()
const { can } = useAbility()

const formTitle = computed(() =>
    formAction.value === 'create' ? t('createDataDialogTitle') : t('updateDataDialogTitle'),
)

const formDialog = ref<boolean>(false)
const formAction = ref<'create' | 'update'>('create')
const formFields = reactive<ApiClientForm>({
    uid: '',
    name: '',
    url: '',
    description: '',
    is_active: true,
    enforce_origin: true,
})

const confirmDialog = ref<boolean>(false)
const detailDialog = ref<boolean>(false)
const detailData = ref<KeyValueItem[]>([])
const tokenDialog = ref<boolean>(false)
const tokenClientName = ref<string>('')
const tokenClientUid = ref<string>('')

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
    itemsPerPage: 10,
    sortBy: [{ key: 'created_at', order: 'desc' }],
    search: '',
})

const headers = computed<DataTableHeader[]>(() => [
    { title: t('actions'), key: 'action', align: 'center', width: '20', sortable: false },
    { title: t('apiClientName'), key: 'name', align: 'start' },
    { title: t('apiClientUrl'), key: 'url', align: 'start' },
    { title: t('apiClientEnforceOrigin'), key: 'enforce_origin', align: 'center', width: '120' },
    { title: t('status'), key: 'is_active', align: 'center', width: '100' },
    { title: t('apiClientTokenStatus'), key: 'token', align: 'center', width: '160', sortable: false },
])

const availableMenus = computed<string[]>(() => {
    const menus: string[] = ['detail']
    if (can('update', 'api_client')) menus.push('update')
    if (can('manage_token', 'api_client')) menus.push('manage_token')
    if (can('delete', 'api_client')) menus.push('delete')
    return menus
})

const loadData = () => store.fetch(options.value)

const handleActionMenu = (action: string, data: ApiClientList) => {
    if (action === 'detail') return handleOpenDetailDialog(data)
    if (action === 'update') return handleOpenDialog(data)
    if (action === 'manage_token') return handleOpenTokenDialog(data)
    return handleOpenConfirmDialog(data.uid)
}

const handleOpenDialog = (item: ApiClientList | null) => {
    if (item) {
        formAction.value = 'update'
        formFields.uid = item.uid
        formFields.name = item.name
        formFields.url = item.url
        formFields.description = item.description ?? ''
        formFields.is_active = !!item.is_active
        formFields.enforce_origin = !!item.enforce_origin
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

const handleOpenDetailDialog = (item: ApiClientList) => {
    detailData.value = [
        { key: 'apiClientName', value: item.name },
        { key: 'apiClientUrl', value: item.url },
        { key: 'description', value: item.description ?? '-' },
        { key: 'status', value: item.is_active ? t('active') : t('inactive') },
        { key: 'apiClientEnforceOrigin', value: item.enforce_origin ? t('yes') : t('no') },
        { key: 'apiClientTokenName', value: item.token?.name ?? '-' },
        { key: 'apiClientTokenLastUsed', value: item.token?.last_used_at ? formatDate(item.token.last_used_at) : t('apiClientTokenNeverUsed') },
        { key: 'apiClientExpiresAt', value: item.token?.expires_at ? formatDate(item.token.expires_at) : t('apiClientNeverExpires') },
        { key: 'createdAt', value: formatDate(item.created_at) },
        { key: 'createdBy', value: item.created_by_name ?? '-' },
        { key: 'updatedAt', value: formatDate(item.updated_at ?? undefined) },
        { key: 'updatedBy', value: item.updated_by_name ?? '-' },
    ]
    detailDialog.value = true
}

const handleOpenTokenDialog = (item: ApiClientList) => {
    tokenClientName.value = item.name
    tokenClientUid.value = item.uid
    store.clearGeneratedToken()
    tokenDialog.value = true
}

const handleSubmit = () => {
    const payload: ApiClientForm = {
        name: (formFields.name ?? '').trim(),
        url: (formFields.url ?? '').trim(),
        description: (formFields.description ?? '').toString().trim() || null,
        is_active: !!formFields.is_active,
        enforce_origin: !!formFields.enforce_origin,
    }

    if (formAction.value === 'create') {
        store.create(payload)
    } else if (formFields.uid) {
        store.update(formFields.uid, payload)
    }

    handleClose()
}

const handleGenerateToken = (payload: ApiClientGenerateTokenForm) => {
    if (!tokenClientUid.value) return
    store.generateToken(tokenClientUid.value, payload)
}

const handleDeleteToken = () => {
    if (!tokenClientUid.value) return
    store.deleteToken(tokenClientUid.value)
    tokenDialog.value = false
}

const handleDelete = () => {
    if (formFields.uid) store.delete(formFields.uid)
    handleClose()
}

const resetForm = () => {
    formFields.uid = ''
    formFields.name = ''
    formFields.url = ''
    formFields.description = ''
    formFields.is_active = true
    formFields.enforce_origin = true
}

const handleClose = () => {
    resetForm()
    formDialog.value = false
    confirmDialog.value = false
}

const handleCloseTokenDialog = () => {
    store.clearGeneratedToken()
    tokenDialog.value = false
    tokenClientName.value = ''
    tokenClientUid.value = ''
}
</script>

<template>
    <v-card>
        <v-card-title class="d-flex flex-column flex-sm-row-reverse justify-space-between align-sm-center">
            <base-create-button
                v-if="can('create', 'api_client')"
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
                <template v-slot:item.url="{ item }">
                    <a
                        :href="item.url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-caption font-mono text-decoration-none"
                    >
                        {{ item.url }}
                    </a>
                </template>

                <template v-slot:item.enforce_origin="{ item }">
                    <v-chip
                        size="x-small"
                        variant="tonal"
                        :color="item.enforce_origin ? 'success' : 'grey'"
                        class="font-weight-bold"
                    >
                        {{ item.enforce_origin ? t('yes') : t('no') }}
                    </v-chip>
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

                <template v-slot:item.token="{ item }">
                    <v-chip
                        v-if="!item.token"
                        size="x-small"
                        variant="tonal"
                        color="warning"
                        class="font-weight-bold"
                    >
                        {{ t('apiClientNoToken') }}
                    </v-chip>
                    <v-chip
                        v-else-if="item.token.is_expired"
                        size="x-small"
                        variant="tonal"
                        color="error"
                        class="font-weight-bold"
                    >
                        {{ t('apiClientTokenExpired') }}
                    </v-chip>
                    <v-chip
                        v-else
                        size="x-small"
                        variant="tonal"
                        color="success"
                        class="font-weight-bold"
                    >
                        {{ t('apiClientTokenActive') }}
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

    <api-client-form
        v-model="formDialog"
        :title="formTitle"
        :data="formFields"
        :saving="store.loading"
        @submit="handleSubmit"
        @close="handleClose"
    />

    <api-client-token-dialog
        v-model="tokenDialog"
        :client-name="tokenClientName"
        :generated="store.generatedToken"
        :saving="store.loading"
        @submit="handleGenerateToken"
        @close="handleCloseTokenDialog"
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
