import { api } from './api'

export const signin = async (payload: { email: string, password: string }) => {
    return await api.post('/signin', payload)
}

export const me = async () => {
    return await api.get('/me')
}

export const signout = async () => {
    return await api.post('/signout')
}