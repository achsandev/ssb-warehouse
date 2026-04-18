export const numberOnly = (event: KeyboardEvent) => {
    const key = event.key
    const allowedKeys = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab']

    if (!/[0-9]/.test(key) && !allowedKeys.includes(key)) {
        event.preventDefault()
    }
}