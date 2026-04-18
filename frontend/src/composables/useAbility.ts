import { ability } from '@/plugins/casl'

export function useAbility() {
    const can = (action: string, subject: string) => {
        return ability.can(action, subject)
    }
    return { can, ability }
}

export { ability }
