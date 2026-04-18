<script lang="ts" setup>
import { computed, ref } from 'vue'
import MdiPlusCircleOutline from '~icons/mdi/plus-circle-outline'

// ─── Internal sentinel value ─────────────────────────────────────────────────
const CREATE_NEW_SENTINEL = '__CREATE_NEW__'

// ─── Props ────────────────────────────────────────────────────────────────────
const props = withDefaults(
    defineProps<{
        // v-model
        modelValue: number | string | object | number[] | string[] | object[] | null | unknown

        // Basic v-autocomplete props
        label: string
        items: SelectItem[]
        loading?: boolean
        errorMessages?: string | readonly string[] | null | undefined
        chips?: boolean
        multiple?: boolean
        disabled?: boolean
        clearable?: boolean
        hint?: string
        persistentHint?: boolean

        // Creatable-specific props
        /** Human-readable table/resource label shown in the quick-create dialog title */
        tableLabel: string
        /** Identifier for the resource (e.g. 'suppliers', 'items'). Exposed via emit so parent can route the create call. */
        tableName: string
        /** Loading state for the save button inside the quick-create dialog */
        createLoading?: boolean
        /** Allow creation only when there is no exact match; set to false to always show the create option */
        createOnlyWhenNoMatch?: boolean
    }>(),
    {
        chips: false,
        multiple: false,
        disabled: false,
        clearable: false,
        persistentHint: false,
        loading: false,
        createLoading: false,
        createOnlyWhenNoMatch: true,
    }
)

// ─── Emits ────────────────────────────────────────────────────────────────────
const emit = defineEmits<{
    /** Standard v-model binding */
    (e: 'update:modelValue', value: any): void
    /**
     * Fired when the user clicks the "Add new" option.
     * `searchText` is what the user had typed so parent can pre-fill the form.
     */
    (e: 'quickCreate', searchText: string): void
    /**
     * Fired when the user confirms the quick-create dialog (clicks Save).
     * Parent is responsible for the actual API call and, on success, should
     * call `closeCreateDialog()` via the exposed method.
     */
    (e: 'confirmCreate'): void
    /** Fired when the quick-create dialog is closed without saving */
    (e: 'cancelCreate'): void
}>()

// ─── Internal state ───────────────────────────────────────────────────────────
const searchText = ref('')
const createDialog = ref(false)

// ─── Computed: items augmented with the "Add new" option when needed ──────────
const augmentedItems = computed<SelectItem[]>(() => {
    const base = props.items ?? []
    const query = (searchText.value ?? '').trim().toLowerCase()

    const shouldShowCreate = (() => {
        if (!props.createOnlyWhenNoMatch) return true
        if (!query) return false
        // Show "create" only when no item title matches the query
        return !base.some(
            (item) => String(item.title ?? '').toLowerCase() === query
        )
    })()

    if (!shouldShowCreate) return base

    const createItem: SelectItem = {
        title: `Tambah "${searchText.value.trim()}"`,
        value: CREATE_NEW_SENTINEL as any,
    }

    return [...base, createItem]
})

// ─── Handlers ─────────────────────────────────────────────────────────────────
const handleSelected = (value: any) => {
    if (value === CREATE_NEW_SENTINEL) {
        // Prevent the sentinel value from being committed to the model
        // Restore previous model value
        emit('quickCreate', searchText.value.trim())
        createDialog.value = true
        return
    }
    emit('update:modelValue', value)
}

const handleUpdateSearch = (value: string) => {
    searchText.value = value ?? ''
}

const handleConfirmCreate = () => {
    emit('confirmCreate')
}

const handleCancelCreate = () => {
    createDialog.value = false
    emit('cancelCreate')
}

// ─── Exposed helpers ──────────────────────────────────────────────────────────
/** Call this from the parent after a successful quick-create API call */
const closeCreateDialog = () => {
    createDialog.value = false
}

defineExpose({ closeCreateDialog })
</script>

<template>
    <!-- ── Main Autocomplete ─────────────────────────────────────────────── -->
    <v-autocomplete
        :model-value="props.modelValue"
        :loading="props.loading"
        :items="augmentedItems"
        :label="props.label"
        item-title="title"
        item-value="value"
        variant="outlined"
        density="comfortable"
        :error-messages="props.errorMessages"
        :chips="props.chips"
        :multiple="props.multiple"
        :disabled="props.disabled"
        :clearable="props.clearable"
        :hint="props.hint"
        :persistent-hint="props.persistentHint"
        autocomplete="off"
        @update:model-value="handleSelected"
        @update:search="handleUpdateSearch"
    >
        <!-- Custom item rendering so the "Add new" row stands out -->
        <template #item="{ item, props: itemProps }">
            <v-divider v-if="item.raw.value === '__CREATE_NEW__'" class="my-1" />
            <v-list-item
                v-bind="itemProps"
                :title="item.raw.title"
                :class="item.raw.value === '__CREATE_NEW__' ? 'create-new-item text-primary' : ''"
            >
                <template v-if="item.raw.value === '__CREATE_NEW__'" #prepend>
                    <v-icon :icon="MdiPlusCircleOutline" size="18" class="mr-1" />
                </template>
            </v-list-item>
        </template>

        <!-- Pass through any other slots the consumer provides -->
        <template v-for="(_, name) in $slots" #[name]="slotData">
            <slot :name="name" v-bind="slotData ?? {}" />
        </template>
    </v-autocomplete>

    <!-- ── Quick-Create Dialog ──────────────────────────────────────────── -->
    <v-dialog
        v-model="createDialog"
        max-width="600"
        persistent
        @keydown.esc="handleCancelCreate"
    >
        <v-card>
            <!-- Dialog title -->
            <v-card-title class="d-flex align-center ga-2 pt-4 px-6">
                <v-icon :icon="MdiPlusCircleOutline" color="primary" />
                <span>Tambah {{ props.tableLabel }}</span>
            </v-card-title>

            <v-divider />

            <!-- Form area – provided entirely by the consumer via slot -->
            <v-card-text class="pa-6">
                <slot
                    name="create-form"
                    :search-text="searchText"
                    :table-name="props.tableName"
                />
            </v-card-text>

            <v-divider />

            <!-- Actions – consumer can override via slot -->
            <v-card-actions class="px-6 py-4">
                <v-spacer />

                <slot
                    name="create-actions"
                    :confirm="handleConfirmCreate"
                    :cancel="handleCancelCreate"
                    :loading="props.createLoading"
                >
                    <!-- Default actions -->
                    <v-btn
                        variant="text"
                        :disabled="props.createLoading"
                        @click="handleCancelCreate"
                    >
                        Batal
                    </v-btn>
                    <v-btn
                        color="primary"
                        variant="flat"
                        :loading="props.createLoading"
                        @click="handleConfirmCreate"
                    >
                        Simpan
                    </v-btn>
                </slot>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<style scoped>
.create-new-item {
    font-weight: 500;
}
</style>
