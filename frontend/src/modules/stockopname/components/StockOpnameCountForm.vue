<script lang="ts" setup>
import { computed, ref } from 'vue'
import { useTranslate } from '@/composables/useTranslate'

const props = defineProps<{
    modelValue: boolean
    data: StockOpnameList
    saving?: boolean
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'submit', values: StockOpnameCountForm): void
    (e: 'close'): void
}>()

const t = useTranslate()

const isBusy = computed(() => !!props.saving)

const rows = ref(
    (props.data.details ?? []).map((d) => ({
        uid: d.uid ?? '',
        actual_qty: d.actual_qty ?? (null as number | null),
        notes: d.notes ?? (null as string | null),
    })),
)

const onSubmit = () => emit('submit', { details: rows.value })
const onClose  = () => emit('close')
</script>

<template>
    <v-dialog
        :model-value="modelValue"
        max-width="1000"
        :persistent="isBusy"
        scrollable
        @update:model-value="onClose"
    >
        <v-card rounded="lg">

            <!-- Title bar -->
            <v-card-title class="d-flex align-center gap-2 px-6 py-4">
                <v-icon icon="mdi-counter" color="primary" class="me-1" />
                <span class="text-h6 font-weight-semibold">{{ t('enterCountResults') }}</span>
                <v-spacer />
                <v-chip size="small" variant="outlined">{{ data.opname_number }}</v-chip>
                <v-btn icon="mdi-close" variant="text" size="small" :disabled="isBusy" @click="onClose" />
            </v-card-title>

            <v-divider />

            <v-card-text class="px-4 py-4">
                <v-sheet rounded="lg" border class="overflow-hidden">
                    <v-table density="compact" class="count-table">
                        <thead>
                            <tr>
                                <th class="text-center no-col">No.</th>
                                <th>{{ t('item') }}</th>
                                <th class="unit-col">{{ t('unit') }}</th>
                                <th class="wh-col">{{ t('warehouse') }}</th>
                                <th class="text-end qty-col">{{ t('systemQty') }}</th>
                                <th class="text-center qty-col">{{ t('actualQty') }}</th>
                                <th class="note-col">{{ t('notes') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, idx) in rows" :key="row.uid">
                                <td class="text-center text-caption text-medium-emphasis no-col">{{ idx + 1 }}</td>
                                <td class="text-body-2">{{ data.details?.[idx]?.item_name ?? '-' }}</td>
                                <td class="unit-col text-body-2">{{ data.details?.[idx]?.unit_symbol ?? '-' }}</td>
                                <td class="wh-col text-body-2">
                                    {{ data.details?.[idx]?.warehouse_name ?? '-' }}
                                    <span v-if="data.details?.[idx]?.rack_name" class="text-caption text-medium-emphasis">
                                        / {{ data.details?.[idx]?.rack_name }}
                                    </span>
                                </td>
                                <td class="text-end qty-col text-body-2">{{ data.details?.[idx]?.system_qty ?? 0 }}</td>
                                <td class="py-1 qty-col">
                                    <v-number-input
                                        v-model="row.actual_qty"
                                        :min="0"
                                        :step="1"
                                        density="compact"
                                        control-variant="stacked"
                                        variant="outlined"
                                        hide-details="auto"
                                    />
                                </td>
                                <td class="py-1 note-col">
                                    <v-text-field
                                        v-model="row.notes"
                                        :placeholder="t('notes')"
                                        density="compact"
                                        variant="outlined"
                                        hide-details="auto"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </v-table>
                </v-sheet>
            </v-card-text>

            <v-divider />

            <v-card-actions class="px-6 py-4 justify-end gap-2">
                <v-btn variant="tonal" :disabled="isBusy" @click="onClose">{{ t('cancel') }}</v-btn>
                <v-btn
                    color="primary"
                    variant="elevated"
                    :loading="saving"
                    prepend-icon="mdi-content-save-outline"
                    @click="onSubmit"
                >
                    {{ t('save') }}
                </v-btn>
            </v-card-actions>

        </v-card>
    </v-dialog>
</template>

<style scoped>
.count-table :deep(thead th) {
    font-size: 0.7rem !important;
    font-weight: 700 !important;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    white-space: nowrap;
    background-color: rgba(var(--v-theme-primary), 1) !important;
    color: #ffffff !important;
}
.count-table :deep(tbody tr:hover td) { background-color: rgba(var(--v-theme-primary), 0.04); }
.count-table :deep(tbody td) { vertical-align: middle; }

.no-col   { width: 48px;  min-width: 48px; }
.unit-col { width: 100px; min-width: 100px; }
.wh-col   { min-width: 160px; }
.qty-col  { width: 130px; min-width: 130px; }
.note-col { min-width: 180px; }
</style>
