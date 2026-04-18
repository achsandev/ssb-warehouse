<script lang="ts" setup>
import { computed } from 'vue'
import { useTranslate } from '@/composables/useTranslate'
import { formatDate } from '@/utils/date'
// Icons
import MdiClose from '~icons/mdi/close'
import MdiPrinter from '~icons/mdi/printer'
import MdiSwapHorizontalBold from '~icons/mdi/swap-horizontal-bold'
import MdiWarehouse from '~icons/mdi/warehouse'
import MdiPackageVariantClosed from '~icons/mdi/package-variant-closed'
import MdiClockOutline from '~icons/mdi/clock-outline'
import MdiLinkVariant from '~icons/mdi/link-variant'
import MdiInformationOutline from '~icons/mdi/information-outline'
import MdiPlusCircleOutline from '~icons/mdi/plus-circle-outline'
import MdiPencilOutline from '~icons/mdi/pencil-outline'
import MdiCheckCircleOutline from '~icons/mdi/check-circle-outline'
import MdiCloseCircleOutline from '~icons/mdi/close-circle-outline'
import MdiCancel from '~icons/mdi/cancel'
import MdiSwapVerticalBold from '~icons/mdi/swap-vertical-bold'
import MdiHelpCircleOutline from '~icons/mdi/help-circle-outline'

const model = defineModel<boolean>()

const props = defineProps<{
    item: ItemTransferList | null
}>()

const emit = defineEmits<{
    (e: 'close'): void
}>()

const t = useTranslate()

// ─── Status color ────────────────────────────────────────────────────────────
const statusInfo = computed(() => {
    const status = (props.item?.status ?? '').toLowerCase()
    switch (status) {
        case 'waiting approval':
            return { color: 'warning', icon: MdiClockOutline, label: status }
        case 'approved':
            return { color: 'success', icon: MdiCheckCircleOutline, label: status }
        case 'rejected':
            return { color: 'error', icon: MdiCloseCircleOutline, label: status }
        case 'cancelled':
            return { color: 'grey', icon: MdiCancel, label: status }
        case 'pending displacement':
            return { color: 'deep-orange', icon: MdiLinkVariant, label: status }
        default:
            return { color: 'grey', icon: MdiHelpCircleOutline, label: status }
    }
})

// ─── Location formatter ──────────────────────────────────────────────────────
const formatLocation = (
    warehouse: { name: string } | null,
    rack: { name: string } | null,
    tank: { name: string } | null,
) => {
    if (!warehouse) return '-'
    const parts = [warehouse.name]
    if (rack) parts.push(`Rak: ${rack.name}`)
    if (tank) parts.push(`Tangki: ${tank.name}`)
    return parts.join(' • ')
}

const sourceLabel = computed(() =>
    props.item ? formatLocation(props.item.from_warehouse, props.item.from_rack, props.item.from_tank) : '-'
)
const destLabel = computed(() =>
    props.item ? formatLocation(props.item.to_warehouse, props.item.to_rack, props.item.to_tank) : '-'
)

// ─── Audit timeline icon mapping ─────────────────────────────────────────────
const actionInfo = (action: string) => {
    switch (action) {
        case 'created':
            return { color: 'primary', icon: MdiPlusCircleOutline, label: 'Dibuat' }
        case 'revised':
            return { color: 'orange', icon: MdiPencilOutline, label: 'Direvisi' }
        case 'approved':
            return { color: 'success', icon: MdiCheckCircleOutline, label: 'Disetujui' }
        case 'rejected':
            return { color: 'error', icon: MdiCloseCircleOutline, label: 'Ditolak' }
        case 'cancelled':
            return { color: 'grey', icon: MdiCancel, label: 'Dibatalkan' }
        case 'displacement_cleared':
            return { color: 'deep-orange', icon: MdiLinkVariant, label: 'Displacement Selesai' }
        case 'stock_moved':
            return { color: 'info', icon: MdiSwapVerticalBold, label: 'Stok Dipindahkan' }
        default:
            return { color: 'grey', icon: MdiInformationOutline, label: action }
    }
}

const handleClose = () => {
    model.value = false
    emit('close')
}

const handlePrint = () => {
    // Print dialog hanya cetak area dengan class .printable-area (lihat CSS)
    window.print()
}
</script>

<template>
    <v-dialog v-model="model" max-width="900" scrollable @update:model-value="(v) => !v && handleClose()">
        <v-card v-if="item" class="rounded-lg d-flex flex-column" style="max-height: 90vh;">
            <!-- Header -->
            <v-card-title class="d-flex align-center ga-2 px-4 px-sm-6 py-3 py-sm-4 bg-primary text-white">
                <v-icon :icon="MdiSwapHorizontalBold" class="shrink-0" />
                <div class="d-flex flex-column overflow-hidden grow">
                    <span class="text-subtitle-1 text-sm-h6 font-weight-semibold text-truncate">
                        {{ item.transfer_number }}
                    </span>
                    <div class="d-flex align-center ga-2 flex-wrap">
                        <span class="text-caption opacity-80">{{ formatDate(item.transfer_date as string) }}</span>
                        <v-chip
                            :color="statusInfo.color"
                            variant="elevated"
                            size="x-small"
                            class="text-uppercase font-weight-bold"
                        >
                            <v-icon :icon="statusInfo.icon" start size="12" />
                            {{ statusInfo.label }}
                        </v-chip>
                    </div>
                </div>
                <v-btn
                    :icon="MdiClose"
                    variant="text"
                    size="small"
                    color="white"
                    class="shrink-0 ms-auto"
                    @click="handleClose"
                />
            </v-card-title>

            <v-card-text class="pa-4 pa-sm-6 grow overflow-y-auto">
                <!-- Chain transfer alert -->
                <v-alert
                    v-if="item.has_pending_displacement"
                    type="warning"
                    variant="tonal"
                    density="compact"
                    class="mb-4"
                    :prepend-icon="MdiLinkVariant"
                >
                    Transfer ini memiliki displacement transfer yang menunggu persetujuan.
                    Tidak dapat di-approve sampai transfer terkait selesai.
                </v-alert>

                <v-alert
                    v-if="item.parent_transfer"
                    type="info"
                    variant="tonal"
                    density="compact"
                    class="mb-4"
                    :prepend-icon="MdiLinkVariant"
                >
                    Ini adalah <strong>displacement transfer</strong> dari
                    <strong>{{ item.parent_transfer.transfer_number }}</strong> ({{ item.parent_transfer.status }}).
                </v-alert>

                <!-- Reject notes -->
                <v-alert
                    v-if="item.reject_notes"
                    type="error"
                    variant="tonal"
                    density="compact"
                    class="mb-4"
                    :prepend-icon="MdiCloseCircleOutline"
                >
                    <strong>{{ t('rejectReason') }}:</strong> {{ item.reject_notes }}
                </v-alert>

                <!-- Source & Destination cards -->
                <v-row dense class="mb-4">
                    <v-col cols="12" md="6">
                        <v-sheet rounded="lg" border class="pa-4 h-100">
                            <div class="d-flex align-center ga-2 mb-2">
                                <v-icon :icon="MdiWarehouse" color="error" size="20" />
                                <span class="text-overline">{{ t('source') }}</span>
                            </div>
                            <div class="text-body-2 font-weight-medium">{{ sourceLabel }}</div>
                        </v-sheet>
                    </v-col>
                    <v-col cols="12" md="6">
                        <v-sheet rounded="lg" border class="pa-4 h-100">
                            <div class="d-flex align-center ga-2 mb-2">
                                <v-icon :icon="MdiWarehouse" color="success" size="20" />
                                <span class="text-overline">{{ t('destination') }}</span>
                            </div>
                            <div class="text-body-2 font-weight-medium">{{ destLabel }}</div>
                        </v-sheet>
                    </v-col>
                </v-row>

                <!-- Notes -->
                <v-sheet v-if="item.notes" rounded="lg" border class="pa-3 mb-4">
                    <div class="text-overline mb-1">{{ t('notes') }}</div>
                    <div class="text-body-2">{{ item.notes }}</div>
                </v-sheet>

                <!-- Detail items -->
                <div class="section-header mb-3">
                    <v-icon :icon="MdiPackageVariantClosed" size="18" color="primary" />
                    <span class="text-subtitle-2 font-weight-bold text-primary">{{ t('details') }}</span>
                    <v-chip size="x-small" color="primary" variant="tonal" class="ms-1">
                        {{ item.details?.length ?? 0 }}
                    </v-chip>
                </div>
                <v-sheet rounded="lg" border class="overflow-hidden mb-4">
                    <v-table density="compact">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 40px;">No.</th>
                                <th>{{ t('item') }}</th>
                                <th class="text-end" style="width: 140px;">{{ t('qty') }}</th>
                                <th>{{ t('description') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(d, idx) in item.details ?? []" :key="d.uid">
                                <td class="text-center text-caption">{{ idx + 1 }}</td>
                                <td class="text-body-2 font-weight-medium">{{ d.item?.name || '-' }}</td>
                                <td class="text-end font-weight-medium">
                                    {{ d.qty }}
                                    <span class="text-medium-emphasis">{{ d.unit?.symbol || '' }}</span>
                                </td>
                                <td class="text-body-2 text-medium-emphasis">{{ d.description || '-' }}</td>
                            </tr>
                        </tbody>
                    </v-table>
                </v-sheet>

                <!-- Chain visualization (child transfers) -->
                <template v-if="item.child_transfers?.length">
                    <div class="section-header mb-3">
                        <v-icon :icon="MdiLinkVariant" size="18" color="deep-orange" />
                        <span class="text-subtitle-2 font-weight-bold text-deep-orange">
                            Displacement Transfers ({{ item.child_transfers.length }})
                        </span>
                    </div>
                    <v-sheet rounded="lg" border class="overflow-hidden mb-4">
                        <v-table density="compact">
                            <thead>
                                <tr>
                                    <th>No. Transfer</th>
                                    <th>{{ t('status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="child in item.child_transfers" :key="child.uid">
                                    <td class="text-body-2 font-weight-medium">{{ child.transfer_number }}</td>
                                    <td>
                                        <v-chip size="x-small" variant="tonal">{{ child.status }}</v-chip>
                                    </td>
                                </tr>
                            </tbody>
                        </v-table>
                    </v-sheet>
                </template>

                <!-- Audit timeline -->
                <div class="section-header mb-3">
                    <v-icon :icon="MdiClockOutline" size="18" color="primary" />
                    <span class="text-subtitle-2 font-weight-bold text-primary">{{ t('auditTrail') }}</span>
                </div>
                <v-sheet
                    v-if="!item.logs?.length"
                    rounded="lg"
                    border
                    class="pa-4 text-center text-medium-emphasis"
                >
                    <v-icon :icon="MdiInformationOutline" size="24" class="mb-1" />
                    <div class="text-body-2">Belum ada catatan aktivitas.</div>
                </v-sheet>
                <v-timeline
                    v-else
                    side="end"
                    align="start"
                    density="compact"
                    truncate-line="both"
                    class="audit-timeline"
                >
                    <v-timeline-item
                        v-for="log in item.logs"
                        :key="log.uid"
                        :dot-color="actionInfo(log.action).color"
                        size="small"
                    >
                        <template #icon>
                            <v-icon :icon="actionInfo(log.action).icon" size="14" color="white" />
                        </template>
                        <div class="d-flex flex-column">
                            <div class="d-flex align-center ga-2 flex-wrap">
                                <span class="text-body-2 font-weight-bold">
                                    {{ actionInfo(log.action).label }}
                                </span>
                                <v-chip
                                    v-if="log.from_status && log.to_status && log.from_status !== log.to_status"
                                    size="x-small"
                                    variant="outlined"
                                >
                                    {{ log.from_status }} → {{ log.to_status }}
                                </v-chip>
                            </div>
                            <div v-if="log.notes" class="text-caption text-medium-emphasis mt-1" style="white-space: pre-wrap;">
                                {{ log.notes }}
                            </div>
                            <div class="text-caption text-medium-emphasis mt-1">
                                {{ log.actor_name || '—' }}
                                <span v-if="log.actor_role"> ({{ log.actor_role }})</span>
                                • {{ formatDate(log.created_at) }}
                            </div>
                        </div>
                    </v-timeline-item>
                </v-timeline>

                <v-divider class="my-4" />

                <!-- Audit info ringkas -->
                <v-row dense>
                    <v-col cols="6" sm="3">
                        <div class="text-overline">{{ t('createdBy') }}</div>
                        <div class="text-body-2 font-weight-medium">{{ item.created_by_name || '-' }}</div>
                    </v-col>
                    <v-col cols="6" sm="3">
                        <div class="text-overline">{{ t('createdAt') }}</div>
                        <div class="text-body-2">{{ formatDate(item.created_at as string) }}</div>
                    </v-col>
                    <v-col cols="6" sm="3">
                        <div class="text-overline">Approved By</div>
                        <div class="text-body-2 font-weight-medium">{{ item.approved_by_name || '-' }}</div>
                    </v-col>
                    <v-col cols="6" sm="3">
                        <div class="text-overline">Approved At</div>
                        <div class="text-body-2">{{ item.approved_at ? formatDate(item.approved_at) : '-' }}</div>
                    </v-col>
                </v-row>
            </v-card-text>

            <v-divider />
            <v-card-actions class="pa-4 justify-end ga-2 no-print">
                <v-btn variant="tonal" @click="handleClose">{{ t('close') }}</v-btn>
                <v-btn
                    color="primary"
                    variant="elevated"
                    :prepend-icon="MdiPrinter"
                    @click="handlePrint"
                >
                    {{ t('printTransfer') }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<style scoped>
.section-header {
    display: flex;
    align-items: center;
    gap: 8px;
}

.audit-timeline :deep(.v-timeline-item__body) {
    padding-bottom: 16px;
}
</style>

<!-- Global print CSS (non-scoped) — berlaku saat user klik "Cetak Surat" -->
<style>
@media print {
    /* Sembunyikan semua kecuali dialog detail yang aktif */
    body > *:not(.v-overlay-container) {
        display: none !important;
    }

    .v-overlay-container .v-overlay:not(:last-child),
    .v-overlay__scrim,
    .no-print {
        display: none !important;
    }

    /* Fullscreen print: hilangkan dialog styling */
    .v-overlay-container .v-dialog {
        position: static !important;
        max-width: 100% !important;
        max-height: none !important;
    }

    .v-overlay-container .v-dialog .v-card {
        max-height: none !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        overflow: visible !important;
    }

    .v-overlay-container .v-dialog .v-card-text {
        overflow: visible !important;
        max-height: none !important;
    }

    /* Header tetap primary, tapi print-friendly */
    .v-overlay-container .v-card-title {
        background: #1565c0 !important;
        color: white !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* Paksa warna background agar tampilan tetap rapi */
    .bg-primary, .bg-deep-orange {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* Page settings */
    @page {
        size: A4;
        margin: 1cm;
    }

    html, body {
        background: white !important;
    }
}
</style>
