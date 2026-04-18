type StockList = {
    uid: string,
    barcode: string | null,
    item: { uid: string, name: string } | null,
    unit: { uid: string, name: string, symbol: string } | null,
    warehouse: { uid: string, name: string } | null,
    rack: { uid: string, name: string } | null,
    tank: { uid: string, name: string } | null,
    stock_units: { uid: string, qty: number, unit_uid: string, unit_name: string, unit_symbol: string }[] | null,
    created_at: string,
    updated_at: string
    created_by_name: string,
    updated_by_name: string
}

type StockForm = {
    item_uid: string,
    warehouse_uid: string,
    rack_uid: string | null,
    tank_uid: string | null,
    unit_uid: string | null,
    qty: number | null,
}

type ConversionForm = {
    item_name?: string,
    current_qty?: any[],
    stock_uid: string | null,
    base_unit_uid: string | null,
    derived_unit_uid: string | null,
    convert_qty: number,
    converted_qty: number
}