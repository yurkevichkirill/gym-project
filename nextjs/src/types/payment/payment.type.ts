import TrainerData from "@/types/trainer/public/trainer.type";

export default interface PaymentType {
    id: number,
    amount: number,
    currency: string,
    method: string,
    category: string,
    stripePaymentIntentId: string,
    status: string,
    isRefund: boolean,
    createAt: string,
    paidAt: string | null,
    expiresAt: string | null,
    trainer: TrainerData | null,
}