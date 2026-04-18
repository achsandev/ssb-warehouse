import { array, date, number, object, string } from "yup";

export const receiveItemSchema = (t: Function) => object({
    purchase_order_id: string()
        .required(
            t('fieldRequired', {
                field: t('purchaseOrder')
            })
        ),
    receipt_date: date()
        .required(
            t('fieldRequired', {
                field: t('receiptDate')
            })
        ),
    warehouse_id: string()
        .required(
            t('fieldRequired', {
                field: t('warehouse')
            })
        ),
    shipping_cost: number()
        .transform((v, o) => (o === '' || o === null || o === undefined ? 0 : v))
        .min(0, t('fieldMustBeGreaterThan', { field: t('shippingCost'), value: -1 })),
    details: array().of(object().shape({
        item_id: string(),
        unit_id: string(),
        supplier_id: string(),
        qty: number()
            .required(
                t('fieldRequired', {
                    field: t('quantity')
                })
            ),
        price: number()
            .required(
                t('fieldRequired', {
                    field: t('price')
                })
            )
            .moreThan(0, t('fieldMustBeGreaterThan', { field: t('price'), value: 0 })),
        qty_received: number()
            .required(
                t('fieldRequired', {
                    field: t('qtyReceived')
                })
            )
            .moreThan(-1, t('fieldMustBeGreaterThan', { field: t('qtyReceived'), value: -1 })),
        total: number()
    })).required().min(1, t('fieldMinItem', { field: t('items'), length: 1 }))
})