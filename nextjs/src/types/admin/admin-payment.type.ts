import type PaymentType from "@/types/payment/payment.type";
import { PaymentStatusEnum } from "@/types/payment/payment-status.enum";

export type AdminPayment = PaymentType;

export interface AdminPaymentsGetQueryParams {
    trainerId?: number;
    clientId?: number;
    minAmount?: number;
    maxAmount?: number;
    isRefund?: boolean;
    status?: PaymentStatusEnum;
    minCreatedAt?: string;
    maxCreatedAt?: string;
    sort?: string;
    page?: number;
    limit?: number;
}

