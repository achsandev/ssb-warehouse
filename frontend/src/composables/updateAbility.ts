import { ability } from "@/plugins/casl"
import { Ability, AbilityBuilder } from "@casl/ability"

export const updateAbility = (permissions: any[]) => {
    const { can, rules } = new AbilityBuilder(Ability)

    permissions.map(permission => {
        can(permission.action, permission.subject)
    })

    ability.update(rules)
}