import {ApiResponse} from "@/types/api-response.type";
import TrainingTypeData from "@/types/training-type.type";

export const getTrainingTypes = async (): Promise<TrainingTypeData[]> => {
    const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/training/types/`);
    if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
    }
    const obj: ApiResponse<TrainingTypeData[]> = await response.json();

    return obj.data;
}