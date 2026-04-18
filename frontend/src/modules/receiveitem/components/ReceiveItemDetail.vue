<script lang="ts" setup>
import { computed } from 'vue'
import type { DataTableHeader } from 'vuetify'
// Composables
import { useTranslate } from '@/composables/useTranslate'
// Utils
import { formatDate } from '@/utils/date'
import { formatRupiah } from '@/utils/currency'
// Icons
import MdiClose from '~icons/mdi/close'
import MdiTruckDelivery from '~icons/mdi/truck-delivery'
import MdiClipboardListOutline from '~icons/mdi/clipboard-list-outline'
import MdiInformationOutline from '~icons/mdi/information-outline'
import MdiAccountCircleOutline from '~icons/mdi/account-circle-outline'
import MdiCashMultiple from '~icons/mdi/cash-multiple'

const model = defineModel<boolean>()

const props = defineProps<{
    items: ReceiveItemList
}>()

const emit = defineEmits<{
    (e: 'close'): void
}>()

const t = useTranslate()

// ─── Derived ─────────────────────────────────────────────────────────────────
const details = computed(() => props.items.details ?? [])

const subtotal = computed(() =>
    details.value.reduce((sum, d) => sum + (Number(d.qty) || 0) * (Number(d.price) || 0), 0),
)

const grandTotal = computed(() => subtotal.value + (Number(props.items.shipping_cost) || 0))

const totalQty = computed(() => details.value.reduce((sum, d) => sum + (Number(d.qty) || 0), 0))
const totalReceived = computed(() => details.value.reduce((sum, d) => sum + (Number(d.qty_received) || 0), 0))

const statusColor = computed(() => {
    switch ((props.items.status ?? '').toLowerCase()) {
        case 'waiting approval': return 'warning'
        case 'approved':         return 'success'
        case 'rejected':         return 'red'
        case 'revised':          return 'deep-orange'
        default:                 return 'grey'
    }
})

const headers = computed<DataTableHeader[]>(() => [
    { title: '#', key: 'no', align: 'center', width: 48, sortable: false },
    { title: t('name'), key: 'name', align: 'start', sortable: false },
    { title: t('unit'), key: 'unit', align: 'center', sortable: false, width: 72 },
    { title: t('supplier'), key: 'supplier', align: 'start', sortable: false },
    { title: t('qty'), key: 'qty', align: 'end', sortable: false, width: 72 },
    { title: t('qtyReceived'), key: 'qty_received', align: 'end', sortable: false, width: 92 },
    { title: t('price'), key: 'price', align: 'end', sortable: false },
    { title: t('total'), key: 'total', align: 'end', sortable: false },
])

const handleClose = () => emit('close')
</script>

<template>
    <v-overlay
        v-model="model"
        class="detail-overlay"
        persistent
        @click:outside="handleClose"
    />

    <v-navigation-drawer
        v-model="model"
        class="detail-drawer"
        location="right"
        :width="720"
        temporary
        :scrim="false"
    >
        <!-- Header -->
        <v-toolbar color="transparent" density="comfortable" flat>
            <template #prepend>
                <v-icon :icon="MdiTruckDelivery" class="ms-2" />
            </template>
            <v-toolbar-title class="text-subtitle-1 font-weight-bold">
                {{ t('dataForm', { field: t('receiveItem') }) }}
            </v-toolbar-title>
            <template #append>
                <v-btn
                    :icon="MdiClose"
                    density="comfortable"
                    variant="text"
                    :aria-label="t('close')"
                    @click="handleClose"
                />
            </template>
        </v-toolbar>

        <div class="detail-body">
            <!-- Summary strip -->
            <v-sheet class="summary-strip pa-4">
                <div class="summary-strip__main">
                    <div class="summary-strip__number">{{ items.receipt_number }}</div>
                    <div class="summary-strip__date">{{ formatDate(items.receipt_date) }}</div>
                </div>
                <v-chip
                    :color="statusColor"
                    variant="tonal"
                    size="small"
                    class="font-weight-bold"
                >
                    {{ items.status }}
                </v-chip>
            </v-sheet>

            <!-- Reject reason banner -->
            <v-alert
                v-if="items.reject_reason"
                type="error"
                variant="tonal"
                density="compact"
                :title="t('rejectReason')"
                class="reject-alert"
            >
                {{ items.reject_reason }}
            </v-alert>

            <!-- Receipt Info -->
            <section class="section">
                <header class="section-header">
                    <v-icon :icon="MdiInformationOutline" size="18" />
                    <span>{{ t('generalInfo') }}</span>
                </header>
                <v-divider class="mb-2" />
                <dl class="info-grid">
                    <div class="info-item">
                        <dt>{{ t('projectName') }}</dt>
                        <dd>{{ items.project_name || '-' }}</dd>
                    </div>
                    <div class="info-item">
                        <dt>{{ t('warehouse') }}</dt>
                        <dd>{{ items.warehouse?.name || '-' }}</dd>
                    </div>
                    <div class="info-item">
                        <dt>{{ t('purchaseOrder') }}</dt>
                        <dd>{{ items.purchase_order?.po_number || '-' }}</dd>
                    </div>
                    <div class="info-item">
                        <dt>{{ t('shippingCost') }}</dt>
                        <dd class="font-weight-medium">{{ formatRupiah(items.shipping_cost ?? 0) }}</dd>
                    </div>
                </dl>
            </section>

            <!-- Details -->
            <section class="section">
                <header class="section-header">
                    <v-icon :icon="MdiClipboardListOutline" size="18" />
                    <span>{{ t('details') }}</span>
                    <v-spacer />
                    <v-chip size="x-small" variant="tonal" color="primary">
                        {{ details.length }} {{ t('items') }}
                    </v-chip>
                </header>
                <v-divider class="mb-2" />

                <v-alert
                    v-if="!details.length"
                    type="info"
                    variant="tonal"
                    density="compact"
                >
                    {{ t('noDataAvailable') }}
                </v-alert>

                <v-data-table
                    v-else
                    class="detail-table"
                    :headers="headers"
                    :items="details"
                    :items-per-page="-1"
                    hide-default-footer
                    density="compact"
                >
                    <template #item.no="{ index }">{{ index + 1 }}</template>
                    <template #item.name="{ item }">{{ item.item?.name ?? '-' }}</template>
                    <template #item.unit="{ item }">{{ item.unit?.symbol ?? '-' }}</template>
                    <template #item.supplier="{ item }">{{ item.supplier?.name ?? '-' }}</template>
                    <template #item.qty="{ item }">{{ Number(item.qty).toLocaleString('id-ID') }}</template>
                    <template #item.qty_received="{ item }">
                        <v-chip
                            size="x-small"
                            variant="tonal"
                            :color="item.qty_received >= item.qty ? 'success' : 'warning'"
                        >
                            {{ Number(item.qty_received).toLocaleString('id-ID') }}
                        </v-chip>
                    </template>
                    <template #item.price="{ item }">{{ formatRupiah(item.price) }}</template>
                    <template #item.total="{ item }">{{ formatRupiah(item.total) }}</template>
                </v-data-table>
            </section>

            <!-- Totals -->
            <section v-if="details.length" class="section">
                <header class="section-header">
                    <v-icon :icon="MdiCashMultiple" size="18" />
                    <span>{{ t('total') }}</span>
                </header>
                <v-divider class="mb-2" />
                <div class="totals">
                    <div class="totals__row">
                        <span>{{ t('qty') }}</span>
                        <span>{{ totalQty.toLocaleString('id-ID') }}</span>
                    </div>
                    <div class="totals__row">
                        <span>{{ t('qtyReceived') }}</span>
                        <span>{{ totalReceived.toLocaleString('id-ID') }}</span>
                    </div>
                    <v-divider class="my-2" />
                    <div class="totals__row">
                        <span>Subtotal</span>
                        <span>{{ formatRupiah(subtotal) }}</span>
                    </div>
                    <div class="totals__row">
                        <span>{{ t('shippingCost') }}</span>
                        <span>{{ formatRupiah(items.shipping_cost ?? 0) }}</span>
                    </div>
                    <v-divider class="my-2" />
                    <div class="totals__row totals__row--grand">
                        <span>{{ t('total') }}</span>
                        <span class="text-primary">{{ formatRupiah(grandTotal) }}</span>
                    </div>
                </div>
            </section>

            <!-- Audit -->
            <section class="section section--muted">
                <header class="section-header">
                    <v-icon :icon="MdiAccountCircleOutline" size="18" />
                    <span>{{ t('additionalInfo') }}</span>
                </header>
                <v-divider class="mb-2" />
                <dl class="info-grid info-grid--compact">
                    <div class="info-item">
                        <dt>{{ t('createdAt') }}</dt>
                        <dd>{{ formatDate(items.created_at) }}</dd>
                    </div>
                    <div class="info-item">
                        <dt>{{ t('createdBy') }}</dt>
                        <dd>{{ items.created_by_name || '-' }}</dd>
                    </div>
                    <div class="info-item">
                        <dt>{{ t('updatedAt') }}</dt>
                        <dd>{{ items.updated_at ? formatDate(items.updated_at) : '-' }}</dd>
                    </div>
                    <div class="info-item">
                        <dt>{{ t('updatedBy') }}</dt>
                        <dd>{{ items.updated_by_name || '-' }}</dd>
                    </div>
                </dl>
            </section>
        </div>

        <template #append>
            <v-divider />
            <div class="d-flex justify-end pa-3">
                <v-btn variant="text" @click="handleClose">
                    {{ t('close') }}
                </v-btn>
            </div>
        </template>
    </v-navigation-drawer>
</template>

<style scoped>
.detail-drawer { z-index: 1007 !important; }
.detail-overlay { z-index: 1006 !important; }

.detail-body {
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

/* ── Summary strip ─────────────────────────────────────────────── */
.summary-strip {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    border-radius: 12px;
    background: rgba(var(--v-theme-primary), 0.06);
    border: 1px solid rgba(var(--v-theme-primary), 0.18);
}
.summary-strip__main { display: flex; flex-direction: column; min-width: 0; }
.summary-strip__number {
    font-size: 1rem;
    font-weight: 700;
    color: rgba(var(--v-theme-on-surface), 0.95);
    word-break: break-all;
}
.summary-strip__date {
    font-size: 0.78rem;
    color: rgba(var(--v-theme-on-surface), 0.65);
    margin-top: 2px;
}

/* ── Section ────────────────────────────────────────────────────── */
.section {
    background: rgb(var(--v-theme-surface));
    border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
    border-radius: 12px;
    padding: 14px 14px 12px;
}
.section--muted {
    background: rgba(var(--v-theme-on-surface), 0.02);
}
.section-header {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    font-size: 0.82rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: rgba(var(--v-theme-on-surface), 0.8);
    margin-bottom: 8px;
}

/* ── Info grid ──────────────────────────────────────────────────── */
.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px 16px;
    margin: 0;
}
.info-grid--compact { gap: 6px 16px; }
.info-item { min-width: 0; }
.info-item dt {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: rgba(var(--v-theme-on-surface), 0.55);
    margin-bottom: 2px;
}
.info-item dd {
    margin: 0;
    font-size: 0.88rem;
    color: rgba(var(--v-theme-on-surface), 0.95);
    word-break: break-word;
}

/* ── Detail table ──────────────────────────────────────────────── */
.detail-table :deep(thead th) {
    background: rgba(var(--v-theme-on-surface), 0.04);
    font-size: 0.72rem !important;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.detail-table :deep(tbody tr:hover td) {
    background: rgba(var(--v-theme-primary), 0.04);
}

/* ── Totals ────────────────────────────────────────────────────── */
.totals { display: flex; flex-direction: column; gap: 2px; }
.totals__row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.88rem;
    color: rgba(var(--v-theme-on-surface), 0.8);
}
.totals__row > span:last-child {
    font-weight: 600;
    color: rgba(var(--v-theme-on-surface), 0.95);
}
.totals__row--grand {
    font-size: 1rem;
    font-weight: 700;
}
.totals__row--grand > span:last-child { font-weight: 700; }

/* ── Responsive ────────────────────────────────────────────────── */
@media (max-width: 720px) {
    .info-grid { grid-template-columns: 1fr; }
}
</style>
