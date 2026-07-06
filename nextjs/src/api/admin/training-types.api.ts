import { apiDelete, apiPatch, apiPost, apiPostFormData } from "@/lib/apiClient";
import { ApiItemResponse } from "@/types/api-item-response.type";
import {
    getTrainingType,
    getTrainingTypesPage,
    getTrainingTypesRequestKey,
    parseTrainingTypesListParams,
} from "@/api/public/training-types.api";
import {
    AdminTrainingType,
    AdminTrainingTypeCreateRequest,
    AdminTrainingTypeUpdateRequest,
} from "@/types/admin/admin-training-type.type";

export {
    getTrainingType as getAdminTrainingType,
    getTrainingTypesPage as getAdminTrainingTypes,
    getTrainingTypesRequestKey as getAdminTrainingTypesRequestKey,
    parseTrainingTypesListParams as parseAdminTrainingTypesListParams,
};

export const createAdminTrainingType = async (
    payload: AdminTrainingTypeCreateRequest,
): Promise<AdminTrainingType> => {
    const response = await apiPost<ApiItemResponse<AdminTrainingType>, AdminTrainingTypeCreateRequest>(
        "/training/types/",
        payload,
    );

    return response.data;
};

export const updateAdminTrainingType = async (
    id: number,
    payload: AdminTrainingTypeUpdateRequest,
): Promise<AdminTrainingType> => {
    const response = await apiPatch<ApiItemResponse<AdminTrainingType>, AdminTrainingTypeUpdateRequest>(
        `/training/types/${id}/`,
        payload,
    );

    return response.data;
};

export const uploadAdminTrainingTypePhoto = async (id: number, photo: File): Promise<AdminTrainingType> => {
    const body = new FormData();
    body.set("photo", photo);

    const response = await apiPostFormData<ApiItemResponse<AdminTrainingType>>(`/training/types/${id}/photo/`, body);

    return response.data;
};

export const deleteAdminTrainingType = async (id: number): Promise<void> => {
    await apiDelete<null>(`/training/types/${id}/`);
};

void getTrainingType;
void getTrainingTypesPage;
void getTrainingTypesRequestKey;
void parseTrainingTypesListParams;

