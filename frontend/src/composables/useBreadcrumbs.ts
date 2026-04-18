import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
// Import Composables
import { useTranslate } from '@/composables/useTranslate'
// Import Stores
import { useWarehouseStore } from '@/stores/warehouse'

export const useBreadcrumbs = () => {
    const t = useTranslate()
    const route = useRoute()
    const router = useRouter()
    const warehouseStore = useWarehouseStore()

    const breadcrumbs = computed(() => {
        const crumbs: any[] = []

        if (route.name !== 'Warehouse' && route.name !== 'RackAndTank') {
            warehouseStore.clearCurrentWarehouse()
        }

        const matched = route.matched.filter(
            (r) => typeof r.meta.title === 'string' && r.meta.title.trim().length > 0
        )

        matched.forEach((breadcrumb) => {
            if (breadcrumb.name === 'Warehouse' && warehouseStore.currentWarehouse) {
                crumbs.push({
                    title: warehouseStore.currentWarehouse.name,
                    disabled: route.name === 'Warehouse',
                    to: route.name !== 'Warehouse'
                        ? router.resolve({ name: 'Warehouse' }).href
                        : undefined,
                })
                return
            }

            // Jika route shelves, tambahkan parent warehouse (hanya 1x)
            if (breadcrumb.name === 'RackAndTank' && warehouseStore.currentWarehouse) {
                // Pastikan warehouse muncul dulu
                if (!crumbs.some((c) => c.title === warehouseStore.currentWarehouse?.name)) {
                    crumbs.push({
                        title: warehouseStore.currentWarehouse.name,
                        disabled: false,
                        to: router.resolve({ name: 'Warehouse' }).href,
                    })
                }

                // Lalu tambahkan shelves
                crumbs.push({
                    title: t(breadcrumb.meta.title as string) || String(breadcrumb.name),
                    disabled: breadcrumb.name === route.name,
                    to: breadcrumb.name !== route.name
                        ? router.resolve({
                                name: breadcrumb.name,
                                params: { uid: warehouseStore.currentWarehouse.uid },
                            }).href
                        : undefined,
                })
                return
            }
            
            // Default (semua route biasa)
            crumbs.push({
                title: t(breadcrumb.meta.title as string) || String(breadcrumb.name),
                disabled: breadcrumb.name === route.name,
                to: breadcrumb.name !== route.name
                    ? router.resolve({ name: breadcrumb.name, params: route.params }).href
                    : undefined,
            })
        })

        return crumbs
    })

    return { breadcrumbs }
}