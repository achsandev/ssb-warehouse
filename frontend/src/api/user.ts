import type { User } from '@/types/user'
import { api } from './api'
import axios from 'axios'

export async function getUsers(): Promise<User[]> {
    const res = await api.get<User[]>('/users')
    return res.data
}

export interface EmployeeParams {
    page?: number
    per_page?: number
}

export interface EmployeePaginated {
    employees: any[]
    total: number
}

export async function getEmployees(params: EmployeeParams = {}): Promise<EmployeePaginated> {
    const res = await axios.get<{ data: { employees: any[], total_count?: number, total?: number } }>(
        `${import.meta.env.VITE_API_EMPLOYEE_URL}/employee/all`,
        { params },
    )
    const d = res.data.data
    return {
        employees: d.employees ?? [],
        total: d.total_count ?? d.total ?? 0,
    }
}

export async function getRoles(): Promise<any[]> {
    const res = await api.get<{data: any[]}>('/lookup/roles')
    return res.data.data
}

export async function getUser(uid: string): Promise<User> {
    const res = await api.get<User>(`/users/${uid}`)
    return res.data
}

export async function createUser(user: Partial<User>): Promise<User> {
    const res = await api.post('/users', user)
    return res.data
}

export async function updateUser(user: User): Promise<User> {
    if (!user.uid) throw new Error('User uid is required')
    const res = await api.put<User>(`/users/${user.uid}`, user)
    return res.data
}


export async function deleteUser(uid: string): Promise<void> {
    await api.delete(`/users/${uid}`)
}
