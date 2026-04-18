import { defineStore } from 'pinia'
import { getSummary } from '@/api/dashboard.api'
import { useMessageStore } from './message'

// ─── Types ───────────────────────────────────────────────────────────────────
export interface DashboardKpis {
    total_items: number
    total_suppliers: number
    total_warehouses: number
    po_this_month: number
    receipt_this_month: number
    usage_this_month: number
    pending_po_approval: number
    pending_receipt_approval: number
    pending_request_approval: number
    po_this_year: number
}

export interface StockMovementPoint {
    month: string
    receive: number
    usage: number
}

export interface CategorySlice {
    name: string
    total: number
}

export interface TopItem {
    name: string
    total: number
}

export interface LowStockItem {
    name: string
    min_qty: number
    current_stock: number
    pct: number
}

export interface ActivityItem {
    type: 'receipt' | 'usage' | 'request'
    number: string
    user: string
    status: string
    date: string
}

export interface PendingApproval {
    module: string
    count: number
    route: string
}

interface DashboardState {
    kpis: DashboardKpis | null
    stockMovement: StockMovementPoint[]
    categoryDistribution: CategorySlice[]
    topRequestedItems: TopItem[]
    lowStock: LowStockItem[]
    recentActivity: ActivityItem[]
    pendingApprovals: PendingApproval[]
    loading: boolean
    error: boolean
}

export const useDashboardStore = defineStore('dashboard', {
    state: (): DashboardState => ({
        kpis: null,
        stockMovement: [],
        categoryDistribution: [],
        topRequestedItems: [],
        lowStock: [],
        recentActivity: [],
        pendingApprovals: [],
        loading: false,
        error: false,
    }),

    actions: {
        async fetch() {
            const message = useMessageStore()
            this.loading = true
            this.error = false
            try {
                const { data } = await getSummary()
                const payload = data.data ?? {}
                this.kpis = payload.kpis ?? null
                this.stockMovement = payload.stock_movement ?? []
                this.categoryDistribution = payload.category_distribution ?? []
                this.topRequestedItems = payload.top_requested_items ?? []
                this.lowStock = payload.low_stock ?? []
                this.recentActivity = payload.recent_activity ?? []
                this.pendingApprovals = payload.pending_approvals ?? []
            } catch (err: any) {
                this.error = true
                message.setMessage({
                    text: err.response?.data?.message ?? err.response?.statusText ?? 'Failed to load dashboard',
                    timeout: 3000,
                    color: 'error',
                })
            } finally {
                this.loading = false
            }
        },
    },
})
