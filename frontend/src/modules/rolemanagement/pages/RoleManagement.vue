<script lang="ts" setup>
    import { ref, onMounted, computed } from 'vue'
    import { useRoleStore } from '@/stores/role'
    import { storeToRefs } from 'pinia'
    import type { Role } from '@/types/role'
    import { useTranslate } from '@/composables/useTranslate'
    import { toCamelCase } from '@/utils/string-case'
    import BaseSearchInput from '@/components/base/BaseSearchInput.vue'
    import BaseRefreshButton from '@/components/base/BaseRefreshButton.vue'
    import BaseCreateButton from '@/components/base/BaseCreateButton.vue'
    import ActionsMenu from '@/components/common/ActionsMenu.vue'
    import ConfirmDeleteDialog from '@/components/common/ConfirmDeleteDialog.vue'
    import { ability } from '@/plugins/casl'
    import { useMessageStore } from '@/stores/message'

    const t = useTranslate()
    const roleStore = useRoleStore()
    const message = useMessageStore()
    const { roles, permissions, loading, selectedRole } = storeToRefs(roleStore)

    const dialog = ref(false)
    const detailDialog = ref(false)
    const confirmDeleteDialog = ref(false)
    const isEdit = ref(false)
    const form = ref<Partial<Role>>({ permissions: [] })
    const pendingDeleteRole = ref<Role | null>(null)
    const search = ref('')

    onMounted(() => {
        roleStore.fetchRoles()
        roleStore.fetchPermissions()
    })

    const groupedPermissions = computed(() => {
        const groups = {} as Record<string, { id: number, name: string, action: string }[]>
        (permissions.value || []).forEach(perm => {
            const [module, action] = perm.name.split('.')
            if (!groups[module]) groups[module] = []
            groups[module].push({ ...perm, action })
        })
        return groups
    })

    const filteredRoles = computed(() => {
        if (!search.value) return roles.value
        return roles.value.filter(role =>
            (role.name || '').toLowerCase().includes(search.value.toLowerCase()) ||
            (role.guard_name || '').toLowerCase().includes(search.value.toLowerCase())
        )
    })

    const openCreate = () => {
        isEdit.value = false
        form.value = { name: '', guard_name: 'web', permissions: [] }
        dialog.value = true
    }

    const openEdit = (role: Role) => {
        isEdit.value = true
        form.value = { ...role, permissions: [...(role.permissions || [])] }
        dialog.value = true
    }

    const formTitle = computed(() => 
        isEdit.value === false
            ? t('createDataDialogTitle')
            : t('updateDataDialogTitle')
    )

    const openDetail = (role: Role) => {
        roleStore.selectRole(role)
        detailDialog.value = true
    }

    const getActionMenus = () => {
        const menus = []
        // If user has manage.all permission, allow all actions
        if (ability.can('manage', 'all')) {
            menus.push('detail', 'update', 'delete')
            return menus;
        }
        if (ability.can('read', 'role_management')) menus.push('detail')
        if (ability.can('update', 'role_management')) menus.push('update')
        if (ability.can('delete', 'role_management')) menus.push('delete')
        return menus;
    };

    const handleActionMenu = (action: string, data: Role) => {
        if (action === 'detail') {
            return openDetail(data)
        }
        if (action === 'update') {
            return openEdit(data)
        }
        return removeRole(data)
    }

    const closeDialog = () => {
        dialog.value = false
        form.value = { permissions: [] }
    }

    const closeDetailDialog = () => {
        detailDialog.value = false
        roleStore.clearSelectedRole()
    }

    const actionColor: Record<string, string> = {
        manage:  'purple',
        read:    'blue',
        create:  'green',
        update:  'orange',
        delete:  'red',
        approve: 'teal',
    }

    const saveRole = async () => {
        if (isEdit.value && form.value.id) {
            await roleStore.updateRole(form.value as Role)
        } else {
            await roleStore.createRole(form.value)
        }
        if (roleStore.error) {
            message.setMessage({ text: roleStore.error, color: 'error', timeout: 4000 })
        } else {
            message.setMessage({ text: t('savedSuccessfully'), color: 'success', timeout: 3000 })
            dialog.value = false
            roleStore.fetchRoles()
        }
    }

    const removeRole = async (role: Role) => {
        pendingDeleteRole.value = role
        confirmDeleteDialog.value = true
    }

    const handleDelete = async () => {
        const role = pendingDeleteRole.value
        if (role?.id) {
            await roleStore.deleteRole(role.id)
            if (roleStore.error) {
                message.setMessage({ text: roleStore.error, color: 'error', timeout: 4000 })
            } else {
                message.setMessage({ text: t('deletedSuccessfully'), color: 'success', timeout: 3000 })
                roleStore.fetchRoles()
            }
        }
        confirmDeleteDialog.value = false
        pendingDeleteRole.value = null
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
                    @update:model-value="roleStore.fetchRoles()"
                />

                <base-refresh-button
                    :loading="loading"
                    :disabled="loading"
                    @click="roleStore.fetchRoles()"
                />
            </div>
        </v-card-title>
        <v-card-text class="overflow-x-scroll">
            <v-data-table :items="filteredRoles" :loading="loading" :headers="[
                { title: t('actions'), value: 'actions', width: '20', sortable: false },
                { title: t('name'), value: 'name' },
                { title: 'Guard', value: 'guard_name' }
            ]">
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
                <v-icon icon="mdi-shield-crown-outline" color="primary" class="me-1" />
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
                <div class="section-header mb-3">
                    <v-icon icon="mdi-information-outline" size="18" color="primary" />
                    <span class="text-subtitle-2 font-weight-bold text-primary">{{ t('generalInfo') }}</span>
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
                            v-model="form.guard_name"
                            label="Guard Name"
                            variant="outlined"
                            density="comfortable"
                            hide-details="auto"
                            required
                        />
                    </v-col>
                </v-row>

                <v-divider class="mt-4 mb-4" />

                <!-- Section: Permissions -->
                <div class="section-header mb-3">
                    <v-icon icon="mdi-lock-outline" size="18" color="primary" />
                    <span class="text-subtitle-2 font-weight-bold text-primary">Permissions</span>
                    <v-chip
                        v-if="(form.permissions || []).length"
                        size="x-small"
                        color="primary"
                        variant="tonal"
                        class="ms-1"
                    >
                        {{ (form.permissions || []).length }}
                    </v-chip>
                </div>

                <v-sheet rounded="lg" border class="overflow-hidden">
                    <v-table density="compact" class="permission-table">
                        <thead>
                            <tr>
                                <th>{{ t('module') }}</th>
                                <th
                                    v-for="action in ['manage','read','create','update','delete','approve']"
                                    :key="action"
                                    class="text-center"
                                >
                                    {{ action }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(perms, module) in groupedPermissions" :key="module">
                                <td class="text-body-2 font-weight-medium">{{ t(toCamelCase(module)) }}</td>
                                <td
                                    v-for="action in ['manage','read','create','update','delete','approve']"
                                    :key="action"
                                    class="text-center"
                                >
                                    <v-checkbox
                                        v-if="perms.some(p => p.action === action)"
                                        :value="`${module}.${action}`"
                                        v-model="form.permissions"
                                        hide-details
                                        density="compact"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </v-table>
                </v-sheet>

            </v-card-text>

            <v-divider />

            <!-- Actions -->
            <v-card-actions class="px-6 py-4 justify-end gap-2">
                <v-btn
                    variant="tonal"
                    :disabled="loading"
                    @click="closeDialog"
                >
                    {{ t('cancel') }}
                </v-btn>
                <v-btn
                    color="primary"
                    variant="elevated"
                    :loading="loading"
                    prepend-icon="mdi-content-save-outline"
                    @click="saveRole"
                >
                    {{ t('save') }}
                </v-btn>
            </v-card-actions>

        </v-card>
    </v-dialog>

    <!-- ── Detail Drawer ───────────────────────────────────────────── -->
    <v-navigation-drawer
        v-model="detailDialog"
        location="right"
        width="460"
        temporary
    >
        <template v-if="selectedRole">
            <!-- Header -->
            <v-list-item class="pa-4" :title="t('information', { field: t('roleManagement') })">
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
                <!-- Role info -->
                <v-row dense class="mb-2">
                    <v-col cols="5" class="text-caption text-medium-emphasis">ID</v-col>
                    <v-col cols="7" class="text-body-2 font-weight-medium">{{ selectedRole.id }}</v-col>

                    <v-col cols="5" class="text-caption text-medium-emphasis">{{ t('name') }}</v-col>
                    <v-col cols="7" class="text-body-2 font-weight-medium">{{ selectedRole.name }}</v-col>

                    <v-col cols="5" class="text-caption text-medium-emphasis">Guard Name</v-col>
                    <v-col cols="7" class="text-body-2">{{ selectedRole.guard_name }}</v-col>
                </v-row>

                <v-divider class="my-3" />

                <!-- Permissions grouped -->
                <p class="text-subtitle-2 font-weight-bold mb-3">Permissions</p>

                <template v-if="(selectedRole.permissions || []).length">
                    <div
                        v-for="(perms, module) in groupedPermissions"
                        :key="module"
                        class="mb-3"
                    >
                        <template v-if="(selectedRole.permissions || []).some(p => p.startsWith(`${module}.`))">
                            <p class="text-caption text-medium-emphasis text-uppercase mb-1">
                                {{ t(toCamelCase(String(module))) }}
                            </p>
                            <div class="d-flex flex-wrap gap-1">
                                <v-chip
                                    v-for="perm in (selectedRole.permissions || []).filter(p => p.startsWith(`${module}.`))"
                                    :key="perm"
                                    size="small"
                                    :color="actionColor[perm.split('.')[1]] ?? 'grey'"
                                    variant="tonal"
                                    class="font-weight-medium"
                                >
                                    {{ perm.split('.')[1] }}
                                </v-chip>
                            </div>
                        </template>
                    </div>
                </template>
                <p v-else class="text-body-2 text-medium-emphasis">-</p>
            </div>
        </template>
    </v-navigation-drawer>

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

.permission-table :deep(thead th) {
    font-size: 0.7rem !important;
    font-weight: 700 !important;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    white-space: nowrap;
    background-color: rgba(var(--v-theme-primary), 1) !important;
    color: #ffffff !important;
}

.permission-table :deep(tbody tr:hover td) {
    background-color: rgba(var(--v-theme-primary), 0.04);
}

.permission-table :deep(tbody td) {
    vertical-align: middle;
}
</style>