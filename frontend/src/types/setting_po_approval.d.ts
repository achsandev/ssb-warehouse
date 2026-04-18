type SettingPoApprovalList = {
    uid: string
    level: number
    role: {
        uid: string
        name: string
    } | null
    min_amount: number | null
    description: string | null
    is_active: boolean
    created_at: Date | string
    updated_at: Date | string | null
    created_by_name: string
    updated_by_name: string | null
}

type SettingPoApprovalForm = {
    level: number | null
    role_uid: string | null
    min_amount: number | string | null
    description: string | null
    is_active: boolean
}
