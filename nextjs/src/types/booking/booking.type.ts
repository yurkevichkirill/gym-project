import PaymentType from "@/types/payment/payment.type";
import { BookingStatusEnum } from "./bookings-status.enum";

export default interface BookingType {
    id: number,
    trainerId: number,
    bookedAt: string,
    date: string,
    durationMinutes: number,
    startTime: string,
    status: BookingStatusEnum,
    payment: PaymentType,
}