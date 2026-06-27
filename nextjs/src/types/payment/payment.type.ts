import TrainerData from "@/types/trainer/public/trainer.type";
import { PaymentStatusEnum } from "@/types/payment/payment-status.enum";
import { PaymentCategoryEnum } from "@/types/payment/payment-category.enum";
import { PaymentMethodEnum } from "@/types/payment/payment-method.enum";

export default interface PaymentType {
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
    trainer: TrainerData | null;
    originalPayment: PaymentType | null;
}
