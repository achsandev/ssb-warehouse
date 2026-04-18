export const formatDate = (value?: Date | string) => {
    if (!value) return ''

    const date = new Date(value)
    return date.toISOString().split('T')[0]
}