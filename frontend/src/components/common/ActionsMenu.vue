<script lang="ts" setup>
// Import Icons
import MajesticonsMoreMenu from '~icons/majesticons/more-menu'
import MaterialSymbolsLightListAlt from '~icons/material-symbols-light/list-alt'
import DashiconsEdit from '~icons/dashicons/edit'
import WeuiDeleteFilled from '~icons/weui/delete-filled'
import MaterialSymbolsLightCalculateRounded from '~icons/material-symbols-light/calculate-rounded'
import FluentTextChangeReject20Regular from '~icons/fluent/text-change-reject-20-regular'
import SolarListCheckBold from '~icons/solar/list-check-bold'
import FluentNotepad24Filled from '~icons/fluent/notepad-24-filled'
import MdiWarehouse from '~icons/mdi/warehouse'
// Import Composables
import { useTranslate } from '@/composables/useTranslate'

const t = useTranslate()

/**
 * Bentuk nilai ikon yang dipakai VIcon/VListItem (`IconValue` milik Vuetify
 * tidak di-reexport public dari root `'vuetify'`). Diturunkan dari salah satu
 * komponen unplugin-icons — semua icon dari loader itu punya shape identik,
 * sehingga kompatibel sebagai `JSXComponent` yang VIcon terima.
 */
type IconLike = typeof MaterialSymbolsLightListAlt | string

const props = defineProps<{
    menus: string[],
    data: any
}>()

const emit = defineEmits<{
    (e: 'click', action: string, data: any): void
}>()

/**
 * Peta aksi → ikon. Memisahkan resolusi ikon dari template menghindari
 * ternary berantai yang sulit dibaca dan memudahkan penambahan aksi baru.
 * Aksi yang tidak terdaftar jatuh ke ikon kalkulator default.
 */
const ACTION_ICON: Record<string, IconLike> = {
    detail:           MaterialSymbolsLightListAlt,
    update:           DashiconsEdit,
    delete:           WeuiDeleteFilled,
    approve:          SolarListCheckBold,
    reject:           FluentTextChangeReject20Regular,
    revision:         FluentNotepad24Filled,
    manage_rack_tank: MdiWarehouse,
}

const iconFor = (action: string): IconLike =>
    ACTION_ICON[action] ?? MaterialSymbolsLightCalculateRounded

const handleClick = (action: string) => {
    emit('click', action, props.data)
}
</script>

<template>
    <v-menu>
        <template v-slot:activator="{ props }">
            <v-btn :icon="MajesticonsMoreMenu" variant="tonal" density="compact" v-bind="props" />
        </template>
        <v-list class="rounded-lg py-0" density="compact" slim>
            <v-list-item
                v-for="(action, i) in props.menus"
                link
                :key="i"
                :append-icon="iconFor(action)"
                @click="handleClick(action)"
            >
                <v-list-item-title class="text-overline">{{ t(action) }}</v-list-item-title>
            </v-list-item>
        </v-list>
    </v-menu>
</template>

<style scoped></style>