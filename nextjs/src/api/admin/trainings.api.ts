import { apiGet, apiPatch, apiPost } from "@/lib/apiClient";
import { ApiCollectionResponse } from "@/types/api-collection-response";
import { ApiItemResponse } from "@/types/api-item-response.type";
import { BookingStatusEnum } from "@/types/booking/bookings-status.enum";
import {
    AdminTraining,
    AdminTrainingsGetQueryParams,
    AdminTrainingUpdateRequest,
} from "@/types/admin/admin-training.type";
import {
    createAdminSearchParams,
    getAdminRequestKey,
    readBoolean,
    readDate,
    readEnum,
    readPositiveInteger,
    readSort,
    readTime,
} from "@/api/admin/admin-api-utils";
import type { SearchParamsReader } from "@/types/admin/admin-common.type";

export const DEFAULT_ADMIN_TRAININGS_SORT = "startTime:ASC";
const SORT_FIELDS = ["startTime", "durationMinutes", "clientId", "date", "status", "bookedAt", "isBusy"] as const;
const STATUS_VALUES = Object.values(BookingStatusEnum);

export type AdminTrainingsListResponse = ApiCollectionResponse<AdminTraining[]>;

export const parseAdminTrainingsListParams = (searchParams: SearchParamsReader): AdminTrainingsGetQueryParams => ({
    trainerId: readPositiveInteger(searchParams.get("trainerId")),
    clientId: readPositiveInteger(searchParams.get("clientId")),
    status: readEnum(searchParams.get("status"), STATUS_VALUES),
    date: readDate(searchParams.get("date")),
    startTime: readTime(searchParams.get("startTime")),
    durationMinutes: readPositiveInteger(searchParams.get("durationMinutes")),
    isBusy: readBoolean(searchParams.get("isBusy")),
    sort: readSort(searchParams, SORT_FIELDS),
    page: readPositiveInteger(searchParams.get("page")),
    limit: readPositiveInteger(searchParams.get("limit"), 100),
});

export const getAdminTrainingsRequestKey = (params: AdminTrainingsGetQueryParams = {}): string => (
    getAdminRequestKey(params)
);

export const getAdminTrainings = async (
    params: AdminTrainingsGetQueryParams = {},
): Promise<AdminTrainingsListResponse> => {
    const queryString = createAdminSearchParams(params).toString();

    return await apiGet<AdminTrainingsListResponse>(`/admin/trainings/${queryString ? `?${queryString}` : ""}`);
};

export const updateAdminTraining = async (
    id: number,
    payload: AdminTrainingUpdateRequest,
): Promise<AdminTraining> => {
    const response = await apiPatch<ApiItemResponse<AdminTraining>, AdminTrainingUpdateRequest>(
        `/admin/trainings/${id}/`,
        payload,
    );

    return response.data;
};

export const cancelAdminTraining = async (id: number): Promise<AdminTraining> => {
    const response = await apiPost<ApiItemResponse<AdminTraining>>(`/admin/trainings/${id}/cancel/`);

    return response.data;
};

export const completeAdminTraining = async (id: number): Promise<AdminTraining> => {
    const response = await apiPost<ApiItemResponse<AdminTraining>>(`/admin/trainings/${id}/complete/`);

    return response.data;
};

