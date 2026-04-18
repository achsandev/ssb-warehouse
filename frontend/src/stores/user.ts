import type { User } from '@/types/user'
import { defineStore } from 'pinia'

interface State {
    users: User[]
    employees: any[]
    employeeTotal: number
    roles: any[]
    loading: boolean
    error: string | null
    selectedUser: User | null
}

export const useUserStore = defineStore('user', {
    state: (): State => ({
        users: [],
        employees: [],
        employeeTotal: 0,
        roles: [],
        loading: false,
        error: null,
        selectedUser: null
    }),
    actions: {
        async fetchUsers() {
            this.loading = true
            try {
                const { getUsers } = await import('@/api/user')
                const result = await getUsers()
                this.users = Array.isArray(result) ? result : []
                this.error = null
            } catch (e: any) {
                this.error = e.message
            } finally {
                this.loading = false
            }
        },
        async fetchEmployees(params: import('@/api/user').EmployeeParams = {}) {
            this.loading = true
            try {
                const { getEmployees } = await import('@/api/user')
                const result = await getEmployees(params)
                this.employees = result.employees
                this.employeeTotal = result.total
                this.error = null
            } catch (e: any) {
                this.error = e.message
            } finally {
                this.loading = false
            }
        },
        async fetchRoles() {
            try {
                const { getRoles } = await import('@/api/user')
                const result = await getRoles()
                this.roles = Array.isArray(result) ? result : []
            } catch (e: any) {
                console.error('Failed to fetch roles:', e.message)
            }
        },
        async createUser(user: Partial<User>) {
            try {
                const { createUser } = await import('@/api/user')
                await createUser(user)
                this.error = null
            } catch (e: any) {
                this.error = e.message
            }
        },
        async updateUser(user: User) {
            try {
                const { updateUser } = await import('@/api/user')
                await updateUser(user)
                this.error = null
            } catch (e: any) {
                this.error = e.message
            }
        },
        async deleteUser(uid: string) {
            try {
                const { deleteUser } = await import('@/api/user')
                await deleteUser(uid)
                this.error = null
            } catch (e: any) {
                this.error = e.message
            }
        },
        selectUser(user: User) {
            this.selectedUser = user
        },
        clearSelectedUser() {
            this.selectedUser = null
        }
    }
})
