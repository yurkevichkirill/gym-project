import WorktimeData from "@/types/trainer/public/worktime.type";
import { ApiCollectionResponse } from "@/types/api-collection-response";
import { GetWorktimesType } from "@/types/worktime/worktimes-get.type";
import { publicApiGet } from "@/lib/publicApiClient";

export const getWorktimes = async (params: GetWorktimesType = {}): Promise<WorktimeData[]> => {
    const searchParams = new URLSearchParams();

    Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null) {
            searchParams.append(key, value.toString());
        }
    });

    const queryString = searchParams.toString();
    const response = await publicApiGet<ApiCollectionResponse<WorktimeData[]>>(
        `/worktime/${queryString ? `?${queryString}` : ""}`,
    );

    return response.data;
};
