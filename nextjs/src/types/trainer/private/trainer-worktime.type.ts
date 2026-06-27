import { ApiCollectionResponse } from "@/types/api-collection-response";
import WorktimeData from "@/types/trainer/public/worktime.type";

export interface TrainerWorktimesGetParams {
    date?: string;
    sort?: string;
    page?: number;
    limit?: number;
}

export interface TrainerWorktimeCreatePayload {
    startTime: string;
    endTime: string;
    date: string;
}

export interface TrainerWorktimeUpdatePayload {
    startTime?: string;
    endTime?: string;
}

export type TrainerWorktimesListResponse = ApiCollectionResponse<WorktimeData[]>;
