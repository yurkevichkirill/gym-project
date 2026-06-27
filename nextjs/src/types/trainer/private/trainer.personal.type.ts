import { UserTypes } from "@/types/auth.type";
import type TrainingTypeData from "@/types/training-type.type";

export interface TrainerPersonalType {
    id: number;
    firstName: string;
    lastName: string;
    phone: string;
    email: string;
    trainingType: TrainingTypeData;
    pricePerHour: number;
    photoPath: string | null;
    education: string | null;
    about: string | null;
    balance: number;
    debt: number;
    createdAt: string;
    deletedAt: string;
    updatedAt: string;
    blockedAt: string;
    type: typeof UserTypes.TRAINER;
}
