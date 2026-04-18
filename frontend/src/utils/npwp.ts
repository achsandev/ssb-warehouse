export const formatNpwp = (str: string): string => {
    const digits = str.replace(/\D/g, '')

    if (digits.length === 15) {
        return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5, 8)}.${digits.slice(8, 9)}-${digits.slice(9, 12)}.${digits.slice(12, 15)}`
    } else if (digits.length === 21) {
        return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5, 8)}.${digits.slice(8, 9)}-${digits.slice(9, 12)}.${digits.slice(12, 15)}-${digits.slice(15, 21)}`
    } else {
        return str
    }
}

export const unformatNpwp = (str: string): string => {
    return str.replace(/\D/g, '')
}