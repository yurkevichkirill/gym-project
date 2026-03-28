import {ApiResponse} from "@/types/api-response.type";
import TrainerData from "@/types/trainer.type";

export const getTrainers = async (): Promise<TrainerData[]> => {
    const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/trainers/`);
    if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
    }
    const obj: ApiResponse<TrainerData[]> = await response.json();

    return obj.data;
}