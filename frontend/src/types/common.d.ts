type SortByItem = {
    key: string,
    order: 'asc' | 'desc'
}

type TableParams = {
    page: number,
    itemsPerPage: number,
    sortBy: SortByItem[],
    search: string
}

type SelectItem = {
    type?: string,
    text?: string,
    title: string,
    value: string | number
}

type KeyValueItem = {
    key: string,
    value: string | number | Array
}