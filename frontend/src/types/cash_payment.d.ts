type CashPaymentList = {
    uid: string
    payment_number: string
    payment_date: Date | string
    warehouse_uid: string | null
    warehouse_name: string
    cash_balance: number
    description: string | null
    amount: number | null
    spk_path: string | null
    spk_name: string | null
    spk_url: string | null
    attachment_path: string | null
    attachment_name: string | null
    attachment_url: string | null
    notes: string | null
    status: string
    created_at: Date | string
    updated_at: Date | string | null
    created_by_name: string
    updated_by_name: string | null
}

type CashPaymentForm = {
    payment_date: Date | string
    warehouse_uid: string | null
    description: string | null
    amount: number | null
    spk?: File | File[] | null | undefined
    attachment?: File | File[] | null | undefined
    notes: string | null
    status?: string
}
