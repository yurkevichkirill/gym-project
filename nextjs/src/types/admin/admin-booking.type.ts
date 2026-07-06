import type PaymentType from "@/types/payment/payment.type";
import { BookingStatusEnum } from "@/types/booking/bookings-status.enum";

export interface AdminBooking {
    id: number;
    clientId: number;
    trainerId: number;
    bookedAt: string;
    date: string;
    durationMinutes: number;
    startTime: string;
    status: BookingStatusEnum;
    payment: PaymentType;
}

export interface AdminBookingsGetQueryParams {
    trainerId?: number;
    clientId?: number;
    status?: BookingStatusEnum;
    date?: string;
    startTime?: string;
    durationMinutes?: number;
    sort?: string;
    page?: number;
    limit?: number;
}

export interface AdminBookingCreateRequest {
    trainerId: number;
    durationMinutes: number;
    startTime: string;
    date: string;
}

