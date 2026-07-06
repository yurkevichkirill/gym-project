import type TrainingTypeData from "@/types/training-type.type";

export type AdminTrainingType = TrainingTypeData;

export interface AdminTrainingTypeCreateRequest {
    name: string;
    description: string;
}

export interface AdminTrainingTypeUpdateRequest {
    name?: string;
    description?: string;
}

