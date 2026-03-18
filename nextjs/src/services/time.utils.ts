export function generateStartTimes(start: string, end: string) {
    const result: string[] = []

    let current = new Date(`1970-01-01T${start}`)
    const endDate = new Date(`1970-01-01T${end}`)

    while (true) {
        const minEnd = new Date(current)
        minEnd.setMinutes(minEnd.getMinutes() + 60)

        if (minEnd > endDate) break

        result.push(current.toTimeString().slice(0,5))
        current.setMinutes(current.getMinutes() + 30)
    }

    return result
}

export function generateEndTimes(end: string, selectedStart: string) {
    const result: string[] = []

    let current = new Date(`1970-01-01T${selectedStart}`)
    current.setMinutes(current.getMinutes() + 60)

    const endDate = new Date(`1970-01-01T${end}`)

    while (current <= endDate) {
        result.push(current.toTimeString().slice(0,5))
        current.setMinutes(current.getMinutes() + 30)
    }

    return result
}