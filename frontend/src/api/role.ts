import type { Role } from '@/types/role'
import { api } from './api'

export async function getRoles(): Promise<{ data: Role[] }> {
  const res = await api.get<{ data: Role[] }>('/roles')
  return res.data
}

export async function getPermissions(): Promise<any> {
  const res = await api.get('/permissions')
  return res.data
}

export async function getRole(id: string): Promise<Role> {
  const res = await api.get<Role>(`/roles/${id}`)
  return res.data
}

export async function createRole(role: Partial<Role>): Promise<Role> {
  const res = await api.post<Role>('/roles', role)
  return res.data
}

export async function updateRole(role: Role): Promise<Role> {
  if (!role.id) throw new Error('Role id is required')
  const res = await api.put<Role>(`/roles/${role.id}`, role)
  return res.data
}

export async function deleteRole(id: string): Promise<void> {
  await api.delete(`/roles/${id}`)
}
