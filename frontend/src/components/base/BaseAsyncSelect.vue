<script lang="ts" setup generic="T extends Record<string, any>">
/**
 * BaseAsyncSelect
 * ---------------
 * Autocomplete dengan lazy loading / infinite scroll.
 * Memuat N opsi per panggilan API (default 5), memuat lebih banyak saat user
 * scroll ke akhir list. Pencarian dilakukan di server (debounced).
 *
 * Contoh fetcher:
 *   async ({ search, page, perPage, signal }) => {
 *       const res = await api.get('/items', {
 *           params: { search, page, per_page: perPage },
 *           signal,
 *       })
 *       return {
 *           items: res.data.data,
 *           hasMore: res.data.meta.current_page < res.data.meta.last_page,
 *       }
 *   }
 */
import { ref, shallowRef, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useDebounceFn, useIntersectionObserver } from '@vueuse/core'

// ─── Types ───────────────────────────────────────────────────────────────────
export interface FetchParams {
    search: string
    page: number
    perPage: number
    signal: AbortSignal
}

export interface FetchResult<T> {
    items: T[]
    hasMore: boolean
}

export type AsyncFetcher<T> = (params: FetchParams) => Promise<FetchResult<T>>

type ModelValue = string | number | string[] | number[] | null | undefined

interface Props {
    modelValue: ModelValue
    fetcher: AsyncFetcher<T>
    label?: string
    placeholder?: string
    errorMessages?: string | string[]
    /**
     * Nama property untuk ditampilkan / dikirim sebagai value. Diketik sebagai
     * `string` (bukan `keyof T & string`) supaya Vue compiler tidak membungkus
     * ke PropType object yang bentrok dengan `SelectItemKey` milik Vuetify.
     * Runtime-safety tetap dipegang Vuetify saat binding item.
     */
    itemTitle?: string
    itemValue?: string
    pageSize?: number
    multiple?: boolean
    clearable?: boolean
    disabled?: boolean
    readonly?: boolean
    loading?: boolean
    variant?: 'outlined' | 'filled' | 'solo' | 'plain' | 'underlined'
    density?: 'default' | 'comfortable' | 'compact'
    /** Item yang dipilih sebelumnya (untuk edit mode) — agar label tampil meski
     *  value belum ada di hasil fetch halaman 1. */
    initialSelected?: T | T[] | null
    /** Debounce ms untuk pencarian. */
    debounceMs?: number
    /** Nilai-nilai yang harus di-exclude dari daftar opsi (untuk mencegah
     *  duplikasi pemilihan antar dropdown). */
    excludeValues?: Array<string | number | null | undefined>
}

// ─── Props / Emits ───────────────────────────────────────────────────────────
const props = withDefaults(defineProps<Props>(), {
    itemTitle: 'title' as any,
    itemValue: 'value' as any,
    pageSize: 5,
    multiple: false,
    clearable: true,
    disabled: false,
    readonly: false,
    loading: false,
    variant: 'outlined',
    density: 'comfortable',
    debounceMs: 300,
})

const emit = defineEmits<{
    'update:modelValue': [value: ModelValue]
    'update:search': [value: string]
    'select': [item: T | T[] | null]
}>()

// ─── State ───────────────────────────────────────────────────────────────────
const items = shallowRef<T[]>([])
const search = ref('')
const page = ref(1)
const initialLoading = ref(false)
const loadingMore = ref(false)
const hasMore = ref(true)

let abortController: AbortController | null = null
let isMounted = false

// ─── Helpers ─────────────────────────────────────────────────────────────────
const abortPrevious = () => {
    if (abortController) {
        abortController.abort()
        abortController = null
    }
}

const dedupeByValue = (source: T[], addition: T[]): T[] => {
    const key = props.itemValue
    const seen = new Set(source.map(it => it[key]))
    const extras = addition.filter(it => !seen.has(it[key]))
    return [...source, ...extras]
}

// ─── Core fetch ──────────────────────────────────────────────────────────────
const loadItems = async (opts: { reset?: boolean } = {}) => {
    const { reset = false } = opts

    if (reset) {
        page.value = 1
        hasMore.value = true
    } else if (!hasMore.value) {
        return
    }

    const isFirstPage = page.value === 1
    if (isFirstPage) initialLoading.value = true
    else loadingMore.value = true

    abortPrevious()
    abortController = new AbortController()
    const localController = abortController

    try {
        const result = await props.fetcher({
            search: search.value,
            page: page.value,
            perPage: props.pageSize,
            signal: localController.signal,
        })

        // Ignore jika request sudah dibatalkan oleh request berikutnya.
        if (localController.signal.aborted || !isMounted) return

        if (reset) {
            items.value = result.items ?? []
        } else {
            items.value = dedupeByValue(items.value, result.items ?? [])
        }

        hasMore.value = !!result.hasMore
    } catch (err: any) {
        // Abaikan abort errors
        const isAbort = err?.name === 'AbortError' || err?.name === 'CanceledError' || err?.code === 'ERR_CANCELED'
        if (!isAbort) {
            console.error('[BaseAsyncSelect] fetch error:', err)
        }
    } finally {
        if (localController === abortController) {
            initialLoading.value = false
            loadingMore.value = false
        }
    }
}

const loadMore = async () => {
    if (initialLoading.value || loadingMore.value || !hasMore.value) return
    page.value += 1
    await loadItems()
}

// ─── Search (debounced) ──────────────────────────────────────────────────────
const debouncedSearch = useDebounceFn(() => {
    loadItems({ reset: true })
}, props.debounceMs)

watch(search, (val) => {
    emit('update:search', val)
    debouncedSearch()
})

// ─── Infinite scroll sentinel ────────────────────────────────────────────────
const sentinel = ref<HTMLElement | null>(null)

useIntersectionObserver(sentinel, ([entry]) => {
    if (entry?.isIntersecting) loadMore()
})

// ─── Display items (merge dengan initialSelected) ────────────────────────────
const displayItems = computed<T[]>(() => {
    const initial = props.initialSelected
    const key = props.itemValue

    // Gabungkan initial selected + fetched items
    let merged: T[]
    if (!initial) {
        merged = items.value
    } else {
        const initialArr = Array.isArray(initial) ? initial : [initial]
        const filtered = initialArr.filter(Boolean) as T[]
        merged = filtered.length ? dedupeByValue(filtered, items.value) : items.value
    }

    // Filter exclude values, tapi TIDAK exclude nilai yang sedang terpilih di komponen ini
    const excludes = props.excludeValues
    if (!excludes?.length) return merged

    const currentVal = props.modelValue
    const currentValues = new Set(
        Array.isArray(currentVal) ? currentVal : (currentVal ? [currentVal] : [])
    )

    const excludeSet = new Set(
        excludes.filter((v) => v !== null && v !== undefined && !currentValues.has(v))
    )

    if (!excludeSet.size) return merged
    return merged.filter((it) => !excludeSet.has(it[key] as any))
})

// ─── Lifecycle ───────────────────────────────────────────────────────────────
onMounted(() => {
    isMounted = true
    loadItems({ reset: true })
})

onBeforeUnmount(() => {
    isMounted = false
    abortPrevious()
})

// ─── Handlers ────────────────────────────────────────────────────────────────
const findItemByValue = (val: string | number): T | null => {
    const key = props.itemValue
    return (displayItems.value as T[]).find((it) => it[key] === val) ?? null
}

const onUpdateModelValue = (value: ModelValue) => {
    emit('update:modelValue', value)

    // Emit full item object untuk parent yang butuh label/metadata
    if (value === null || value === undefined) {
        emit('select', props.multiple ? [] as any : null)
    } else if (Array.isArray(value)) {
        const items = value
            .map((v) => findItemByValue(v as string | number))
            .filter(Boolean) as T[]
        emit('select', items)
    } else {
        emit('select', findItemByValue(value as string | number))
    }
}
</script>

<template>
    <v-autocomplete
        :model-value="modelValue as any"
        :items="displayItems as any[]"
        :item-title="itemTitle"
        :item-value="itemValue"
        :label="label"
        :placeholder="placeholder"
        :loading="initialLoading || loading"
        :error-messages="errorMessages"
        :multiple="multiple"
        :clearable="clearable"
        :disabled="disabled"
        :readonly="readonly"
        :variant="variant"
        :density="density"
        v-model:search="search"
        :no-filter="true"
        :return-object="false"
        :hide-no-data="initialLoading"
        hide-details="auto"
        :menu-props="{ maxHeight: 360, offset: 4 }"
        @update:model-value="onUpdateModelValue"
    >
        <!-- Loading-more sentinel diletakkan di akhir list menu -->
        <template #append-item>
            <div
                ref="sentinel"
                class="async-select__sentinel d-flex justify-center align-center"
            >
                <v-progress-circular
                    v-if="loadingMore"
                    indeterminate
                    size="18"
                    width="2"
                    color="primary"
                />
            </div>
        </template>

        <!-- Allow parent to override item rendering -->
        <template v-if="$slots.item" #item="slotProps">
            <slot name="item" v-bind="slotProps" />
        </template>

        <template v-if="$slots.selection" #selection="slotProps">
            <slot name="selection" v-bind="slotProps" />
        </template>

        <template v-if="$slots['no-data']" #no-data>
            <slot name="no-data" />
        </template>
    </v-autocomplete>
</template>

<style scoped>
.async-select__sentinel {
    min-height: 8px;
    padding: 0;
}

.async-select__sentinel:has(.v-progress-circular) {
    min-height: 40px;
    padding: 8px 0;
}
</style>
