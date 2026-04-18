export type SettingApproverItemRequest = {
    uid: string
    requester_role_id: number
    requester_role_name: string
    approver_role_id: number
    approver_role_name: string
    created_at: Date | string
    updated_at: Date | string | null
    created_by_name: string | null
    updated_by_name: string | null
}
