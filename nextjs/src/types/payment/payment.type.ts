import TrainerData from "@/types/trainer/public/trainer.type";
import { PaymentStatusEnum } from "./payment-status.enum";
import { PaymentCategoryEnum } from "./payment-category.enum";
import { PaymentMethodEnum } from "./payment-method.enum";

export default interface PaymentType {
    id: number,
    amount: number,
    currency: string,
    method: PaymentMethodEnum,
    category: PaymentCategoryEnum,
    stripePaymentIntentId: string,
    status: PaymentStatusEnum,
    isRefund: boolean,
    createdAt: string,
    paidAt: string | null,
    expiresAt: string | null,
    trainer: TrainerData | null,
    originalPayment: PaymentType | null,
}