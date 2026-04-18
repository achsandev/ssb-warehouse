<script lang="ts" setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
// Composables
import { useTranslate } from '@/composables/useTranslate'
// Stores
import { useWarehouseStore } from '@/stores/warehouse'
// Components
import RackListTable from '@/modules/warehouse/components/RackListTable.vue'
import TankListTable from '@/modules/warehouse/components/TankListTable.vue'
import DetailList from '@/components/common/DetailList.vue'
// Icons
import MdiWarehouse from '~icons/mdi/warehouse'
import MdiMapMarkerOutline from '~icons/mdi/map-marker-outline'
import MdiInformationOutline from '~icons/mdi/information-outline'
import MdiArrowLeft from '~icons/mdi/arrow-left'
import MdiViewGridOutline from '~icons/mdi/view-grid-outline'
import MdiWaterOutline from '~icons/mdi/water-outline'

// ─── Setup ───────────────────────────────────────────────────────────────────
const route = useRoute()
const router = useRouter()
const t = useTranslate()
const warehouseStore = useWarehouseStore()

type TabKey = 'rack' | 'tank'

interface TabMeta {
    key: TabKey
    label: string
    // Konsisten dengan pola icon di codebase (unplugin-icons mengembalikan Vue
    // component; VIcon menerima IconValue yang tidak kompatibel dengan `unknown`).
    icon: any
}

// ─── State ───────────────────────────────────────────────────────────────────
/**
 * UID reaktif — Vue Router dapat me-reuse instance komponen saat navigasi
 * antar route yang share definition. Memakai `computed` memastikan perubahan
 * `route.params.uid` memicu watcher untuk re-fetch.
 */
const uid = computed<string>(() => String(route.params.uid ?? ''))

const tab = ref<TabKey>('rack')
const detailDialog = ref<boolean>(false)
const detailData = ref<KeyValueItem[]>([])
const warehouseData = ref<WarehouseBasicData | null>(null)

// ─── Derivations ─────────────────────────────────────────────────────────────
const tabs = computed<TabMeta[]>(() => [
    { key: 'rack', label: t('rack'), icon: MdiViewGridOutline },
    { key: 'tank', label: t('tank'), icon: MdiWaterOutline },
])

const isLoading = computed<boolean>(() => warehouseStore.loading && !warehouseData.value)

// ─── Handlers ────────────────────────────────────────────────────────────────
const loadDataWarehouse = async (targetUid: string) => {
    if (!targetUid) return
    try {
        warehouseData.value = null
        await warehouseStore.getBasicInfoByUid(targetUid)
        warehouseData.value = Array.isArray(warehouseStore.data)
            ? warehouseStore.data[0]
            : null
    } catch (err) {
        console.error('Error loading warehouse basic info:', err)
    }
}

// Immediate watcher menggantikan `onMounted + manual call` dan otomatis
// menangani kasus param berubah tanpa unmount (component reuse).
watch(uid, (value) => loadDataWarehouse(value), { immediate: true })

const handleOpenDetailDialog = (value: KeyValueItem[], isActive: boolean) => {
    detailData.value = value
    detailDialog.value = isActive
}

const handleBack = () => {
    router.push({ name: 'Warehouse' }).catch((err) => {
        console.error('Back navigation error:', err)
    })
}
</script>

<template>
    <!-- ════ Hero header ════ -->
    <v-card class="hero-card rounded-xl mb-4" elevation="0">
        <div class="hero-gradient" aria-hidden="true" />
        <div class="hero-content">
            <v-btn
                :icon="MdiArrowLeft"
                variant="text"
                color="white"
                size="small"
                density="comfortable"
                class="hero-back"
                :aria-label="t('back')"
                @click="handleBack"
            />

            <div class="hero-main">
                <div class="hero-icon">
                    <v-icon :icon="MdiWarehouse" size="28" />
                </div>

                <div class="hero-text">
                    <div class="hero-label">{{ t('storageLocations') }}</div>

                    <template v-if="isLoading">
                        <div class="hero-skeleton" aria-busy="true" />
                        <div class="hero-skeleton hero-skeleton--sub" aria-busy="true" />
                    </template>
                    <template v-else>
                        <h1 class="hero-title">
                            {{ warehouseData?.name || '-' }}
                        </h1>
                        <div v-if="warehouseData?.address" class="hero-meta">
                            <v-icon :icon="MdiMapMarkerOutline" size="14" />
                            <span>{{ warehouseData.address }}</span>
                        </div>
                    </template>
                </div>
            </div>

            <div
                v-if="warehouseData?.additional_info"
                class="hero-info"
            >
                <v-icon :icon="MdiInformationOutline" size="14" />
                <span>{{ warehouseData.additional_info }}</span>
            </div>
        </div>
    </v-card>

    <!-- ════ Tabs + content ════ -->
    <v-card class="rounded-xl" elevation="0" border>
        <v-tabs
            v-model="tab"
            color="primary"
            align-tabs="start"
            density="comfortable"
            class="px-2 pt-2 tabs-wrapper"
            show-arrows
        >
            <v-tab
                v-for="meta in tabs"
                :key="meta.key"
                :value="meta.key"
                class="text-none font-weight-semibold"
            >
                <template #prepend>
                    <v-icon :icon="meta.icon" size="18" />
                </template>
                {{ meta.label }}
            </v-tab>
        </v-tabs>

        <v-divider />

        <v-tabs-window v-model="tab">
            <v-tabs-window-item value="rack">
                <rack-list-table
                    v-if="tab === 'rack'"
                    @open-detail-dialog="handleOpenDetailDialog"
                />
            </v-tabs-window-item>
            <v-tabs-window-item value="tank">
                <tank-list-table
                    v-if="tab === 'tank'"
                    @open-detail-dialog="handleOpenDetailDialog"
                />
            </v-tabs-window-item>
        </v-tabs-window>
    </v-card>

    <detail-list v-model="detailDialog" :items="detailData" />
</template>

<style scoped>
/* ════ Hero card ════ */
.hero-card {
    position: relative;
    overflow: hidden;
    color: #fff;
    background: transparent;
}
.hero-gradient {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(1200px 200px at 0% 0%, rgba(255, 255, 255, 0.18), transparent 60%),
        linear-gradient(135deg,
            rgb(var(--v-theme-primary)) 0%,
            color-mix(in srgb, rgb(var(--v-theme-primary)) 75%, #1a237e) 100%);
    z-index: 0;
}
.hero-content {
    position: relative;
    z-index: 1;
    padding: 20px 20px 18px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.hero-back {
    align-self: flex-start;
    background: rgba(255, 255, 255, 0.12) !important;
    backdrop-filter: blur(4px);
}
.hero-back:hover {
    background: rgba(255, 255, 255, 0.22) !important;
}

.hero-main {
    display: flex;
    align-items: flex-start;
    gap: 14px;
}
.hero-icon {
    width: 52px;
    height: 52px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.18);
    color: #fff;
    backdrop-filter: blur(4px);
}
.hero-text {
    flex: 1;
    min-width: 0;
}
.hero-label {
    font-size: 0.7rem;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    font-weight: 500;
    opacity: 0.85;
}
.hero-title {
    font-size: clamp(1.15rem, 2.2vw, 1.5rem);
    font-weight: 700;
    line-height: 1.2;
    margin: 2px 0 6px;
    word-break: break-word;
}
.hero-meta {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.85rem;
    opacity: 0.9;
    background: rgba(255, 255, 255, 0.12);
    padding: 4px 10px;
    border-radius: 999px;
    max-width: 100%;
    word-break: break-word;
}
/* Placeholder loading — shimmer pulse tanpa dependency labs component. */
.hero-skeleton {
    height: 26px;
    max-width: 260px;
    margin: 2px 0 6px;
    border-radius: 6px;
    background:
        linear-gradient(
            90deg,
            rgba(255, 255, 255, 0.14) 0%,
            rgba(255, 255, 255, 0.28) 50%,
            rgba(255, 255, 255, 0.14) 100%
        );
    background-size: 200% 100%;
    animation: hero-shimmer 1.4s ease-in-out infinite;
}
.hero-skeleton--sub {
    height: 16px;
    max-width: 180px;
    margin-top: 6px;
}
@keyframes hero-shimmer {
    0%   { background-position: 100% 0; }
    100% { background-position: -100% 0; }
}

.hero-info {
    display: flex;
    align-items: flex-start;
    gap: 6px;
    font-size: 0.8rem;
    line-height: 1.5;
    opacity: 0.9;
    border-top: 1px solid rgba(255, 255, 255, 0.18);
    padding-top: 10px;
    margin-top: 4px;
}

/* ════ Tabs ════ */
.tabs-wrapper :deep(.v-tab) {
    min-height: 44px;
    border-radius: 10px 10px 0 0;
}

/* ════ Responsive ════ */
@media (max-width: 600px) {
    .hero-content {
        padding: 16px;
        gap: 12px;
    }
    .hero-main {
        gap: 10px;
    }
    .hero-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
    }
    .hero-meta {
        display: flex;
        width: fit-content;
    }
}
</style>
