<script lang="ts" setup>
import { computed, onMounted } from 'vue'
import { useNow } from '@vueuse/core'
// Stores & composables
import { useDashboardStore } from '@/stores/dashboard'
import { useAuthStore } from '@/stores/auth'
import { useAbility } from '@/composables/useAbility'
// Components
import KpiCard from '../components/KpiCard.vue'
import SectionCard from '../components/SectionCard.vue'
import StockMovementChart from '../components/StockMovementChart.vue'
import CategoryDonutChart from '../components/CategoryDonutChart.vue'
import TopItemsChart from '../components/TopItemsChart.vue'
import LowStockList from '../components/LowStockList.vue'
import RecentActivityList from '../components/RecentActivityList.vue'
import PendingApprovalsList from '../components/PendingApprovalsList.vue'
// Icons
import MdiPackageVariantClosed from '~icons/mdi/package-variant-closed'
import MdiHandshake from '~icons/mdi/handshake'
import MdiWarehouse from '~icons/mdi/warehouse'
import MdiClipboardCheck from '~icons/mdi/clipboard-check-outline'
import MdiTruckDelivery from '~icons/mdi/truck-delivery'
import MdiMovingRounded from '~icons/mdi/transit-transfer'
import MdiAlertDecagram from '~icons/mdi/alert-decagram-outline'
import MdiTrendingUp from '~icons/mdi/trending-up'
import MdiChartDonut from '~icons/mdi/chart-donut'
import MdiChartBar from '~icons/mdi/chart-bar'
import MdiHistory from '~icons/mdi/history'
import MdiClipboardClock from '~icons/mdi/clipboard-clock-outline'
import MdiRefresh from '~icons/mdi/refresh'

const store = useDashboardStore()
const authStore = useAuthStore()
const { can } = useAbility()
const now = useNow({ interval: 1000 })

// Pemetaan modul approval → CASL subject.
const APPROVAL_SUBJECTS: Record<string, string> = {
    'Item Request':     'item_request',
    'Purchase Order':   'purchase_order',
    'Receive Item':     'receive_item',
    'Item Usage':       'item_usage',
    'Stock Adjustment': 'stock_adjustment',
}

/** True jika user punya minimal satu permission approve di modul-modul di atas. */
const hasApprovalAccess = computed(() =>
    Object.values(APPROVAL_SUBJECTS).some((s) => can('approve', s)) || can('manage', 'all'),
)

/** Hanya tampilkan approval untuk modul yang user bisa approve. */
const myApprovals = computed(() =>
    store.pendingApprovals.filter((p) => {
        if (can('manage', 'all')) return true
        const subject = APPROVAL_SUBJECTS[p.module]
        return subject ? can('approve', subject) : false
    }),
)

// ─── Greeting ────────────────────────────────────────────────────────────────
const greeting = computed(() => {
    const h = now.value.getHours()
    if (h < 11) return 'Selamat Pagi'
    if (h < 15) return 'Selamat Siang'
    if (h < 18) return 'Selamat Sore'
    return 'Selamat Malam'
})

const currentDateLabel = computed(() =>
    now.value.toLocaleDateString('id-ID', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' }),
)

const currentTimeLabel = computed(() =>
    now.value.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
)

// ─── KPI mapping ─────────────────────────────────────────────────────────────
const kpiList = computed(() => {
    const k = store.kpis
    if (!k) return []
    return [
        { label: 'Total Item', value: k.total_items, icon: MdiPackageVariantClosed, color: 'primary',    hint: 'di katalog' },
        { label: 'Supplier',   value: k.total_suppliers, icon: MdiHandshake,        color: 'info',       hint: 'aktif' },
        { label: 'Gudang',     value: k.total_warehouses, icon: MdiWarehouse,        color: 'teal',       hint: 'lokasi' },
        { label: 'PO Bulan Ini', value: k.po_this_month, icon: MdiClipboardCheck,   color: 'success',    hint: 'pembelian' },
        { label: 'Penerimaan Bulan Ini', value: k.receipt_this_month, icon: MdiTruckDelivery, color: 'blue-darken-1', hint: 'transaksi' },
        { label: 'Pemakaian Bulan Ini', value: k.usage_this_month, icon: MdiMovingRounded, color: 'deep-orange', hint: 'transaksi' },
    ]
})

const totalPending = computed(() => {
    const k = store.kpis
    if (!k) return 0
    return (k.pending_request_approval ?? 0)
        + (k.pending_po_approval ?? 0)
        + (k.pending_receipt_approval ?? 0)
})

// ─── Actions ─────────────────────────────────────────────────────────────────
const refresh = () => store.fetch()

onMounted(() => store.fetch())
</script>

<template>
    <div class="dashboard">
        <!-- Hero / Greeting -->
        <section class="hero-band mb-4">
            <div class="hero-band__content">
                <div>
                    <div class="text-body-2 text-medium-emphasis">{{ currentDateLabel }} · {{ currentTimeLabel }}</div>
                    <h1 class="hero-title">
                        {{ greeting }}, <span class="hero-name">{{ authStore.user?.name ?? 'User' }}</span>
                    </h1>
                    <p class="text-body-2 text-medium-emphasis mb-0">
                        Ringkasan operasional gudang — diperbarui realtime saat halaman dibuka.
                    </p>
                </div>
                <div class="d-flex align-center ga-2">
                    <v-chip
                        v-if="totalPending > 0"
                        color="warning"
                        variant="flat"
                        :prepend-icon="MdiAlertDecagram"
                        class="font-weight-bold"
                    >
                        {{ totalPending }} approval menunggu
                    </v-chip>
                    <v-btn
                        :icon="MdiRefresh"
                        variant="tonal"
                        size="small"
                        :loading="store.loading"
                        :disabled="store.loading"
                        @click="refresh"
                    />
                </div>
            </div>
        </section>

        <!-- KPI Grid -->
        <transition-group name="stagger" tag="div" class="kpi-grid">
            <div
                v-for="(kpi, idx) in kpiList"
                :key="kpi.label"
                class="kpi-item"
                :style="{ animationDelay: `${idx * 60}ms` }"
            >
                <kpi-card
                    :label="kpi.label"
                    :value="kpi.value"
                    :icon="kpi.icon"
                    :color="kpi.color"
                    :hint="kpi.hint"
                />
            </div>
        </transition-group>

        <!-- Row: stock movement + donut -->
        <v-row class="mt-3">
            <v-col cols="12" lg="8">
                <section-card
                    title="Pergerakan Stok"
                    subtitle="Penerimaan vs Pemakaian — 12 bulan terakhir"
                    :icon="MdiChartBar"
                    color="primary"
                    :loading="store.loading"
                >
                    <stock-movement-chart :data="store.stockMovement" />
                </section-card>
            </v-col>
            <v-col cols="12" lg="4">
                <section-card
                    title="Distribusi Kategori"
                    subtitle="Jumlah item per kategori"
                    :icon="MdiChartDonut"
                    color="info"
                    :loading="store.loading"
                >
                    <category-donut-chart :data="store.categoryDistribution" />
                </section-card>
            </v-col>
        </v-row>

        <!-- Row: top items + low stock -->
        <v-row class="mt-1">
            <v-col cols="12" lg="7">
                <section-card
                    title="Barang Paling Sering Diminta"
                    subtitle="Top 7 dalam 12 bulan terakhir"
                    :icon="MdiTrendingUp"
                    color="success"
                    :loading="store.loading"
                >
                    <top-items-chart :data="store.topRequestedItems" />
                </section-card>
            </v-col>
            <v-col cols="12" lg="5">
                <section-card
                    title="Peringatan Stok Rendah"
                    subtitle="Di bawah minimum stock"
                    :icon="MdiAlertDecagram"
                    color="error"
                    :loading="store.loading"
                >
                    <low-stock-list :items="store.lowStock" />
                </section-card>
            </v-col>
        </v-row>

        <!-- Row: pending approvals + recent activity -->
        <v-row class="mt-1 mb-2">
            <!-- Approvals hanya muncul untuk user yang punya hak approve pada modul tsb. -->
            <v-col v-if="hasApprovalAccess" cols="12" lg="5">
                <section-card
                    title="Approval Menunggu"
                    subtitle="Klik untuk menuju modul terkait"
                    :icon="MdiClipboardClock"
                    color="warning"
                    :loading="store.loading"
                >
                    <pending-approvals-list :items="myApprovals" />
                </section-card>
            </v-col>
            <v-col cols="12" :lg="hasApprovalAccess ? 7 : 12">
                <section-card
                    title="Aktivitas Terbaru"
                    subtitle="10 kejadian terakhir"
                    :icon="MdiHistory"
                    color="primary"
                    :loading="store.loading"
                >
                    <recent-activity-list :items="store.recentActivity" />
                </section-card>
            </v-col>
        </v-row>
    </div>
</template>

<style scoped>
.dashboard {
    min-height: 100%;
}

/* ── Hero band ─────────────────────────────────────────────────────────── */
.hero-band {
    background: linear-gradient(135deg,
        rgba(var(--v-theme-primary), 0.08) 0%,
        rgba(var(--v-theme-primary), 0.02) 100%);
    border: 1px solid rgba(var(--v-theme-primary), 0.15);
    border-radius: 16px;
    padding: 20px 24px;
    animation: fade-down 0.4s ease both;
}
.hero-band__content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}
.hero-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 2px 0 4px;
    line-height: 1.2;
}
.hero-name {
    background: linear-gradient(135deg, rgb(var(--v-theme-primary)) 0%, rgb(var(--v-theme-info)) 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}

/* ── KPI grid ──────────────────────────────────────────────────────────── */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 12px;
}
.kpi-item {
    min-width: 0;  /* izinkan isi mengikuti lebar cell */
    display: flex; /* card mengisi tinggi cell */
}
.kpi-item > * {
    width: 100%;
}
@media (max-width: 1280px) { .kpi-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
@media (max-width: 900px)  { .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 600px)  { .kpi-grid { grid-template-columns: minmax(0, 1fr); } }

/* Staggered entrance animation untuk KPI card */
.stagger-enter-active {
    animation: kpi-in 0.45s cubic-bezier(.22, 1, .36, 1) both;
}
@keyframes kpi-in {
    from { opacity: 0; transform: translateY(10px) scale(0.98); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes fade-down {
    from { opacity: 0; transform: translateY(-8px); }
    to   { opacity: 1; transform: translateY(0); }
}

@media (max-width: 600px) {
    .hero-band { padding: 16px; }
    .hero-title { font-size: 1.25rem; }
}
</style>
