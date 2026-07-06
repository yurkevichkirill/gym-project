import { apiDelete, apiPatch, apiPost } from "@/lib/apiClient";
import { ApiItemResponse } from "@/types/api-item-response.type";
import WorktimeData from "@/types/trainer/public/worktime.type";
import { getWorktimesPage, getWorktimesRequestKey, parseWorktimesListParams } from "@/api/public/worktime.api";
import {
    AdminWorktimeCreateRequest,
    AdminWorktimeUpdateRequest,
} from "@/types/admin/admin-worktime.type";

export { getWorktimesPage as getAdminWorktimes, getWorktimesRequestKey as getAdminWorktimesRequestKey, parseWorktimesListParams as parseAdminWorktimesListParams };

export const createAdminTrainerWorktime = async (
    trainerId: number,
    payload: AdminWorktimeCreateRequest,
): Promise<WorktimeData> => {
    const response = await apiPost<ApiItemResponse<WorktimeData>, AdminWorktimeCreateRequest>(
        `/trainers/${trainerId}/worktime/`,
        payload,
    );

    return response.data;
};

export const updateAdminWorktime = async (
    id: number,
    payload: AdminWorktimeUpdateRequest,
): Promise<WorktimeData> => {
    const response = await apiPatch<ApiItemResponse<WorktimeData>, AdminWorktimeUpdateRequest>(
        `/admin/worktime/${id}/`,
        payload,
    );

    return response.data;
};

export const deleteAdminWorktime = async (id: number): Promise<void> => {
    await apiDelete<null>(`/admin/worktime/${id}/`);
};

void getWorktimesPage;
void getWorktimesRequestKey;
void parseWorktimesListParams;

