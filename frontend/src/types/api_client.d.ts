type ApiClientTokenMeta = {
    name: string,
    abilities: string[],
    last_used_at: string | null,
    expires_at: string | null,
    is_expired: boolean,
    created_at: string,
}

type ApiClientList = {
    uid: string,
    name: string,
    url: string,
    description: string | null,
    is_active: boolean,
    enforce_origin: boolean,
    token: ApiClientTokenMeta | null,
    created_at: string | Date,
    updated_at: string | Date | null,
    created_by_name: string | null,
    updated_by_name: string | null,
}

type ApiClientForm = {
    uid?: string,
    name: string,
    url: string,
    description?: string | null,
    is_active: boolean,
    enforce_origin: boolean,
}

type ApiClientGenerateTokenForm = {
    name: string,
    abilities: string[],
    expires_at?: string | null,
}

type ApiClientGeneratedToken = {
    plain_text_token: string,
    name: string,
    abilities: string[],
    expires_at: string | null,
    created_at: string,
}
