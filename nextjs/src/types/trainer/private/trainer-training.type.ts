import { ApiCollectionResponse } from "@/types/api-collection-response";
import { BookingStatusEnum } from "@/types/booking/bookings-status.enum";
import { PaymentCategoryEnum } from "@/types/payment/payment-category.enum";
import { PaymentMethodEnum } from "@/types/payment/payment-method.enum";
import { PaymentStatusEnum } from "@/types/payment/payment-status.enum";

export interface TrainerPaymentType {
    id: number;
    amount: number;
    currency: string;
    method: PaymentMethodEnum;
    category: PaymentCategoryEnum;
    stripePaymentIntentId: string | null;
    status: PaymentStatusEnum;
    isRefund: boolean;
    createdAt: string;
    paidAt: string | null;
    expiresAt: string | null;
    originalPayment: TrainerPaymentType | null;
}

export interface TrainerTrainingType {
    id: number;
    startTime: string;
    durationMinutes: number;
    date: string;
    isBusy: boolean;
    clientId: number;
    bookedAt: string;
    status: BookingStatusEnum;
    payment: TrainerPaymentType;
}

export interface TrainerTrainingsGetParams {
    clientId?: number;
    status?: BookingStatusEnum;
    date?: string;
    startTime?: string;
    durationMinutes?: number;
    isBusy?: boolean;
    sort?: string;
    page?: number;
    limit?: number;
}

export interface TrainerTrainingUpdatePayload {
    startTime?: string;
    date?: string;
}

export type TrainerTrainingsListResponse = ApiCollectionResponse<TrainerTrainingType[]>;
