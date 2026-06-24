import TrainingTypeData from "@/types/training-type.type";
import { ApiCollectionResponse } from "@/types/api-collection-response";
import { publicApiGet } from "@/lib/publicApiClient";

export const getTrainingTypes = async (): Promise<TrainingTypeData[]> => {
    const response = await publicApiGet<ApiCollectionResponse<TrainingTypeData[]>>('/training/types/');

    return response.data;
};
