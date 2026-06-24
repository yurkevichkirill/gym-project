import TrainerData from "@/types/trainer/public/trainer.type";
import { ApiCollectionResponse } from "@/types/api-collection-response";
import { ApiItemResponse } from "@/types/api-item-response.type";
import { publicApiGet } from "@/lib/publicApiClient";

export const getTrainers = async (): Promise<TrainerData[]> => {
    const response = await publicApiGet<ApiCollectionResponse<TrainerData[]>>('/trainers/?limit=21');

    return response.data;
};

export const getTrainer = async (id: string): Promise<TrainerData> => {
    const response = await publicApiGet<ApiItemResponse<TrainerData>>(`/trainers/${id}/`);

    return response.data;
};
