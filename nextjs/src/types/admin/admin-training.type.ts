import type { BookingStatusEnum } from "@/types/booking/bookings-status.enum";
import type PaymentType from "@/types/payment/payment.type";

export interface AdminTraining {
    id: number;
    startTime: string;
    durationMinutes: number;
    date: string;
    isBusy: boolean;
    clientId: number;
    bookedAt: string;
    status: BookingStatusEnum;
    payment: PaymentType;
}

export interface AdminTrainingsGetQueryParams {
    clientId?: number;
    trainerId?: number;
    status?: BookingStatusEnum;
    date?: string;
    startTime?: string;
    durationMinutes?: number;
    isBusy?: boolean;
    sort?: string;
    page?: number;
    limit?: number;
}

export interface AdminTrainingUpdateRequest {
    startTime?: string;
    date?: string;
}

