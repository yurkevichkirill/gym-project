import {
    apiDelete,
    apiGet,
    apiPatch,
    apiPostFormData,
} from "@/lib/apiClient";
import { ApiItemResponse } from "@/types/api-item-response.type";
import TrainerEditType from "@/types/trainer/private/trainer-edit.type";
import { TrainerPersonalType } from "@/types/trainer/private/trainer.personal.type";

export const getCurrentTrainer = async (): Promise<TrainerPersonalType> => {
    const response = await apiGet<ApiItemResponse<TrainerPersonalType>>("/trainer/me/");

    return response.data;
};

export const updateCurrentTrainer = async (
    payload: TrainerEditType,
): Promise<TrainerPersonalType> => {
    const response = await apiPatch<ApiItemResponse<TrainerPersonalType>, TrainerEditType>(
        "/trainer/me/",
        payload,
    );

    return response.data;
};

export const uploadCurrentTrainerPhoto = async (
    photo: File,
): Promise<TrainerPersonalType> => {
    const formData = new FormData();
    formData.append("photo", photo);

    const response = await apiPostFormData<ApiItemResponse<TrainerPersonalType>>(
        "/trainer/me/photo/",
        formData,
    );

    return response.data;
};

export const deleteCurrentTrainer = async (): Promise<void> => {
    await apiDelete<null>("/trainer/me/");
};
