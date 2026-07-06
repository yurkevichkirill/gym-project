import type TrainingTypeData from "@/types/training-type.type";

export interface AdminTrainer {
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
    deletedAt: string | null;
    updatedAt: string;
    blockedAt: string | null;
    type: string;
}

export interface AdminTrainersGetQueryParams {
    minPricePerHour?: number;
    maxPricePerHour?: number;
    trainingTypeId?: number;
    minBalance?: number;
    maxBalance?: number;
    isDeleted?: boolean;
    isBlocked?: boolean;
    sort?: string;
    page?: number;
    limit?: number;
}

export interface AdminTrainerCreateRequest {
    firstName: string;
    lastName: string;
    email: string;
    phone: string;
    password: string;
    trainingTypeId: number;
    pricePerHour: number;
    education?: string | null;
    about?: string | null;
}

export interface AdminTrainerUpdateRequest {
    firstName?: string;
    lastName?: string;
    email?: string;
    phone?: string;
    password?: string;
    pricePerHour?: number;
    education?: string | null;
    about?: string | null;
}

