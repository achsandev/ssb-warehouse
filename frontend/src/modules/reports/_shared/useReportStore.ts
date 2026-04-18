import { defineStore } from 'pinia'
import { useMessageStore } from '@/stores/message'
import { api } from '@/api/api'

export interface ReportListParams {
    start_date?: string | null
    end_date?: string | null
    status?: string | null
    search?: string | null
    page?: number
    per_page?: number
}

export interface ReportExportParams {
    start_date?: string | null
    end_date?: string | null
    status?: string | null
    search?: string | null
}

const cleanParams = <T extends Record<string, any>>(input: T): Partial<T> => {
    const out: Record<string, any> = {}
    Object.entries(input).forEach(([k, v]) => {
        if (v === null || v === undefined || v === '') return
        out[k] = v
    })
    return out as Partial<T>
}

/**
 * Factory Pinia store untuk report. Semua report pakai kontrak yang sama
 * (rentang tanggal + status + search + pagination).
 *
 * @param id          Unique store id (mis. 'stockUsageReport').
 * @param basePath    Base path API tanpa leading slash (mis. '/reports/stock_usage').
 */
export const createReportStore = <TRow = any>(id: string, basePath: string) => {
    return defineStore(id, {
        state: () => ({
            data: [] as TRow[],
            pagination: { current_page: 1, per_page: 10, last_page: 1 },
            total: 0,
            loading: false,
            exporting: false,
            error: false,
        }),

        actions: {
            async fetch(payload: ReportListParams) {
                const message = useMessageStore()
                this.loading = true
                this.error = false
                try {
                    const { data } = await api.get(basePath, { params: cleanParams(payload) })
                    this.data = data.data?.data ?? []
                    this.pagination = data.data?.meta ?? this.pagination
                    this.total = data.data?.meta?.total ?? 0
                } catch (err: any) {
                    this.error = true
                    message.setMessage({
                        text: err.response?.data?.message ?? err.response?.statusText ?? 'Failed to load report',
                        timeout: 3000,
                        color: 'error',
                    })
                } finally {
                    this.loading = false
                }
            },

            async export(payload: ReportExportParams) {
                const message = useMessageStore()
                this.exporting = true
                this.error = false
                try {
                    const response = await api.get(`${basePath}/export`, {
                        params: cleanParams(payload),
                        responseType: 'blob',
                    })

                    const disposition: string = response.headers?.['content-disposition'] ?? ''
                    const match = /filename="?([^"]+)"?/i.exec(disposition)
                    const contentType: string = response.headers?.['content-type'] ?? 'application/octet-stream'
                    const ext = contentType.includes('spreadsheetml') ? 'xlsx' : 'csv'
                    const filename = match?.[1] ?? `${id}_${Date.now()}.${ext}`

                    const blob = new Blob([response.data], { type: contentType })
                    const url = URL.createObjectURL(blob)
                    const link = document.createElement('a')
                    link.href = url
                    link.download = filename
                    document.body.appendChild(link)
                    link.click()
                    document.body.removeChild(link)
                    URL.revokeObjectURL(url)

                    message.setMessage({
                        text: 'Report exported successfully',
                        timeout: 1500,
                        color: 'success',
                    })
                } catch (err: any) {
                    this.error = true
                    let text = err.response?.statusText ?? 'Failed to export report'
                    if (err.response?.data instanceof Blob) {
                        try {
                            const parsed = JSON.parse(await err.response.data.text())
                            text = parsed.message ?? text
                        } catch { /* ignore */ }
                    } else if (err.response?.data?.message) {
                        text = err.response.data.message
                    }
                    message.setMessage({ text, timeout: 3000, color: 'error' })
                } finally {
                    this.exporting = false
                }
            },
        },
    })
}
