<script lang="ts" setup>
import { useTranslate } from '@/composables/useTranslate'
import { formatDate } from '@/utils/date'

const model = defineModel<boolean>()

const props = defineProps<{
    item: StockOpnameList
}>()

const emit = defineEmits<{
    (e: 'close'): void
}>()

const t = useTranslate()

const handleClose = () => emit('close')
const handlePrint = () => {
    const style = document.createElement('style')
    style.id = 'opname-print-override'
    style.textContent = `
        @media print {
            body > *:not(.v-overlay-container) { display: none !important; }
            .v-overlay-container .v-overlay:not(.v-overlay--active) { display: none !important; }
            .v-overlay__scrim { display: none !important; }
            .no-print { display: none !important; }
        }
    `
    document.head.appendChild(style)
    window.print()
    document.getElementById('opname-print-override')?.remove()
}
</script>

<template>
    <v-dialog v-model="model" max-width="900" scrollable @update:model-value="handleClose">
        <v-card rounded="lg">

            <!-- Actions bar (hidden on print) -->
            <v-card-title class="d-flex align-center gap-2 px-6 py-4 no-print">
                <!-- <v-icon icon="mdi-printer-outline" color="primary" class="me-1" /> -->
                <span class="text-h6 font-weight-semibold">{{ t('printOpname') }}</span>
                <v-spacer />
                <v-btn color="primary" variant="elevated" @click="handlePrint">
                    {{ t('print') }}
                </v-btn>
                <!-- <v-btn icon="mdi-close" variant="text" size="small" @click="handleClose" /> -->
            </v-card-title>

            <v-divider class="no-print" />

            <!-- Printable content -->
            <v-card-text class="print-area px-6 py-5">

                <!-- Header -->
                <div class="text-center mb-4">
                    <h2 class="text-h6 font-weight-bold text-uppercase">{{ t('stockOpname') }}</h2>
                    <div class="text-body-2 text-medium-emphasis">{{ item.opname_number }}</div>
                </div>

                <v-row dense class="mb-4">
                    <v-col cols="6">
                        <table class="info-table">
                            <tr>
                                <td class="text-caption text-medium-emphasis pe-4">{{ t('opnameNumber') }}</td>
                                <td class="text-body-2 font-weight-medium">: {{ item.opname_number }}</td>
                            </tr>
                            <tr>
                                <td class="text-caption text-medium-emphasis pe-4">{{ t('opnameDate') }}</td>
                                <td class="text-body-2">: {{ formatDate(item.opname_date as string) }}</td>
                            </tr>
                            <tr>
                                <td class="text-caption text-medium-emphasis pe-4">{{ t('status') }}</td>
                                <td class="text-body-2">: {{ item.status }}</td>
                            </tr>
                        </table>
                    </v-col>
                    <v-col cols="6">
                        <table class="info-table">
                            <tr>
                                <td class="text-caption text-medium-emphasis pe-4">{{ t('createdBy') }}</td>
                                <td class="text-body-2">: {{ item.created_by_name }}</td>
                            </tr>
                            <tr>
                                <td class="text-caption text-medium-emphasis pe-4">{{ t('notes') }}</td>
                                <td class="text-body-2">: {{ item.notes || '-' }}</td>
                            </tr>
                        </table>
                    </v-col>
                </v-row>

                <!-- Detail Table -->
                <table class="print-table w-100">
                    <thead>
                        <tr>
                            <th class="text-center" style="width:40px">No.</th>
                            <th>{{ t('item') }}</th>
                            <th style="width:80px">{{ t('unit') }}</th>
                            <th>{{ t('warehouse') }}</th>
                            <th class="text-end" style="width:90px">{{ t('systemQty') }}</th>
                            <th class="text-center" style="width:100px">{{ t('actualQty') }}</th>
                            <th class="text-end" style="width:90px">{{ t('differenceQty') }}</th>
                            <th>{{ t('notes') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(d, idx) in item.details ?? []" :key="idx">
                            <td class="text-center">{{ idx + 1 }}</td>
                            <td>{{ d.item_name }}</td>
                            <td>{{ d.unit_symbol }}</td>
                            <td>{{ d.warehouse_name }}<span v-if="d.rack_name"> / {{ d.rack_name }}</span></td>
                            <td class="text-end">{{ d.system_qty }}</td>
                            <td class="text-center">{{ d.actual_qty ?? '' }}</td>
                            <td class="text-end">{{ d.difference_qty ?? '' }}</td>
                            <td>{{ d.notes || '' }}</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Signatures -->
                <div class="d-flex justify-space-around mt-10">
                    <div class="text-center">
                        <div class="text-caption text-medium-emphasis mb-8">{{ t('createdBy') }}</div>
                        <div style="border-top: 1px solid #333; width: 160px; margin: 0 auto;"></div>
                        <div class="text-caption mt-1">{{ item.created_by_name }}</div>
                    </div>
                    <div class="text-center">
                        <div class="text-caption text-medium-emphasis mb-8">Verifikasi</div>
                        <div style="border-top: 1px solid #333; width: 160px; margin: 0 auto;"></div>
                        <div class="text-caption mt-1">&nbsp;</div>
                    </div>
                </div>

            </v-card-text>

        </v-card>
    </v-dialog>
</template>

<style scoped>
.info-table { border-collapse: collapse; }
.info-table td { padding: 1px 4px; }

.print-table { border-collapse: collapse; font-size: 0.8rem; }
.print-table th,
.print-table td { border: 1px solid #ccc; padding: 4px 8px; }
.print-table th { background-color: #f5f5f5; font-weight: 700; text-transform: uppercase; font-size: 0.7rem; }

.w-100 { width: 100%; }

@media print {
    .no-print { display: none !important; }

    .print-area {
        padding: 16px !important;
    }

    .print-table th { background-color: #eee !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
