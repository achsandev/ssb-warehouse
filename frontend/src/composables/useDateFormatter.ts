import { computed, ref } from 'vue';

export const useDateFormatter = (value?: string | Date) => {
    const initialDate = ref(value ?? new Date())

    const formattedDate = computed(() => {
        if (!initialDate.value) return ''

        const date = new Date(initialDate.value)
        return date.toISOString().split('T')[0]
    })

    return { initialDate, formattedDate }
}