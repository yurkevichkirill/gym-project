import TrainerData from "@/types/trainer.type";

export default interface PaymentType {
    id: number,
    trainer: TrainerData | null,
    amount: string,
    category: string,
}