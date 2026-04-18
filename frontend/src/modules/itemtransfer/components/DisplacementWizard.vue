<script lang="ts" setup>
/**
 * DisplacementWizard — dialog untuk mengatur pemindahan item eksisting
 * yang ada di lokasi tujuan transfer utama (chain/cascading transfer).
 *
 * Flow:
 *  1. Parent menyimpan transfer utama → dapat UID
 *  2. Parent buka wizard ini dengan props: parent transfer + daftar occupants + source (= destinasi transfer utama)
 *  3. User pilih destinasi alternatif untuk tiap occupant
 *  4. Wizard emit 'submit' dengan payload child transfer → parent simpan via API
 */
import { computed, ref, watch } from 'vue'
import { useTranslate } from '@/composables/useTranslate'
import dayjs from 'dayjs'
import { api } from '@/api/api'
// Components
import BaseAsyncSelect from '@/components/base/BaseAsyncSelect.vue'
import type { AsyncFetcher } from '@/components/base/BaseAsyncSelect.vue'
import BaseDateInput from '@/components/base/BaseDateInput.vue'
// Icons
import MdiClose from '~icons/mdi/close'
import MdiLinkVariant from '~icons/mdi/link-variant'
import MdiWarehouse from '~icons/mdi/warehouse'
import MdiPackageVariantClosed from '~icons/mdi/package-variant-closed'
import MdiContentSaveOutline from '~icons/mdi/content-save-outline'

// ─── Types ───────────────────────────────────────────────────────────────────
type Option = { title: string; value: string }

type Occupant = {
    item_uid: string
    item_name: string
    unit_uid: string
    unit_name: string
    unit_symbol: string
    qty: number
}

type SourceLocation = {
    warehouse_uid: string
    warehouse_name: string
    rack_uid: string | null
    rack_name: string | null
    tank_uid: string | null
    tank_name: string | null
}

const props = defineProps<{
    modelValue: boolean
    parentTransferUid: string
    parentTransferNumber: string
    /** Source displacement = destination transfer utama. */
    source: SourceLocation
    occupants: Occupant[]
    saving?: boolean
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'submit', payload: ItemTransferForm): void
    (e: 'close'): void
}>()

const t = useTranslate()

// ─── State ───────────────────────────────────────────────────────────────────
const transferDate = ref<string>(dayjs().format('YYYY-MM-DD'))
const toWarehouseUid = ref<string | null>(null)
const toRackUid = ref<string | null>(null)
const toTankUid = ref<string | null>(null)
const notes = ref<string>('')
const errorMsg = ref<string>('')

// Cache untuk label di review
const selectedToWarehouse = ref<Option | null>(null)
const selectedToRack = ref<Option | null>(null)
const selectedToTank = ref<Option | null>(null)

// Reset saat dialog dibuka
watch(() => props.modelValue, (open) => {
    if (open) {
        transferDate.value = dayjs().format('YYYY-MM-DD')
        toWarehouseUid.value = null
        toRackUid.value = null
        toTankUid.value = null
        notes.value = ''
        errorMsg.value = ''
        selectedToWarehouse.value = null
        selectedToRack.value = null
        selectedToTank.value = null
    }
})

// Cascade reset + mutual exclusive
watch(toWarehouseUid, (v, old) => {
    if (old !== undefined && v !== old) {
        toRackUid.value = null
        toTankUid.value = null
    }
})
watch(toRackUid, (v) => { if (v) toTankUid.value = null })
watch(toTankUid, (v) => { if (v) toRackUid.value = null })

// ─── Fetcher factory ────────────────────────────────────────────────────────
const makeFetcher = (
    url: string | (() => string | null),
    mapper: (item: any) => Option = (i) => ({ title: i.name, value: i.uid })
): AsyncFetcher<Option> => {
    return async ({ search, page, perPage, signal }) => {
        const endpoint = typeof url === 'function' ? url() : url
        if (!endpoint) return { items: [], hasMore: false }

        const res = await api.get(endpoint, {
            params: { page, per_page: perPage, search, sort_by: 'name', sort_dir: 'asc' },
            signal,
        })
        const payload = res.data ?? {}
        const meta = payload.meta ?? payload.pagination ?? {}
        return {
            items: (payload.data ?? []).map(mapper),
            hasMore: Number(meta.current_page ?? page) < Number(meta.last_page ?? 1),
        }
    }
}

const fetchWarehouse = makeFetcher('/warehouse')
const fetchToRack = makeFetcher(() => toWarehouseUid.value ? `/rack/${toWarehouseUid.value}` : null)
const fetchToTank = makeFetcher(() => toWarehouseUid.value ? `/tank/${toWarehouseUid.value}` : null)

// ─── Helpers ─────────────────────────────────────────────────────────────────
const sourceLabel = computed(() => {
    const parts = [props.source.warehouse_name]
    if (props.source.rack_name) parts.push(`Rak: ${props.source.rack_name}`)
    if (props.source.tank_name) parts.push(`Tangki: ${props.source.tank_name}`)
    return parts.join(' • ')
})

const destLabel = computed(() => {
    if (!selectedToWarehouse.value) return '-'
    const parts = [selectedToWarehouse.value.title]
    if (selectedToRack.value && toRackUid.value) parts.push(`Rak: ${selectedToRack.value.title}`)
    if (selectedToTank.value && toTankUid.value) parts.push(`Tangki: ${selectedToTank.value.title}`)
    return parts.join(' • ')
})

const sameLocationError = computed(() => {
    // Tidak boleh tujuan = sumber (sumber = destinasi parent transfer).
    if (!toWarehouseUid.value) return false
    if (toWarehouseUid.value !== props.source.warehouse_uid) return false
    const rackSame = (toRackUid.value ?? null) === (props.source.rack_uid ?? null)
    const tankSame = (toTankUid.value ?? null) === (props.source.tank_uid ?? null)
    return rackSame && tankSame
})

// ─── Submit ──────────────────────────────────────────────────────────────────
const handleSubmit = () => {
    errorMsg.value = ''

    if (!toWarehouseUid.value) {
        errorMsg.value = t('fieldRequired', { field: t('toWarehouse') })
        return
    }
    if (sameLocationError.value) {
        errorMsg.value = t('sourceDestSameError')
        return
    }

    const payload: ItemTransferForm = {
        transfer_date: transferDate.value,
        from_warehouse_uid: props.source.warehouse_uid,
        from_rack_uid: props.source.rack_uid,
        from_tank_uid: props.source.tank_uid,
        to_warehouse_uid: toWarehouseUid.value,
        to_rack_uid: toRackUid.value,
        to_tank_uid: toTankUid.value,
        notes: notes.value || null,
        parent_transfer_uid: props.parentTransferUid,
        details: props.occupants.map((o) => ({
            item_uid: o.item_uid,
            unit_uid: o.unit_uid,
            qty: o.qty,
            description: null,
        })),
    }

    emit('submit', payload)
}

const handleClose = () => {
    emit('update:modelValue', false)
    emit('close')
}
</script>

<template>
    <v-dialog
        :model-value="modelValue"
        max-width="900"
        persistent
        scrollable
    >
        <v-card class="rounded-lg d-flex flex-column" :loading="saving" style="max-height: 90vh;">
            <!-- Header -->
            <v-card-title class="d-flex align-center ga-2 px-4 px-sm-6 py-3 py-sm-4 bg-deep-orange text-white">
                <v-icon :icon="MdiLinkVariant" class="shrink-0" />
                <div class="d-flex flex-column overflow-hidden grow">
                    <span class="text-subtitle-1 text-sm-h6 font-weight-semibold">
                        {{ t('arrangeDisplacementTransfer') }}
                    </span>
                    <span class="text-caption opacity-80">
                        {{ t('linkedTo') }}: {{ parentTransferNumber }}
                    </span>
                </div>
                <v-btn
                    :icon="MdiClose"
                    variant="text"
                    size="small"
                    color="white"
                    class="shrink-0 ms-auto"
                    :disabled="saving"
                    @click="handleClose"
                />
            </v-card-title>

            <v-card-text class="pa-4 pa-sm-6 grow overflow-y-auto">
                <v-alert
                    type="info"
                    variant="tonal"
                    density="compact"
                    class="mb-4"
                    :text="t('displacementWizardHint')"
                />

                <!-- Items yang akan dipindah -->
                <div class="section-header mb-3">
                    <v-icon :icon="MdiPackageVariantClosed" size="18" color="deep-orange" />
                    <span class="text-subtitle-2 font-weight-bold text-deep-orange">
                        {{ t('itemsToDisplace') }}
                    </span>
                    <v-chip size="x-small" color="deep-orange" variant="tonal" class="ms-2">
                        {{ occupants.length }}
                    </v-chip>
                </div>
                <v-sheet rounded="lg" border class="overflow-hidden mb-4">
                    <v-table density="compact">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 40px;">No.</th>
                                <th>{{ t('item') }}</th>
                                <th class="text-end" style="width: 160px;">{{ t('qty') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(occ, idx) in occupants" :key="occ.item_uid">
                                <td class="text-center text-caption">{{ idx + 1 }}</td>
                                <td class="text-body-2 font-weight-medium">{{ occ.item_name }}</td>
                                <td class="text-end font-weight-medium">
                                    {{ occ.qty }}
                                    <span class="text-medium-emphasis">{{ occ.unit_symbol }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </v-table>
                </v-sheet>

                <!-- Source (readonly — = destinasi parent) -->
                <div class="section-header mb-3">
                    <v-icon :icon="MdiWarehouse" size="18" color="error" />
                    <span class="text-subtitle-2 font-weight-bold text-error">{{ t('source') }}</span>
                </div>
                <v-sheet rounded="lg" border class="pa-3 mb-4">
                    <div class="text-body-2 font-weight-medium">{{ sourceLabel }}</div>
                    <div class="text-caption text-medium-emphasis mt-1">
                        {{ t('sourceSameAsParentDest') }}
                    </div>
                </v-sheet>

                <!-- Destination -->
                <div class="section-header mb-3">
                    <v-icon :icon="MdiWarehouse" size="18" color="success" />
                    <span class="text-subtitle-2 font-weight-bold text-success">{{ t('destination') }}</span>
                </div>
                <v-row dense>
                    <v-col cols="12" md="6">
                        <base-date-input v-model="transferDate" :label="t('transferDate')" />
                    </v-col>
                    <v-col cols="12" md="6">
                        <base-async-select
                            v-model="toWarehouseUid"
                            :fetcher="fetchWarehouse"
                            :label="t('toWarehouse')"
                            @select="(item) => selectedToWarehouse = item as Option | null"
                        />
                    </v-col>
                    <v-col cols="12" md="6">
                        <base-async-select
                            :key="`wiz-to-rack-${toWarehouseUid}`"
                            v-model="toRackUid"
                            :fetcher="fetchToRack"
                            :label="`${t('rack')} (${t('optional')})`"
                            :disabled="!toWarehouseUid || !!toTankUid"
                            @select="(item) => selectedToRack = item as Option | null"
                        />
                    </v-col>
                    <v-col cols="12" md="6">
                        <base-async-select
                            :key="`wiz-to-tank-${toWarehouseUid}`"
                            v-model="toTankUid"
                            :fetcher="fetchToTank"
                            :label="`${t('tank')} (${t('optional')})`"
                            :disabled="!toWarehouseUid || !!toRackUid"
                            @select="(item) => selectedToTank = item as Option | null"
                        />
                    </v-col>
                    <v-col cols="12">
                        <v-textarea
                            v-model="notes"
                            :label="`${t('notes')} (${t('optional')})`"
                            variant="outlined"
                            density="comfortable"
                            rows="2"
                            auto-grow
                            hide-details="auto"
                        />
                    </v-col>
                </v-row>

                <!-- Preview destinasi terpilih -->
                <v-sheet v-if="toWarehouseUid" rounded="lg" border class="pa-3 mt-4">
                    <div class="d-flex align-center ga-2 mb-1">
                        <v-icon :icon="MdiWarehouse" size="16" color="success" />
                        <span class="text-overline">{{ t('selectedDestination') }}</span>
                    </div>
                    <div class="text-body-2 font-weight-medium">{{ destLabel }}</div>
                </v-sheet>

                <v-alert
                    v-if="errorMsg"
                    type="error"
                    variant="tonal"
                    density="compact"
                    class="mt-4"
                    :text="errorMsg"
                />
            </v-card-text>

            <v-divider />

            <v-card-actions class="pa-4 justify-end ga-2">
                <v-btn variant="tonal" :disabled="saving" @click="handleClose">
                    {{ t('cancel') }}
                </v-btn>
                <v-btn
                    color="deep-orange"
                    variant="elevated"
                    :loading="saving"
                    :disabled="!toWarehouseUid"
                    :prepend-icon="MdiContentSaveOutline"
                    @click="handleSubmit"
                >
                    {{ t('saveDisplacementTransfer') }}
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
</style>
