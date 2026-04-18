<script lang="ts" setup>
import { computed, ref, onBeforeUnmount } from 'vue'
// Icons
import MdiCloudUploadOutline from '~icons/mdi/cloud-upload-outline'
import MdiFileDocumentOutline from '~icons/mdi/file-document-outline'
import MdiImageOutline from '~icons/mdi/image-outline'
import MdiClose from '~icons/mdi/close'
import MdiAlertCircleOutline from '~icons/mdi/alert-circle-outline'
import MdiOpenInNew from '~icons/mdi/open-in-new'

// Default allowed extensions
const DEFAULT_ACCEPT = '.png,.jpg,.jpeg,.jpe,.img'

const props = withDefaults(defineProps<{
    title?: string
    multiple?: boolean
    cleareable?: boolean
    disabled?: boolean
    /** Comma-separated accepted extensions, e.g. ".png,.jpg" */
    accept?: string
    modelValue: string | File | File[] | undefined
}>(), {
    title: '',
    multiple: false,
    cleareable: false,
    disabled: false,
    accept: DEFAULT_ACCEPT,
})

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | File | File[] | undefined): void
}>()

// =========================
// Helpers
// =========================
const extOf = (name: string) =>
    '.' + (name.split('.').pop() ?? '').toLowerCase()

const isAllowed = (file: File): boolean => {
    const allowed = props.accept.split(',').map(s => s.trim().toLowerCase())
    return allowed.includes(extOf(file.name))
}

const formatSize = (bytes: number): string => {
    if (!bytes) return '0 B'
    const units = ['B', 'KB', 'MB', 'GB']
    let i = 0
    let n = bytes
    while (n >= 1024 && i < units.length - 1) {
        n /= 1024
        i++
    }
    return `${n.toFixed(n < 10 && i > 0 ? 1 : 0)} ${units[i]}`
}

// =========================
// State
// =========================
const errorMessage = ref<string>('')
const isDragging = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)

// =========================
// Derived
// =========================
const isExistingFile = computed(() => typeof props.modelValue === 'string')

const existingFileName = computed(() => {
    if (typeof props.modelValue === 'string') {
        return props.modelValue.split('/').pop() ?? props.modelValue
    }
    return ''
})

const existingIsImage = computed(() => {
    if (typeof props.modelValue !== 'string') return false
    return /\.(png|jpe?g|jpe|gif|webp|bmp|svg|img)$/i.test(props.modelValue)
})

const selectedFiles = computed<File[]>(() => {
    const val = props.modelValue
    if (!val || typeof val === 'string') return []
    return Array.isArray(val) ? val : [val]
})

const hasSelectedFiles = computed(() => selectedFiles.value.length > 0)

const acceptedLabel = computed(() =>
    props.accept.split(',').map(s => s.trim().toUpperCase().replace(/^\./, '')).join(', ')
)

const showDropzone = computed(() =>
    !isExistingFile.value && (props.multiple || !hasSelectedFiles.value)
)

// =========================
// Preview URLs (revoke on unmount)
// =========================
const previewCache = new Map<File, string>()

const previewUrlFor = (file: File): string | null => {
    if (!file.type.startsWith('image/')) return null
    let url = previewCache.get(file)
    if (!url) {
        url = URL.createObjectURL(file)
        previewCache.set(file, url)
    }
    return url
}

const revokePreview = (file: File) => {
    const url = previewCache.get(file)
    if (url) {
        URL.revokeObjectURL(url)
        previewCache.delete(file)
    }
}

onBeforeUnmount(() => {
    for (const url of previewCache.values()) URL.revokeObjectURL(url)
    previewCache.clear()
})

// =========================
// Handlers
// =========================
const processFiles = (incoming: File[]) => {
    errorMessage.value = ''
    const valid = incoming.filter(isAllowed)
    const invalid = incoming.filter(f => !isAllowed(f))

    if (invalid.length) {
        errorMessage.value = `File tidak valid: ${invalid.map(f => f.name).join(', ')}. Hanya ${acceptedLabel.value} yang diizinkan.`
    }
    if (!valid.length) return

    if (!props.multiple) {
        emit('update:modelValue', valid[0])
    } else {
        emit('update:modelValue', [...selectedFiles.value, ...valid])
    }
}

const onNativeChange = (e: Event) => {
    const target = e.target as HTMLInputElement
    const files = target.files ? Array.from(target.files) : []
    if (files.length) processFiles(files)
    // reset native value supaya file yang sama bisa dipilih ulang
    target.value = ''
}

const openFilePicker = () => {
    if (props.disabled) return
    fileInput.value?.click()
}

const onDragEnter = (e: DragEvent) => {
    if (props.disabled) return
    e.preventDefault()
    isDragging.value = true
}
const onDragOver = (e: DragEvent) => {
    if (props.disabled) return
    e.preventDefault()
    isDragging.value = true
}
const onDragLeave = (e: DragEvent) => {
    e.preventDefault()
    isDragging.value = false
}
const onDrop = (e: DragEvent) => {
    if (props.disabled) return
    e.preventDefault()
    isDragging.value = false
    const files = Array.from(e.dataTransfer?.files ?? [])
    if (files.length) processFiles(files)
}

const handleRemoveFile = (index: number) => {
    errorMessage.value = ''
    const target = selectedFiles.value[index]
    if (target) revokePreview(target)
    const updated = selectedFiles.value.filter((_, i) => i !== index)
    if (!updated.length) {
        emit('update:modelValue', props.multiple ? [] : undefined)
    } else {
        emit('update:modelValue', props.multiple ? updated : updated[0])
    }
}

const handleClearExisting = () => {
    errorMessage.value = ''
    emit('update:modelValue', undefined)
}
</script>

<template>
    <div class="file-input">
        <!-- ══ Existing server file ══ -->
        <div v-if="isExistingFile" class="file-card">
            <div class="file-icon-wrap">
                <a
                    v-if="existingIsImage"
                    :href="(modelValue as string)"
                    target="_blank"
                    rel="noopener"
                    class="file-thumb"
                >
                    <img :src="(modelValue as string)" alt="" />
                </a>
                <div v-else class="file-icon">
                    <component :is="MdiFileDocumentOutline" class="file-icon-img" />
                </div>
            </div>
            <div class="file-body">
                <div class="file-name">{{ existingFileName }}</div>
                <a
                    :href="(modelValue as string)"
                    target="_blank"
                    rel="noopener"
                    class="file-open"
                >
                    <component :is="MdiOpenInNew" class="file-open-icon" />
                    Buka di tab baru
                </a>
            </div>
            <button
                v-if="cleareable !== false && !disabled"
                type="button"
                class="file-remove"
                :aria-label="'Hapus file'"
                @click="handleClearExisting"
            >
                <component :is="MdiClose" class="file-remove-icon" />
            </button>
        </div>

        <!-- ══ Selected (new) files ══ -->
        <div v-if="hasSelectedFiles" class="files-list">
            <div v-for="(file, index) in selectedFiles" :key="index" class="file-card">
                <div class="file-icon-wrap">
                    <div v-if="previewUrlFor(file)" class="file-thumb">
                        <img :src="previewUrlFor(file)!" alt="" />
                    </div>
                    <div v-else class="file-icon">
                        <component :is="MdiImageOutline" class="file-icon-img" />
                    </div>
                </div>
                <div class="file-body">
                    <div class="file-name">{{ file.name }}</div>
                    <div class="file-meta">{{ formatSize(file.size) }}</div>
                </div>
                <button
                    v-if="!disabled"
                    type="button"
                    class="file-remove"
                    :aria-label="'Hapus file'"
                    @click="handleRemoveFile(index)"
                >
                    <component :is="MdiClose" class="file-remove-icon" />
                </button>
            </div>
        </div>

        <!-- ══ Dropzone ══ -->
        <div
            v-if="showDropzone"
            class="dropzone"
            :class="{ 'dropzone--active': isDragging, 'dropzone--disabled': disabled }"
            role="button"
            tabindex="0"
            @click="openFilePicker"
            @keydown.enter.space.prevent="openFilePicker"
            @dragenter="onDragEnter"
            @dragover="onDragOver"
            @dragleave="onDragLeave"
            @drop="onDrop"
        >
            <div class="dropzone-icon">
                <component :is="MdiCloudUploadOutline" class="dropzone-icon-img" />
            </div>
            <div class="dropzone-text">
                <div class="dropzone-title">
                    {{ title || 'Tarik file ke sini atau klik untuk memilih' }}
                </div>
                <div class="dropzone-subtitle">
                    {{ acceptedLabel }}
                    <span v-if="multiple"> · dapat memilih beberapa file</span>
                </div>
            </div>
            <input
                ref="fileInput"
                type="file"
                class="dropzone-input"
                :accept="accept"
                :multiple="multiple"
                :disabled="disabled"
                @change="onNativeChange"
            />
        </div>

        <!-- ══ Validation error ══ -->
        <div v-if="errorMessage" class="error-banner">
            <component :is="MdiAlertCircleOutline" class="error-icon" />
            <span>{{ errorMessage }}</span>
        </div>
    </div>
</template>

<style scoped>
.file-input {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

/* ══ Dropzone ══ */
.dropzone {
    position: relative;
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 18px;
    border-radius: 12px;
    border: 2px dashed rgba(var(--v-border-color), calc(var(--v-border-opacity) * 1.4));
    background: rgba(var(--v-theme-on-surface), 0.015);
    cursor: pointer;
    transition: border-color 0.15s, background 0.15s, transform 0.15s;
    outline: none;
}
.dropzone:hover:not(.dropzone--disabled),
.dropzone:focus-visible:not(.dropzone--disabled) {
    border-color: rgba(var(--v-theme-primary), 0.6);
    background: rgba(var(--v-theme-primary), 0.04);
}
.dropzone--active {
    border-color: rgb(var(--v-theme-primary)) !important;
    background: rgba(var(--v-theme-primary), 0.08) !important;
    transform: scale(1.002);
}
.dropzone--disabled {
    cursor: not-allowed;
    opacity: 0.55;
}

.dropzone-icon {
    width: 44px; height: 44px;
    border-radius: 10px;
    background: rgba(var(--v-theme-primary), 0.1);
    color: rgb(var(--v-theme-primary));
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    transition: background 0.15s, color 0.15s;
}
.dropzone--active .dropzone-icon {
    background: rgb(var(--v-theme-primary));
    color: #fff;
}
.dropzone-icon-img { width: 24px; height: 24px; }

.dropzone-text { flex: 1; min-width: 0; }
.dropzone-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: rgba(var(--v-theme-on-surface), 0.95);
    line-height: 1.3;
}
.dropzone-subtitle {
    font-size: 0.72rem;
    color: rgba(var(--v-theme-on-surface), 0.6);
    margin-top: 2px;
    letter-spacing: 0.02em;
}

.dropzone-input {
    position: absolute;
    width: 1px; height: 1px;
    opacity: 0; pointer-events: none;
}

/* ══ File cards ══ */
.files-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.file-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    border-radius: 10px;
    background: rgb(var(--v-theme-surface));
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    transition: border-color 0.15s, box-shadow 0.15s;
}
.file-card:hover {
    border-color: rgba(var(--v-theme-primary), 0.4);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
}

.file-icon-wrap { flex-shrink: 0; }
.file-icon {
    width: 40px; height: 40px;
    border-radius: 8px;
    background: rgba(var(--v-theme-primary), 0.1);
    color: rgb(var(--v-theme-primary));
    display: flex; align-items: center; justify-content: center;
}
.file-icon-img { width: 20px; height: 20px; }
.file-thumb {
    display: block;
    width: 40px; height: 40px;
    border-radius: 8px;
    overflow: hidden;
    background: rgba(var(--v-theme-on-surface), 0.05);
    text-decoration: none;
}
.file-thumb img {
    width: 100%; height: 100%;
    object-fit: cover;
    display: block;
}

.file-body { flex: 1; min-width: 0; }
.file-name {
    font-size: 0.85rem;
    font-weight: 600;
    word-break: break-all;
    line-height: 1.3;
    color: rgba(var(--v-theme-on-surface), 0.95);
}
.file-meta {
    font-size: 0.7rem;
    color: rgba(var(--v-theme-on-surface), 0.6);
    margin-top: 2px;
}
.file-open {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.7rem;
    color: rgb(var(--v-theme-primary));
    text-decoration: none;
    margin-top: 3px;
    font-weight: 500;
}
.file-open:hover { text-decoration: underline; }
.file-open-icon { width: 12px; height: 12px; }

.file-remove {
    width: 30px; height: 30px;
    border: 0;
    background: transparent;
    color: rgba(var(--v-theme-on-surface), 0.55);
    border-radius: 8px;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    transition: background 0.15s, color 0.15s;
}
.file-remove:hover {
    background: rgba(239, 68, 68, 0.1);
    color: #EF4444;
}
.file-remove-icon { width: 16px; height: 16px; }

/* ══ Error banner ══ */
.error-banner {
    display: flex;
    align-items: flex-start;
    gap: 6px;
    padding: 8px 10px;
    border-radius: 8px;
    background: rgba(239, 68, 68, 0.08);
    color: #EF4444;
    font-size: 0.75rem;
    font-weight: 500;
}
.error-icon {
    width: 16px; height: 16px;
    flex-shrink: 0;
    margin-top: 1px;
}
</style>
