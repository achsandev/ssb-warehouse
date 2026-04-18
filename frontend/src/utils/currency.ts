export const formatRupiah = (value: string | number | undefined): string => {
    if (!value && value !== 0) return 'Rp 0'
    
    const num = Math.floor(Number(value))
    if (isNaN(num)) return 'Rp 0'
    const format = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(num)

    return format
}

export const unformatCurrency = (value: string): number => {
    const cleanValue = String(value).replace(/\D/g, '')
    return Number(cleanValue)
}