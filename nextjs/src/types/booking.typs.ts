export default interface BookingType {
    id: number,
    trainerId: number,
    bookedAt: string,
    date: string,
    durationMinutes: number,
    startTime: string,
    status: string,
}