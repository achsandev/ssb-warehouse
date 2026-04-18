<script lang="ts" setup>
import { useRouter } from 'vue-router'
import type { PendingApproval } from '@/stores/dashboard'
import MdiArrowRight from '~icons/mdi/arrow-right'

defineProps<{ items: PendingApproval[] }>()

const router = useRouter()

const go = (route: string) => {
    if (route) router.push(route)
}
</script>

<template>
    <div class="approvals-list">
        <div
            v-for="(it, idx) in items"
            :key="idx"
            class="approval-row"
            :class="{ 'approval-row--clickable': it.count > 0 }"
            @click="it.count > 0 && go(it.route)"
        >
            <div class="grow min-w-0">
                <div class="text-body-2 font-weight-medium">{{ it.module }}</div>
                <div class="text-caption text-medium-emphasis">Menunggu persetujuan</div>
            </div>
            <v-chip
                :color="it.count > 0 ? 'warning' : 'grey'"
                :variant="it.count > 0 ? 'flat' : 'tonal'"
                size="small"
                class="font-weight-bold"
            >
                {{ it.count }}
            </v-chip>
            <v-icon v-if="it.count > 0" :icon="MdiArrowRight" size="18" class="ms-1 text-medium-emphasis" />
        </div>
    </div>
</template>

<style scoped>
.approvals-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.approval-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border: 1px solid rgba(var(--v-theme-on-surface), 0.06);
    border-radius: 8px;
    transition: background 0.15s ease, border-color 0.15s ease, transform 0.15s ease;
}
.approval-row--clickable {
    cursor: pointer;
}
.approval-row--clickable:hover {
    background: rgba(var(--v-theme-warning), 0.06);
    border-color: rgba(var(--v-theme-warning), 0.4);
    transform: translateX(2px);
}
</style>
