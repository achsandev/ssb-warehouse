<script lang="ts" setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useField, useForm } from 'vee-validate'
// Composables & validations
import { useTranslate } from '@/composables/useTranslate'
import { itemsSchema } from '@/validations'
// API
import { api } from '@/api/api'
// Stores — hanya unitsStore yang masih dipakai (untuk quick-create)
import { useItemUnitsStore } from '@/stores/item_units'
import { useMessageStore } from '@/stores/message'
import { useThemeStore } from '@/stores/theme'
// Components
import BaseAsyncSelect from '@/components/base/BaseAsyncSelect.vue'
import type { AsyncFetcher } from '@/components/base/BaseAsyncSelect.vue'
import BaseAutocompleteCreatable from '@/components/base/BaseAutocompleteCreatable.vue'
import BaseCurrencyInput from '@/components/base/BaseCurrencyInput.vue'
import BaseFileInput from '@/components/base/BaseFileInput.vue'
import BaseDateInput from '@/components/base/BaseDateInput.vue'
// Icons (unplugin-icons / Iconify)
import MdiClose from '~icons/mdi/close'
import MdiWarehouse from '~icons/mdi/warehouse'
import MdiShape from '~icons/mdi/shape'
import MdiIdentifier from '~icons/mdi/identifier'
import MdiRuler from '~icons/mdi/ruler'
import MdiCashMultiple from '~icons/mdi/cash-multiple'
import MdiTextLong from '~icons/mdi/text-long'
import MdiCounter from '~icons/mdi/counter'
import MdiContentSaveOutline from '~icons/mdi/content-save-outline'
import MdiArrowRight from '~icons/mdi/arrow-right'
import MdiArrowLeft from '~icons/mdi/arrow-left'

// ─── Types ───────────────────────────────────────────────────────────────────
type UidNamed = { uid: string; name: string }

type ItemStockInitial = {
    uid?: string | null
    warehouse_uid?: string | null
    rack_uid?: string | null
    tank_uid?: string | null
    qty?: number | null
    /**
     * Object snapshot opsional — dipakai untuk menampilkan nama pada AsyncSelect
     * saat edit mode (value terpilih belum tentu ada di halaman 1 hasil fetch).
     */
    warehouse?: UidNamed | null
    rack?: UidNamed | null
    tank?: UidNamed | null
} | null

type Option = { title: string; value: string }

// ─── Props / Emits ───────────────────────────────────────────────────────────
const props = defineProps<{
    modelValue: boolean
    title: string
    data: ItemsList
    stockInitial?: ItemStockInitial
    saving?: boolean
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'submit', values: ItemsForm): void
    (e: 'close'): void
}>()

const t = useTranslate()

// ─── Stores ──────────────────────────────────────────────────────────────────
const message = useMessageStore()
const unitsStore = useItemUnitsStore()
const themeStore = useThemeStore()

// Warna icon & teks pada hero header mengikuti tema aktif:
// light → hitam, dark → putih.
const heroColor = computed(() => themeStore.resolvedTheme === 'dark' ? '#ffffff' : '#000000')

// ─── State ───────────────────────────────────────────────────────────────────
const currentStep = ref<1 | 2>(1)
const loadingForm = ref(false)

const INFO_FIELDS = [
    'name', 'brand_uid', 'category_uid', 'unit_uid',
    'movement_category_uid', 'valuation_method_uid',
    'material_group_uid', 'sub_material_group_uid',
    'supplier_uid', 'request_types_uid', 'unit_types_uid',
    'min_qty', 'part_number', 'interchange_part',
    'image', 'price', 'exp_date', 'additional_info',
] as const

const STOCK_FIELDS = [
    'stock_warehouse_uid', 'stock_rack_uid',
    'stock_tank_uid', 'stock_qty',
] as const

const unitRef = ref<InstanceType<typeof BaseAutocompleteCreatable>>()
const newUnitName = ref('')
const newUnitSymbol = ref('')
const unitItem = ref<Option[]>([])

// ─── Form ────────────────────────────────────────────────────────────────────
const { handleSubmit, setValues, resetForm, errors, validate } = useForm<ItemsForm>({
    validationSchema: itemsSchema(t),
})

setValues({
    name: props.data.name,
    brand_uid: props.data.brand?.uid ?? null,
    category_uid: props.data.category?.uid ?? null,
    unit_uid: props.data.unit?.uid ?? null,
    movement_category_uid: props.data.movement_category?.uid ?? null,
    valuation_method_uid: props.data.valuation_method?.uid ?? null,
    material_group_uid: props.data.material_group?.uid ?? null,
    sub_material_group_uid: props.data.sub_material_group?.uid ?? null,
    supplier_uid: props.data.supplier?.uid ?? null,
    min_qty: props.data.min_qty ?? 0,
    part_number: props.data.part_number,
    interchange_part: props.data.interchange_part ?? '',
    image: props.data.image ?? undefined,
    price: props.data.price ?? '',
    exp_date: props.data.exp_date,
    additional_info: props.data.additional_info ?? '',
    request_types_uid: props.data.request_types?.map(r => r.uid) ?? [],
    unit_types_uid: props.data.unit_types?.map(u => u.uid) ?? [],
    stock_warehouse_uid: props.stockInitial?.warehouse_uid ?? null,
    stock_rack_uid: props.stockInitial?.rack_uid ?? null,
    stock_tank_uid: props.stockInitial?.tank_uid ?? null,
    stock_qty: props.stockInitial?.qty ?? null,
})

const { value: name } = useField<string>('name')
const { value: brand_uid } = useField<string | null>('brand_uid')
const { value: category_uid } = useField<string | null>('category_uid')
const { value: unit_uid } = useField<string | null>('unit_uid')
const { value: movement_category_uid } = useField<string | null>('movement_category_uid')
const { value: valuation_method_uid } = useField<string | null>('valuation_method_uid')
const { value: material_group_uid } = useField<string | null>('material_group_uid')
const { value: sub_material_group_uid } = useField<string | null>('sub_material_group_uid')
const { value: supplier_uid } = useField<string | null>('supplier_uid')
const { value: min_qty } = useField<number>('min_qty')
const { value: part_number } = useField<string>('part_number')
const { value: interchange_part } = useField<string>('interchange_part')
const { value: image } = useField<string | File | File[] | undefined>('image')
const { value: price } = useField<string>('price')
const { value: exp_date } = useField<string | Date | undefined>('exp_date')
const { value: additional_info } = useField<string>('additional_info')
const { value: request_types_uid } = useField<string[]>('request_types_uid')
const { value: unit_types_uid } = useField<string[]>('unit_types_uid')
const { value: stock_warehouse_uid } = useField<string | null>('stock_warehouse_uid')
const { value: stock_rack_uid } = useField<string | null>('stock_rack_uid')
const { value: stock_tank_uid } = useField<string | null>('stock_tank_uid')
const { value: stock_qty } = useField<number | null>('stock_qty')

// ─── Derived ─────────────────────────────────────────────────────────────────
const selectedUnitLabel = computed(() => {
    const found = unitsStore.data.find(u => u.uid === unit_uid.value)
    return found ? `${found.name} (${found.symbol})` : '-'
})

const stockFieldErrors = computed(() =>
    [errors.value.stock_warehouse_uid, errors.value.stock_rack_uid, errors.value.stock_tank_uid, errors.value.stock_qty]
        .filter(Boolean).length
)

const infoFieldErrors = computed(() => {
    const stockKeys = new Set(STOCK_FIELDS as readonly string[])
    return Object.keys(errors.value).filter(k => !stockKeys.has(k)).length
})

// ─── Fetcher factory ─────────────────────────────────────────────────────────
/** Builder fetcher paginasi standar dari endpoint Laravel (page, per_page, search). */
const makeFetcher = (
    url: string | (() => string | null),
    mapper: (item: any) => Option = (item) => ({ title: item.name, value: item.uid })
): AsyncFetcher<Option> => {
    return async ({ search, page, perPage, signal }) => {
        const endpoint = typeof url === 'function' ? url() : url
        if (!endpoint) return { items: [], hasMore: false }

        const res = await api.get(endpoint, {
            params: {
                page,
                per_page: perPage,
                search,
                sort_by: 'created_at',
                sort_dir: 'desc',
            },
            signal,
        })

        const payload = res.data ?? {}
        const rawItems = payload.data ?? []
        const meta = payload.meta ?? payload.pagination ?? {}
        const currentPage = Number(meta.current_page ?? page)
        const lastPage = Number(meta.last_page ?? 1)

        return {
            items: rawItems.map(mapper),
            hasMore: currentPage < lastPage,
        }
    }
}

// ─── Fetchers ────────────────────────────────────────────────────────────────
const fetchMaterialGroup = makeFetcher('/material_groups')
const fetchBrand = makeFetcher('/item_brands')
const fetchItemCategory = makeFetcher('/item_categories')
const fetchMovementCategory = makeFetcher('/movement_categories')
const fetchValuationMethod = makeFetcher('/valuation_methods')
const fetchSupplier = makeFetcher('/supplier')
const fetchRequestTypes = makeFetcher('/request_types')
const fetchUnitTypes = makeFetcher('/usage_units')
const fetchWarehouse = makeFetcher('/warehouse')

// Cascaded: sub material group — tergantung material_group_uid
const fetchSubMaterialGroup = makeFetcher(
    () => material_group_uid.value
        ? `/sub_material_groups/get_by_material_group_uid/${material_group_uid.value}`
        : null
)

// Cascaded: rack & tank — tergantung stock_warehouse_uid
const fetchRack = makeFetcher(
    () => stock_warehouse_uid.value ? `/rack/${stock_warehouse_uid.value}` : null
)
const fetchTank = makeFetcher(
    () => stock_warehouse_uid.value ? `/tank/${stock_warehouse_uid.value}` : null
)

// ─── initialSelected untuk edit mode ─────────────────────────────────────────
const toOption = (obj: { uid: string; name: string } | null | undefined): Option | null =>
    obj ? { title: obj.name, value: obj.uid } : null

const initialMaterialGroup = computed(() => toOption(props.data.material_group))
const initialSubMaterialGroup = computed(() => toOption(props.data.sub_material_group))
const initialBrand = computed(() => toOption(props.data.brand))
const initialCategory = computed(() => toOption(props.data.category))
const initialMovementCategory = computed(() => toOption(props.data.movement_category))
const initialValuationMethod = computed(() => toOption(props.data.valuation_method))
const initialSupplier = computed(() => toOption(props.data.supplier))
const initialRequestTypes = computed<Option[]>(() =>
    props.data.request_types?.map(r => ({ title: r.name, value: r.uid })) ?? []
)
const initialUnitTypes = computed<Option[]>(() =>
    props.data.unit_types?.map(u => ({ title: u.name, value: u.uid })) ?? []
)

/**
 * Initial selected untuk lokasi stok saat edit mode. Tanpa ini BaseAsyncSelect
 * hanya menampilkan UID mentah bila item terpilih belum muncul di halaman
 * pertama hasil fetch.
 */
const initialWarehouse = computed<Option | null>(() =>
    toOption(props.stockInitial?.warehouse ?? null),
)
const initialRack = computed<Option | null>(() =>
    toOption(props.stockInitial?.rack ?? null),
)
const initialTank = computed<Option | null>(() =>
    toOption(props.stockInitial?.tank ?? null),
)

// ─── Cascades: reset child saat parent berubah ───────────────────────────────
watch(material_group_uid, (val, oldVal) => {
    if (oldVal !== undefined && val !== oldVal) sub_material_group_uid.value = null
})

watch(stock_warehouse_uid, async (val, oldVal) => {
    if (oldVal !== undefined && val !== oldVal) {
        stock_rack_uid.value = null
        stock_tank_uid.value = null
    }
    await loadOccupiedLocations()
})

// ─── Deteksi Rak/Tangki yang sudah terisi (exclude dari dropdown) ────────────
const occupiedRackUids = ref<string[]>([])
const occupiedTankUids = ref<string[]>([])

const loadOccupiedLocations = async () => {
    if (!stock_warehouse_uid.value) {
        occupiedRackUids.value = []
        occupiedTankUids.value = []
        return
    }

    try {
        const { api } = await import('@/api/api')
        const res = await api.get('/stock', {
            params: {
                page: 1,
                per_page: -1,
                warehouse_uid: stock_warehouse_uid.value,
            },
        })
        const stocks = res.data?.data ?? []

        const racks = new Set<string>()
        const tanks = new Set<string>()
        for (const stock of stocks) {
            // Jangan exclude record stok milik item ini sendiri (saat edit mode),
            // agar user masih bisa pilih rak/tangki yang sekarang digunakan.
            if (props.stockInitial?.uid && stock.uid === props.stockInitial.uid) continue

            if (stock.rack?.uid) racks.add(stock.rack.uid)
            if (stock.tank?.uid) tanks.add(stock.tank.uid)
        }

        occupiedRackUids.value = Array.from(racks)
        occupiedTankUids.value = Array.from(tanks)
    } catch (err) {
        console.error('[ItemsForm] loadOccupiedLocations error:', err)
        occupiedRackUids.value = []
        occupiedTankUids.value = []
    }
}

// ─── Unit loader & quick-create (BaseAutocompleteCreatable masih pakai store) ─
const loadUnitData = async () => {
    await unitsStore.fetch({
        page: 1,
        itemsPerPage: -1,
        sortBy: [{ key: 'name', order: 'asc' }],
        search: '',
    })
    unitItem.value = unitsStore.data.map(it => ({ title: `${it.name} (${it.symbol})`, value: it.uid }))
}

const handleQuickCreateUnit = (searchText: string) => {
    newUnitName.value = searchText
    newUnitSymbol.value = ''
}

const handleConfirmCreateUnit = async () => {
    await unitsStore.create({ name: newUnitName.value, symbol: newUnitSymbol.value })
    await loadUnitData()
    const created = unitItem.value.find(i => i.title.startsWith(newUnitName.value))
    if (created) unit_uid.value = created.value
    unitRef.value?.closeCreateDialog()
    newUnitName.value = ''
    newUnitSymbol.value = ''
}

// ─── Lifecycle ───────────────────────────────────────────────────────────────
onMounted(async () => {
    loadingForm.value = true
    try {
        await loadUnitData()
        // Load occupied rack/tank jika warehouse sudah terisi (edit mode)
        if (stock_warehouse_uid.value) await loadOccupiedLocations()
    } catch {
        message.setMessage({
            text: t('loadingFormError'),
            timeout: 3000,
            color: 'error',
        })
    } finally {
        loadingForm.value = false
    }
})

// ─── Stepper navigation ─────────────────────────────────────────────────────
const step1Valid = ref(false)

const goToNextStep = async () => {
    const { valid, errors: errs } = await validate()
    const hasInfoError = Object.keys(errs).some(k => (INFO_FIELDS as readonly string[]).includes(k))

    if (!valid && hasInfoError) {
        currentStep.value = 1
        return
    }

    step1Valid.value = true
    currentStep.value = 2
}

const goToPreviousStep = () => {
    currentStep.value = 1
}

// ─── Submit ──────────────────────────────────────────────────────────────────
const submit = handleSubmit(
    (values) => emit('submit', values),
    (errs) => {
        const hasStockError = Object.keys(errs).some(k => (STOCK_FIELDS as readonly string[]).includes(k))
        const hasInfoError = Object.keys(errs).some(k => (INFO_FIELDS as readonly string[]).includes(k))
        if (hasInfoError) currentStep.value = 1
        else if (hasStockError) currentStep.value = 2
    }
)

const handleClose = () => {
    resetForm()
    emit('close')
}
</script>

<template>
    <v-dialog
        persistent
        max-width="1100"
        :model-value="props.modelValue"
        scrollable
    >
        <v-card class="rounded-lg d-flex flex-column items-form-card" :loading="loadingForm" style="max-height: 92vh;">
            <!-- Hero header -->
            <div class="hero" :style="{ color: heroColor }">
                <div class="hero-content">
                    <div class="hero-badge">
                        <v-icon :icon="MdiShape" class="hero-badge-icon" :color="heroColor" />
                    </div>
                    <div class="hero-text">
                        <div class="hero-label">{{ t('item') }}</div>
                        <div class="hero-title">{{ props.title }}</div>
                    </div>
                    <v-btn
                        icon
                        density="comfortable"
                        variant="text"
                        :color="heroColor"
                        :disabled="loadingForm || props.saving"
                        :aria-label="t('close')"
                        @click="handleClose"
                    >
                        <v-icon :icon="MdiClose" size="20" />
                    </v-btn>
                </div>
            </div>

            <v-sheet class="stepper-wrapper shrink-0 bg-surface" :elevation="0">
                <v-stepper
                    v-model="currentStep"
                    hide-actions
                    flat
                    editable
                    class="bg-transparent"
                >
                    <v-stepper-header>
                        <v-stepper-item
                            :value="1"
                            :title="t('itemInfo')"
                            :subtitle="infoFieldErrors > 0 ? t('checkErrors') : ''"
                            :editable="true"
                            :error="currentStep === 2 && infoFieldErrors > 0"
                            :complete="currentStep > 1 && infoFieldErrors === 0"
                            color="primary"
                        />
                        <v-divider />
                        <v-stepper-item
                            :value="2"
                            :title="t('stock')"
                            :subtitle="stockFieldErrors > 0 ? t('checkErrors') : ''"
                            :editable="step1Valid"
                            :error="stockFieldErrors > 0"
                            color="primary"
                        />
                    </v-stepper-header>
                </v-stepper>
            </v-sheet>

            <v-divider />

            <v-form @submit.prevent="submit" class="d-flex flex-column grow" style="min-height: 0;">
                <v-card-text class="pa-6 grow overflow-y-auto">
                    <v-stepper-window v-model="currentStep" class="ma-0">
                        <!-- ══════ STEP 1: INFO BARANG ══════ -->
                        <v-stepper-window-item :value="1">
                            <!-- Klasifikasi -->
                            <div class="section-header mb-3">
                                <v-icon :icon="MdiShape" size="18" color="primary" />
                                <span class="text-subtitle-2 font-weight-bold text-primary">{{ t('classification') }}</span>
                            </div>
                            <v-row dense>
                                <v-col cols="12" sm="6">
                                    <base-async-select
                                        v-model="material_group_uid"
                                        :fetcher="fetchMaterialGroup"
                                        :initial-selected="initialMaterialGroup"
                                        :label="t('materialGroup')"
                                        :error-messages="errors.material_group_uid"
                                    />
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <!-- Reload saat parent berubah via :key -->
                                    <base-async-select
                                        :key="`sub-${material_group_uid}`"
                                        v-model="sub_material_group_uid"
                                        :fetcher="fetchSubMaterialGroup"
                                        :initial-selected="initialSubMaterialGroup"
                                        :label="t('subMaterialGroup')"
                                        :error-messages="errors.sub_material_group_uid"
                                        :disabled="!material_group_uid"
                                    />
                                </v-col>
                                <v-col cols="12" sm="6" md="4">
                                    <base-async-select
                                        v-model="category_uid"
                                        :fetcher="fetchItemCategory"
                                        :initial-selected="initialCategory"
                                        :label="t('itemCategory')"
                                        :error-messages="errors.category_uid"
                                    />
                                </v-col>
                                <v-col cols="12" sm="6" md="4">
                                    <base-async-select
                                        v-model="brand_uid"
                                        :fetcher="fetchBrand"
                                        :initial-selected="initialBrand"
                                        :label="t('brand')"
                                        :error-messages="errors.brand_uid"
                                    />
                                </v-col>
                                <v-col cols="12" sm="6" md="4">
                                    <base-async-select
                                        v-model="movement_category_uid"
                                        :fetcher="fetchMovementCategory"
                                        :initial-selected="initialMovementCategory"
                                        :label="t('movementCategory')"
                                        :error-messages="errors.movement_category_uid"
                                    />
                                </v-col>
                            </v-row>

                            <v-divider class="my-4" />

                            <!-- Identitas -->
                            <div class="section-header mb-3">
                                <v-icon :icon="MdiIdentifier" size="18" color="primary" />
                                <span class="text-subtitle-2 font-weight-bold text-primary">{{ t('identity') }}</span>
                            </div>
                            <v-row dense>
                                <v-col cols="12" md="6">
                                    <v-text-field
                                        v-model="name"
                                        :label="t('name')"
                                        :error-messages="errors.name"
                                        variant="outlined"
                                        density="comfortable"
                                        autocomplete="off"
                                    />
                                </v-col>
                                <v-col cols="12" sm="6" md="3">
                                    <v-text-field
                                        v-model="part_number"
                                        :label="t('partNumber')"
                                        :error-messages="errors.part_number"
                                        variant="outlined"
                                        density="comfortable"
                                        autocomplete="off"
                                    />
                                </v-col>
                                <v-col cols="12" sm="6" md="3">
                                    <v-text-field
                                        v-model="interchange_part"
                                        :label="t('interchangePart')"
                                        :error-messages="errors.interchange_part"
                                        variant="outlined"
                                        density="comfortable"
                                        autocomplete="off"
                                    />
                                </v-col>
                            </v-row>

                            <v-divider class="my-4" />

                            <!-- Satuan & Tipe -->
                            <div class="section-header mb-3">
                                <v-icon :icon="MdiRuler" size="18" color="primary" />
                                <span class="text-subtitle-2 font-weight-bold text-primary">{{ t('unitAndTypes') }}</span>
                            </div>
                            <v-row dense>
                                <v-col cols="12" sm="6" md="4">
                                    <base-autocomplete-creatable
                                        ref="unitRef"
                                        v-model="unit_uid"
                                        :loading="unitsStore.loading"
                                        :items="unitItem"
                                        :label="t('unit')"
                                        :error-messages="errors.unit_uid"
                                        :disabled="unitsStore.loading"
                                        clearable
                                        table-name="item_units"
                                        table-label="Unit"
                                        :create-loading="unitsStore.loading"
                                        @quick-create="handleQuickCreateUnit"
                                        @confirm-create="handleConfirmCreateUnit"
                                    >
                                        <template #create-form>
                                            <v-row dense>
                                                <v-col cols="12">
                                                    <v-text-field
                                                        v-model="newUnitName"
                                                        label="Nama Unit"
                                                        variant="outlined"
                                                        density="comfortable"
                                                    />
                                                </v-col>
                                                <v-col cols="12">
                                                    <v-text-field
                                                        v-model="newUnitSymbol"
                                                        label="Simbol"
                                                        variant="outlined"
                                                        density="comfortable"
                                                    />
                                                </v-col>
                                            </v-row>
                                        </template>
                                    </base-autocomplete-creatable>
                                </v-col>
                                <v-col cols="12" sm="6" md="4">
                                    <base-async-select
                                        v-model="unit_types_uid"
                                        :fetcher="fetchUnitTypes"
                                        :initial-selected="initialUnitTypes"
                                        :label="t('unitTypes')"
                                        :error-messages="errors.unit_types_uid as any"
                                        multiple
                                    />
                                </v-col>
                                <v-col cols="12" sm="6" md="4">
                                    <base-async-select
                                        v-model="request_types_uid"
                                        :fetcher="fetchRequestTypes"
                                        :initial-selected="initialRequestTypes"
                                        :label="t('requestTypes')"
                                        :error-messages="errors.request_types_uid as any"
                                        multiple
                                    />
                                </v-col>
                                <v-col cols="12" sm="6" md="4">
                                    <base-async-select
                                        v-model="valuation_method_uid"
                                        :fetcher="fetchValuationMethod"
                                        :initial-selected="initialValuationMethod"
                                        :label="t('valuationMethod')"
                                        :error-messages="errors.valuation_method_uid"
                                    />
                                </v-col>
                                <v-col cols="12" sm="6" md="4">
                                    <v-number-input
                                        v-model="min_qty"
                                        :label="t('minQty')"
                                        :error-messages="errors.min_qty"
                                        :min="0"
                                        control-variant="stacked"
                                        variant="outlined"
                                        density="comfortable"
                                    />
                                </v-col>
                                <v-col cols="12" sm="6" md="4">
                                    <base-date-input v-model="exp_date" />
                                </v-col>
                            </v-row>

                            <v-divider class="my-4" />

                            <!-- Harga & Supplier -->
                            <div class="section-header mb-3">
                                <v-icon :icon="MdiCashMultiple" size="18" color="primary" />
                                <span class="text-subtitle-2 font-weight-bold text-primary">{{ t('pricingAndSupplier') }}</span>
                            </div>
                            <v-row dense>
                                <v-col cols="12" sm="6">
                                    <base-currency-input
                                        v-model="price"
                                        :label="t('price')"
                                        :error-messages="errors.price"
                                    />
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <base-async-select
                                        v-model="supplier_uid"
                                        :fetcher="fetchSupplier"
                                        :initial-selected="initialSupplier"
                                        :label="t('supplier')"
                                        :error-messages="errors.supplier_uid"
                                    />
                                </v-col>
                            </v-row>

                            <v-divider class="my-4" />

                            <!-- Info Tambahan -->
                            <div class="section-header mb-3">
                                <v-icon :icon="MdiTextLong" size="18" color="primary" />
                                <span class="text-subtitle-2 font-weight-bold text-primary">{{ t('additionalInfo') }}</span>
                            </div>
                            <v-row dense>
                                <v-col cols="12">
                                    <v-textarea
                                        v-model="additional_info"
                                        :label="t('additionalInfo')"
                                        :error-messages="errors.additional_info"
                                        row-height="30"
                                        variant="outlined"
                                        density="comfortable"
                                        rows="3"
                                        autocomplete="off"
                                    />
                                </v-col>
                                <v-col cols="12">
                                    <base-file-input v-model="image" :cleareable="true" />
                                </v-col>
                            </v-row>
                        </v-stepper-window-item>

                        <!-- ══════ STEP 2: STOK ══════ -->
                        <v-stepper-window-item :value="2">
                            <v-alert
                                type="info"
                                variant="tonal"
                                density="compact"
                                class="mb-4"
                                :text="t('initialStockHint')"
                            />

                            <!-- Lokasi Gudang -->
                            <div class="section-header mb-3">
                                <v-icon :icon="MdiWarehouse" size="18" color="primary" />
                                <span class="text-subtitle-2 font-weight-bold text-primary">{{ t('storageLocation') }}</span>
                            </div>
                            <v-row dense>
                                <v-col cols="12" md="6">
                                    <base-async-select
                                        v-model="stock_warehouse_uid"
                                        :fetcher="fetchWarehouse"
                                        :label="t('warehouse')"
                                        :error-messages="errors.stock_warehouse_uid"
                                        :initial-selected="initialWarehouse"
                                    />
                                </v-col>
                                <v-col cols="12" md="3">
                                    <base-async-select
                                        :key="`rack-${stock_warehouse_uid}`"
                                        v-model="stock_rack_uid"
                                        :fetcher="fetchRack"
                                        :label="t('rack')"
                                        :error-messages="errors.stock_rack_uid"
                                        :disabled="!stock_warehouse_uid"
                                        :exclude-values="occupiedRackUids"
                                        :initial-selected="initialRack"
                                    />
                                </v-col>
                                <v-col cols="12" md="3">
                                    <base-async-select
                                        :key="`tank-${stock_warehouse_uid}`"
                                        v-model="stock_tank_uid"
                                        :fetcher="fetchTank"
                                        :label="t('tank')"
                                        :error-messages="errors.stock_tank_uid"
                                        :disabled="!stock_warehouse_uid"
                                        :exclude-values="occupiedTankUids"
                                        :initial-selected="initialTank"
                                    />
                                </v-col>
                            </v-row>

                            <v-divider class="my-4" />

                            <!-- Qty -->
                            <div class="section-header mb-3">
                                <v-icon :icon="MdiCounter" size="18" color="primary" />
                                <span class="text-subtitle-2 font-weight-bold text-primary">{{ t('quantity') }}</span>
                            </div>
                            <v-row dense>
                                <v-col cols="12" md="6">
                                    <v-text-field
                                        :model-value="selectedUnitLabel"
                                        :label="t('unit')"
                                        variant="outlined"
                                        density="comfortable"
                                        readonly
                                        :placeholder="unit_uid ? '' : t('selectItemFirst')"
                                    />
                                </v-col>
                                <v-col cols="12" md="6">
                                    <v-number-input
                                        v-model="stock_qty"
                                        :label="t('qty')"
                                        :error-messages="errors.stock_qty"
                                        :min="0"
                                        control-variant="stacked"
                                        variant="outlined"
                                        density="comfortable"
                                        :disabled="!stock_warehouse_uid"
                                    />
                                </v-col>
                            </v-row>
                        </v-stepper-window-item>
                    </v-stepper-window>
                </v-card-text>

                <v-divider />

                <v-card-actions class="pa-4 justify-end gap-2">
                    <v-btn
                        variant="tonal"
                        :disabled="loadingForm || props.saving"
                        @click="handleClose"
                    >
                        {{ t('cancel') }}
                    </v-btn>
                    <!-- Step 1 → Next -->
                    <v-btn
                        v-if="currentStep === 1"
                        color="primary"
                        variant="elevated"
                        :disabled="loadingForm || props.saving"
                        :append-icon="MdiArrowRight"
                        @click="goToNextStep"
                    >
                        {{ t('next') }}
                    </v-btn>
                    <!-- Step 2 → Back + Save -->
                    <template v-else>
                        <v-btn
                            variant="outlined"
                            :disabled="loadingForm || props.saving"
                            :prepend-icon="MdiArrowLeft"
                            @click="goToPreviousStep"
                        >
                            {{ t('back') }}
                        </v-btn>
                        <v-btn
                            type="submit"
                            color="primary"
                            variant="elevated"
                            :loading="props.saving"
                            :disabled="loadingForm"
                            :prepend-icon="MdiContentSaveOutline"
                        >
                            {{ t('save') }}
                        </v-btn>
                    </template>
                </v-card-actions>
            </v-form>
        </v-card>
    </v-dialog>
</template>

<style scoped>
.items-form-card { overflow: hidden; }

/* ══ Hero ══ */
.hero {
    position: relative;
    overflow: hidden;
    padding: 18px 22px;
    flex-shrink: 0;
}
.hero-bg {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgb(var(--v-theme-primary)) 0%, rgba(var(--v-theme-primary), 0.78) 100%);
}
.hero-bg::before,
.hero-bg::after {
    content: '';
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.08);
}
.hero-bg::before { width: 180px; height: 180px; top: -60px; right: -40px; }
.hero-bg::after  { width: 120px; height: 120px; bottom: -50px; left: -30px; }
.hero-content {
    position: relative;
    display: flex;
    align-items: center;
    gap: 14px;
}
.hero-badge {
    width: 44px; height: 44px;
    border-radius: 12px;
    background: rgba(180,180,180,0.2);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.hero-badge-icon {
    width: 22px !important; height: 22px !important;
}
.hero-text { flex: 1; min-width: 0; }
.hero-label {
    font-size: 0.68rem;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    opacity: 0.85;
    font-weight: 500;
}
.hero-title {
    font-size: 1.2rem;
    font-weight: 700;
    line-height: 1.25;
    margin-top: 2px;
    word-break: break-word;
}

/* ══ Section header (accent + icon + title) ══ */
.section-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    background: rgba(var(--v-theme-primary), 0.05);
    border-left: 3px solid rgb(var(--v-theme-primary));
    border-radius: 6px;
}

/* ══ Stepper ══ */
.stepper-wrapper {
    position: relative;
    z-index: 2;
    border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.08);
    background: rgba(var(--v-theme-on-surface), 0.015);
}
.stepper-wrapper :deep(.v-stepper) {
    border-radius: 0;
    box-shadow: none !important;
}
.stepper-wrapper :deep(.v-stepper-header) {
    box-shadow: none !important;
    min-height: 60px;
}
.stepper-wrapper :deep(.v-stepper-item__title) {
    font-weight: 600;
    font-size: 0.9rem;
}
.stepper-wrapper :deep(.v-stepper-actions) {
    display: none !important;
}

/* ══ Responsive ══ */
@media (max-width: 600px) {
    .hero { padding: 14px 16px; }
    .hero-title { font-size: 1.05rem; }
    .hero-badge { width: 38px; height: 38px; }
    .hero-badge-icon { width: 18px !important; height: 18px !important; }
}
</style>
