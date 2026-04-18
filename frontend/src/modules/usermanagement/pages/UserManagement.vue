<script lang="ts" setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useUserStore } from '@/stores/user'
import { storeToRefs } from 'pinia'
import type { User } from '@/types/user'
import BaseSearchInput from '@/components/base/BaseSearchInput.vue'
import BaseRefreshButton from '@/components/base/BaseRefreshButton.vue'
import BaseCreateButton from '@/components/base/BaseCreateButton.vue'
import { useTranslate } from '@/composables/useTranslate'
import ActionsMenu from '@/components/common/ActionsMenu.vue'
import ConfirmDeleteDialog from '@/components/common/ConfirmDeleteDialog.vue'
import { useAbility } from '@/composables/useAbility'
import { useMessageStore } from '@/stores/message'
import IconamoonCloseFill from '~icons/iconamoon/close-fill'
import IconamoonCheck from '~icons/iconamoon/check'

const t = useTranslate()
const ability = useAbility()
const userStore = useUserStore()
const message = useMessageStore()
const { users, employees, employeeTotal, loading, selectedUser, roles } = storeToRefs(userStore)

const dialog = ref(false)
const detailDialog = ref(false)
const confirmDeleteDialog = ref(false)
const isEdit = ref(false)
const employeeDialog = ref(false)
const search = ref('')
const employeeSearch = ref('')
const employeePage = ref(1)
const employeePerPage = ref(10)
const form = ref<Partial<User> & { role?: string | number }>({})
const pendingDeleteUser = ref<User | null>(null)

onMounted(() => {
    userStore.fetchUsers()
    userStore.fetchRoles()
})

watch(employeeDialog, (open) => {
    if (open) {
        employeePage.value = 1
        employeeSearch.value = ''
        loadEmployees()
    }
})

const loadEmployees = () => {
    userStore.fetchEmployees({
        page: employeePage.value,
        per_page: employeePerPage.value
    })
}

watch([employeePage, employeePerPage], loadEmployees)

const filteredUsers = computed(() => {
    if (!search.value) return users.value
    return users.value.filter(user =>
        (user.name || '').toLowerCase().includes(search.value.toLowerCase()) ||
        (user.email || '').toLowerCase().includes(search.value.toLowerCase()) ||
        (user.user_details?.nik || '').toLowerCase().includes(search.value.toLowerCase())
    )
})

const userDetail = computed(() => {
    if (!selectedUser.value) return null
    const d = selectedUser.value.user_details
    return Array.isArray(d) ? d[0] : d
})

const getDefaultUserDetail = () => ({
    id_karyawan: 0,
    nik: '',
    name: '',
    department: '',
    sub_department: '',
    position: '',
    direct_supervisor_id: 0,
    direct_supervisor_position: ''
})

const handleActionMenu = (action: string, data: User) => {
    if (action === 'detail') return openDetail(data)
    if (action === 'update') return openEdit(data)
    return openConfirmDelete(data)
}

const formTitle = computed(() =>
    isEdit.value ? t('updateDataDialogTitle') : t('createDataDialogTitle')
)

const selectEmployee = (emp: any) => {
    form.value.name = emp.name
    form.value.user_details = {
        id_karyawan: emp.id,
        nik: emp.nik,
        name: emp.name,
        department: emp.departemen,
        sub_department: emp.subdepartemen,
        position: emp.Jabatan,
        direct_supervisor_id: emp.direct_supervisor?.id || 0,
        direct_supervisor_position: emp.direct_supervisor?.jabatan || ''
    }
    employeeDialog.value = false
}

const openCreate = () => {
    isEdit.value = false
    form.value = { user_details: getDefaultUserDetail() }
    dialog.value = true
}

const openEdit = (user: User) => {
    isEdit.value = true
    const userRoles = (user as any).roles
    const currentRoleId = Array.isArray(userRoles) && userRoles.length > 0 ? userRoles[0].id : undefined
    form.value = {
        ...user,
        role: currentRoleId,
        password: undefined,
        user_details: user.user_details
            ? { ...(Array.isArray(user.user_details) ? user.user_details[0] : user.user_details) }
            : getDefaultUserDetail()
    }
    dialog.value = true
}

const openDetail = (user: User) => {
    userStore.selectUser(user)
    detailDialog.value = true
}

const openConfirmDelete = (user: User) => {
    pendingDeleteUser.value = user
    confirmDeleteDialog.value = true
}

const getActionMenus = () => {
    const menus: string[] = []
    if (ability.can('manage', 'all')) {
        menus.push('detail', 'update', 'delete')
        return menus
    }
    if (ability.can('read', 'user_management')) menus.push('detail')
    if (ability.can('update', 'user_management')) menus.push('update')
    if (ability.can('delete', 'user_management')) menus.push('delete')
    return menus
}

const closeDialog = () => {
    dialog.value = false
    form.value = { user_details: getDefaultUserDetail() }
}

const closeDetailDialog = () => {
    detailDialog.value = false
    userStore.clearSelectedUser()
}

const saveUser = async () => {
    const user_detail = (form.value.user_details || {}) as Record<string, any>
    if (!user_detail.name) user_detail.name = form.value.name || ''
    const payload = {
        ...form.value,
        role_id: form.value.role ? Number(form.value.role) : undefined,
        user_detail,
    } as any
    delete payload.user_details
    delete payload.role
    if (!payload.password) delete payload.password

    if (isEdit.value && form.value.uid) {
        if (!payload.name) payload.name = ''
        if (!payload.email) payload.email = ''
        await userStore.updateUser(payload as User)
    } else {
        await userStore.createUser(payload)
    }

    if (userStore.error) {
        message.setMessage({ text: userStore.error, color: 'error', timeout: 4000 })
    } else {
        message.setMessage({ text: t('savedSuccessfully'), color: 'success', timeout: 3000 })
        dialog.value = false
        userStore.fetchUsers()
    }
}

const handleDelete = async () => {
    const user = pendingDeleteUser.value
    if (user?.uid) {
        await userStore.deleteUser(user.uid)
        if (userStore.error) {
            message.setMessage({ text: userStore.error, color: 'error', timeout: 4000 })
        } else {
            message.setMessage({ text: t('deletedSuccessfully'), color: 'success', timeout: 3000 })
            userStore.fetchUsers()
        }
    }
    confirmDeleteDialog.value = false
    pendingDeleteUser.value = null
}
</script>


<template>
    <v-card>
        <v-card-title class="d-flex flex-column flex-sm-row-reverse justify-space-between align-sm-center">
            <base-create-button
                :loading="loading"
                :disabled="loading"
                @click="openCreate"
            />
            <div class="d-flex align-center">
                <base-search-input
                    :loading="loading"
                    v-model="search"
                    @update:model-value="userStore.fetchUsers()"
                />
                <base-refresh-button
                    :loading="loading"
                    :disabled="loading"
                    @click="userStore.fetchUsers()"
                />
            </div>
        </v-card-title>
        <v-card-text>
            <v-data-table
                :items="filteredUsers"
                :loading="loading"
                :headers="[
                    { title: t('actions'), value: 'actions', width: '20', sortable: false },
                    { title: t('name'), value: 'name' },
                    { title: 'Email', value: 'email' },
                    { title: 'NIK', value: 'user_details[0].nik' },
                    { title: 'Department', value: 'user_details[0].department' },
                    { title: 'Position', value: 'user_details[0].position' },
                    { title: t('role'), value: 'roles[0].name' }
                ]"
                item-value="uid"
            >
                <template #item.actions="{ item }">
                    <actions-menu
                        :menus="getActionMenus()"
                        :data="item"
                        @click="handleActionMenu"
                    />
                </template>
            </v-data-table>
        </v-card-text>
    </v-card>

    <!-- ── Form Dialog ─────────────────────────────────────────────── -->
    <v-dialog v-model="dialog" max-width="900" scrollable :persistent="loading">
        <v-card :loading="loading" rounded="lg">

            <!-- Title bar -->
            <v-card-title class="d-flex align-center gap-2 px-6 py-4">
                <v-icon icon="mdi-account-cog-outline" color="primary" class="me-1" />
                <span class="text-h6 font-weight-semibold">{{ formTitle }}</span>
                <v-spacer />
                <v-btn
                    icon="mdi-close"
                    variant="text"
                    size="small"
                    :disabled="loading"
                    @click="closeDialog"
                />
            </v-card-title>

            <v-divider />

            <v-card-text class="px-6 py-5">

                <!-- Section: General Info -->
                <div class="d-flex align-center justify-space-between mb-3">
                    <div class="section-header">
                        <v-icon icon="mdi-information-outline" size="18" color="primary" />
                        <span class="text-subtitle-2 font-weight-bold text-primary">{{ t('generalInfo') }}</span>
                    </div>
                    <v-btn
                        size="small"
                        color="primary"
                        variant="tonal"
                        prepend-icon="mdi-account-search-outline"
                        @click="employeeDialog = true"
                    >
                        {{ t('selectEmployee') }}
                    </v-btn>
                </div>

                <v-row dense>
                    <v-col cols="12" md="6">
                        <v-text-field
                            v-model="form.name"
                            :label="t('name')"
                            variant="outlined"
                            density="comfortable"
                            hide-details="auto"
                            required
                        />
                    </v-col>
                    <v-col cols="12" md="6">
                        <v-text-field
                            v-model="(form.user_details ?? {}).nik"
                            label="NIK"
                            variant="outlined"
                            density="comfortable"
                            hide-details="auto"
                        />
                    </v-col>
                    <v-col cols="12" md="6">
                        <v-text-field
                            v-model="form.email"
                            label="Email"
                            variant="outlined"
                            density="comfortable"
                            hide-details="auto"
                            required
                        />
                    </v-col>
                    <v-col cols="12" md="6">
                        <v-text-field
                            v-model="form.password"
                            type="password"
                            :label="isEdit ? 'Password ' + t('optional') : 'Password'"
                            :placeholder="isEdit ? t('leaveEmptyToKeepPassword') : ''"
                            variant="outlined"
                            density="comfortable"
                            hide-details="auto"
                            :required="!isEdit"
                        />
                    </v-col>
                    <v-col cols="12" md="6">
                        <v-select
                            v-model="form.role"
                            :items="roles || []"
                            item-title="name"
                            item-value="id"
                            :label="t('role')"
                            variant="outlined"
                            density="comfortable"
                            hide-details="auto"
                            required
                        />
                    </v-col>
                </v-row>

                <v-divider class="mt-4 mb-4" />

                <!-- Section: Employee Detail -->
                <div class="section-header mb-3">
                    <v-icon icon="mdi-badge-account-outline" size="18" color="primary" />
                    <span class="text-subtitle-2 font-weight-bold text-primary">Employee Detail</span>
                </div>

                <v-row dense>
                    <v-col cols="12" md="6">
                        <v-text-field
                            v-model="(form.user_details ?? {}).department"
                            label="Department"
                            variant="outlined"
                            density="comfortable"
                            hide-details="auto"
                        />
                    </v-col>
                    <v-col cols="12" md="6">
                        <v-text-field
                            v-model="(form.user_details ?? {}).sub_department"
                            label="Sub Department"
                            variant="outlined"
                            density="comfortable"
                            hide-details="auto"
                        />
                    </v-col>
                    <v-col cols="12" md="6">
                        <v-text-field
                            v-model="(form.user_details ?? {}).position"
                            label="Position"
                            variant="outlined"
                            density="comfortable"
                            hide-details="auto"
                        />
                    </v-col>
                    <v-col cols="12" md="6">
                        <v-text-field
                            v-model="(form.user_details ?? {}).direct_supervisor_position"
                            label="Direct Supervisor Position"
                            variant="outlined"
                            density="comfortable"
                            hide-details="auto"
                        />
                    </v-col>
                </v-row>

                <input type="hidden" v-model="(form.user_details ?? {}).id_karyawan" />
                <input type="hidden" v-model="(form.user_details ?? {}).direct_supervisor_id" />

            </v-card-text>

            <v-divider />

            <!-- Actions -->
            <v-card-actions class="px-6 py-4 justify-end gap-2">
                <v-btn variant="tonal" :disabled="loading" @click="closeDialog">
                    {{ t('cancel') }}
                </v-btn>
                <v-btn
                    color="primary"
                    variant="elevated"
                    :loading="loading"
                    prepend-icon="mdi-content-save-outline"
                    @click="saveUser"
                >
                    {{ t('save') }}
                </v-btn>
            </v-card-actions>

        </v-card>
    </v-dialog>

    <!-- ── Employee Picker Dialog ──────────────────────────────────── -->
    <v-dialog v-model="employeeDialog" fullscreen>
        <v-card>
            <v-toolbar color="primary">
                <v-toolbar-title class="font-weight-semibold">Select Employee</v-toolbar-title>
                <v-spacer />
                <v-btn :icon="IconamoonCloseFill" variant="text" @click="employeeDialog = false" />
            </v-toolbar>
            <v-card-text class="pa-4">
                <v-data-table-server
                    v-model:page="employeePage"
                    v-model:items-per-page="employeePerPage"
                    :items="employees"
                    :items-length="employeeTotal"
                    :loading="loading"
                    :headers="[
                        { title: 'NIK', value: 'nik' },
                        { title: t('name'), value: 'name' },
                        { title: 'Department', value: 'departemen' },
                        { title: 'Position', value: 'Jabatan' },
                        { title: t('actions'), value: 'actions', width: 20, align: 'center', sortable: false }
                    ]"
                    @update:options="loadEmployees"
                >
                    <template #item.actions="{ item }">
                        <v-btn
                            :icon="IconamoonCheck"
                            variant="tonal"
                            size="small"
                            color="primary"
                            @click="selectEmployee(item)"
                        />
                    </template>
                </v-data-table-server>
            </v-card-text>
        </v-card>
    </v-dialog>

    <!-- ── Detail Drawer ───────────────────────────────────────────── -->
    <v-navigation-drawer
        v-model="detailDialog"
        location="right"
        width="460"
        temporary
    >
        <template v-if="selectedUser">
            <v-list-item class="pa-4" :title="t('information', { field: t('userManagement') })">
                <template #append>
                    <v-btn
                        icon="mdi-close"
                        variant="text"
                        size="small"
                        @click="closeDetailDialog"
                    />
                </template>
            </v-list-item>

            <v-divider />

            <div class="pa-4">
                <v-row dense class="mb-2">
                    <v-col cols="5" class="text-caption text-medium-emphasis">{{ t('name') }}</v-col>
                    <v-col cols="7" class="text-body-2 font-weight-medium">{{ userDetail?.name || selectedUser.name }}</v-col>

                    <v-col cols="5" class="text-caption text-medium-emphasis">Email</v-col>
                    <v-col cols="7" class="text-body-2">{{ selectedUser.email }}</v-col>

                    <v-col cols="5" class="text-caption text-medium-emphasis">{{ t('role') }}</v-col>
                    <v-col cols="7">
                        <v-chip
                            v-if="(selectedUser as any).roles?.[0]"
                            size="small"
                            color="primary"
                            variant="tonal"
                            class="font-weight-medium"
                        >
                            {{ (selectedUser as any).roles[0].name }}
                        </v-chip>
                        <span v-else class="text-body-2">-</span>
                    </v-col>
                </v-row>

                <v-divider class="my-3" />

                <p class="text-subtitle-2 font-weight-bold mb-3">Employee Detail</p>

                <v-row dense>
                    <v-col cols="5" class="text-caption text-medium-emphasis">NIK</v-col>
                    <v-col cols="7" class="text-body-2">{{ userDetail?.nik || (selectedUser as any).user_details?.nik || '-' }}</v-col>

                    <v-col cols="5" class="text-caption text-medium-emphasis">Department</v-col>
                    <v-col cols="7" class="text-body-2">{{ userDetail?.department || '-' }}</v-col>

                    <v-col cols="5" class="text-caption text-medium-emphasis">Sub Department</v-col>
                    <v-col cols="7" class="text-body-2">{{ userDetail?.sub_department || '-' }}</v-col>

                    <v-col cols="5" class="text-caption text-medium-emphasis">Position</v-col>
                    <v-col cols="7" class="text-body-2">{{ userDetail?.position || '-' }}</v-col>

                    <v-col cols="5" class="text-caption text-medium-emphasis">Direct Supervisor</v-col>
                    <v-col cols="7" class="text-body-2">{{ userDetail?.direct_supervisor_position || '-' }}</v-col>
                </v-row>
            </div>
        </template>
    </v-navigation-drawer>

    <!-- ── Confirm Delete ──────────────────────────────────────────── -->
    <confirm-delete-dialog
        v-model="confirmDeleteDialog"
        @delete="handleDelete"
        @close="confirmDeleteDialog = false"
    />
</template>

<style scoped>
.section-header {
    display: flex;
    align-items: center;
    gap: 6px;
}
</style>