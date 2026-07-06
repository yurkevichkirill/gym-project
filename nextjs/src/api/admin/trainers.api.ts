import { apiDelete, apiGet, apiPatch, apiPost, apiPostFormData } from "@/lib/apiClient";
import { ApiCollectionResponse } from "@/types/api-collection-response";
import { ApiItemResponse } from "@/types/api-item-response.type";
import {
    AdminTrainer,
    AdminTrainerCreateRequest,
    AdminTrainersGetQueryParams,
    AdminTrainerUpdateRequest,
} from "@/types/admin/admin-trainer.type";
import {
    createAdminSearchParams,
    getAdminRequestKey,
    readBoolean,
    readNonNegativeInteger,
    readPositiveInteger,
    readSort,
} from "@/api/admin/admin-api-utils";
import type { SearchParamsReader } from "@/types/admin/admin-common.type";

export const DEFAULT_ADMIN_TRAINERS_SORT = "lastName:ASC";
const SORT_FIELDS = ["pricePerHour", "firstName", "lastName", "trainingTypeId", "balance"] as const;

export type AdminTrainersListResponse = ApiCollectionResponse<AdminTrainer[]>;

export const parseAdminTrainersListParams = (searchParams: SearchParamsReader): AdminTrainersGetQueryParams => ({
    minPricePerHour: readNonNegativeInteger(searchParams.get("minPricePerHour")),
    maxPricePerHour: readNonNegativeInteger(searchParams.get("maxPricePerHour")),
    trainingTypeId: readPositiveInteger(searchParams.get("trainingTypeId")),
    minBalance: readNonNegativeInteger(searchParams.get("minBalance")),
    maxBalance: readNonNegativeInteger(searchParams.get("maxBalance")),
    isDeleted: readBoolean(searchParams.get("isDeleted")),
    isBlocked: readBoolean(searchParams.get("isBlocked")),
    sort: readSort(searchParams, SORT_FIELDS),
    page: readPositiveInteger(searchParams.get("page")),
    limit: readPositiveInteger(searchParams.get("limit"), 100),
});

export const getAdminTrainersRequestKey = (params: AdminTrainersGetQueryParams = {}): string => (
    getAdminRequestKey(params)
);

export const getAdminTrainers = async (
    params: AdminTrainersGetQueryParams = {},
): Promise<AdminTrainersListResponse> => {
    const queryString = createAdminSearchParams(params).toString();

    return await apiGet<AdminTrainersListResponse>(`/admin/trainers/${queryString ? `?${queryString}` : ""}`);
};

export const getAdminTrainer = async (id: number): Promise<AdminTrainer> => {
    const response = await apiGet<ApiItemResponse<AdminTrainer>>(`/admin/trainers/${id}/`);

    return response.data;
};

export const createAdminTrainer = async (payload: AdminTrainerCreateRequest): Promise<AdminTrainer> => {
    const response = await apiPost<ApiItemResponse<AdminTrainer>, AdminTrainerCreateRequest>("/trainers/", payload);

    return response.data;
};

export const updateAdminTrainer = async (
    id: number,
    payload: AdminTrainerUpdateRequest,
): Promise<AdminTrainer> => {
    const response = await apiPatch<ApiItemResponse<AdminTrainer>, AdminTrainerUpdateRequest>(`/trainers/${id}/`, payload);

    return response.data;
};

export const uploadAdminTrainerPhoto = async (id: number, photo: File): Promise<AdminTrainer> => {
    const body = new FormData();
    body.set("photo", photo);

    const response = await apiPostFormData<ApiItemResponse<AdminTrainer>>(`/trainers/${id}/photo/`, body);

    return response.data;
};

export const deleteAdminTrainer = async (id: number): Promise<void> => {
    await apiDelete<null>(`/trainers/${id}/`);
};

export const restoreAdminTrainer = async (id: number): Promise<AdminTrainer> => {
    const response = await apiPost<ApiItemResponse<AdminTrainer>>(`/trainers/${id}/restore/`);

    return response.data;
};

export const blockAdminTrainer = async (id: number): Promise<AdminTrainer> => {
    const response = await apiPost<ApiItemResponse<AdminTrainer>>(`/trainers/${id}/block/`);

    return response.data;
};

export const unblockAdminTrainer = async (id: number): Promise<AdminTrainer> => {
    const response = await apiPost<ApiItemResponse<AdminTrainer>>(`/trainers/${id}/unblock/`);

    return response.data;
};

