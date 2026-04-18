import { api } from './api'

export const getSummary = async () => api.get('/dashboard/summary')
