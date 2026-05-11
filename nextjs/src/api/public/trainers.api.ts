import TrainerData from "@/types/trainer/public/trainer.type";
import {ApiCollectionResponse} from "@/types/api-collection-response";
import {ApiItemResponse} from "@/types/api-item-response.type";

export const getTrainers = async (): Promise<TrainerData[]> => {
    const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/trainers/?limit=21`);
    if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
    }
    const obj: ApiCollectionResponse<TrainerData[]> = await response.json();

    return obj.data;
}

export const getTrainer = async (id: string):Promise<TrainerData> => {
    const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/trainers/${id}/`);
    if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
    }
    const obj: ApiItemResponse<TrainerData> = await response.json();

    return obj.data;
}