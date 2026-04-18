import type { Role } from '@/types/role'
import { defineStore } from 'pinia'

interface State {
    roles: Role[]
    permissions: any[]
    loading: boolean
    error: string | null
    selectedRole: Role | null
}

export const useRoleStore = defineStore('role', {
    state: (): State => ({
        roles: [],
        permissions: [],
        loading: false,
        error: null,
        selectedRole: null
    }),
    actions: {
        async fetchRoles() {
            this.loading = true
            try {
                const { getRoles } = await import('@/api/role')
                const result = await getRoles()
                this.roles = Array.isArray(result?.data) ? result.data : []
                this.error = null
            } catch (e: any) {
                this.error = e.message
            } finally {
                this.loading = false
            }
        },
        async fetchPermissions() {
            this.loading = true
            try {
                const { getPermissions } = await import('@/api/role')
                const result = await getPermissions()
                this.permissions = Array.isArray(result) ? result : []
                this.error = null
            } catch (e: any) {
                this.error = e.message
            } finally {
                this.loading = false
            }
        },
        async createRole(role: Partial<Role>) {
            try {
                const { createRole } = await import('@/api/role')
                await createRole(role)
                this.error = null
            } catch (e: any) {
                this.error = e.message
            }
        },
        async updateRole(role: Role) {
            try {
                const { updateRole } = await import('@/api/role')
                await updateRole(role)
                this.error = null

                const { useAuthStore } = await import('@/stores/auth')
                const authStore = useAuthStore()
                await authStore.refreshPermissions()
            } catch (e: any) {
                this.error = e.message
            }
        },
        async deleteRole(id: string) {
            try {
                const { deleteRole } = await import('@/api/role')
                await deleteRole(id)
                this.error = null
            } catch (e: any) {
                this.error = e.message
            }
        },
        selectRole(role: Role) {
            this.selectedRole = role
        },
        clearSelectedRole() {
            this.selectedRole = null
        }
    }
})
