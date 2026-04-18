export const toPascalCase = (str: string): string => {
    return str
        .replace(/([-_ ][a-z])/gi, (group) => group.toUpperCase().replace('-', ' ').replace('_', ' '))
        .replace(/^[a-z]/, (group) => group.toUpperCase())
}

export const toCamelCase = (str: string): string => {
    return str
        .replace(/([-_ ][a-z])/gi, (group) => group.toUpperCase().replace('-', '').replace('_', '').replace(' ', ''))
        .replace(/^[A-Z]/, (group) => group.toLowerCase())
}