import {
    apiDelete,
    apiGet,
    apiPatch,
    apiPost,
} from "@/lib/apiClient";
import { ApiItemResponse } from "@/types/api-item-response.type";
import WorktimeData from "@/types/trainer/public/worktime.type";
import {
    TrainerWorktimeCreatePayload,
    TrainerWorktimesGetParams,
    TrainerWorktimesListResponse,
    TrainerWorktimeUpdatePayload,
} from "@/types/trainer/private/trainer-worktime.type";

export const DEFAULT_TRAINER_WORKTIMES_SORT = "date:ASC";

const WORKTIME_SORT_FIELDS = new Set(["date", "startTime", "endTime"]);
const SORT_ORDERS = new Set(["ASC", "DESC"]);

type SearchParamsReader = {
    get: (name: string) => string | null;
};

const readPositiveInteger = (
    value: string | null,
    maximum?: number,
): number | undefined => {
    if (value === null || !/^\d+$/.test(value)) {
        return undefined;
    }

    const parsed = Number(value);

    if (
        !Number.isSafeInteger(parsed)
        || parsed <= 0
        || (maximum !== undefined && parsed > maximum)
    ) {
        return undefined;
    }

    return parsed;
};

const isBackendDate = (value: string): boolean => {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        return false;
    }

    const [year, month, day] = value.split("-").map(Number);
    const parsed = new Date(Date.UTC(year, month - 1, day));

    return parsed.getUTCFullYear() === year
        && parsed.getUTCMonth() === month - 1
        && parsed.getUTCDate() === day;
};

const isValidSort = (value: string): boolean => {
    return value.split(",").every((item) => {
        const parts = item.split(":");

        if (parts.length > 2) {
            return false;
        }

        const field = parts[0].trim();
        const order = (parts[1] ?? "ASC").trim().toUpperCase();

        return field.length > 0
            && WORKTIME_SORT_FIELDS.has(field)
            && SORT_ORDERS.has(order);
    });
};

const toBackendTime = (value: string): string => {
    return /^\d{2}:\d{2}$/.test(value) ? `${value}:00` : value;
};

export const parseTrainerWorktimesListParams = (
    searchParams: SearchParamsReader,
): TrainerWorktimesGetParams => {
    const date = searchParams.get("date");
    const sort = searchParams.get("sort");

    return {
        date: date && isBackendDate(date) ? date : undefined,
        sort: sort && isValidSort(sort) ? sort : undefined,
        page: readPositiveInteger(searchParams.get("page")),
        limit: readPositiveInteger(searchParams.get("limit"), 100),
    };
};

const createSearchParams = (
    params: TrainerWorktimesGetParams,
): URLSearchParams => {
    const searchParams = new URLSearchParams();

    Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined) {
            searchParams.set(key, value.toString());
        }
    });

    return searchParams;
};

export const getTrainerWorktimesRequestKey = (
    params: TrainerWorktimesGetParams,
): string => {
    return createSearchParams(params).toString();
};

export const getCurrentTrainerWorktimes = async (
    params: TrainerWorktimesGetParams = {},
): Promise<TrainerWorktimesListResponse> => {
    const queryString = getTrainerWorktimesRequestKey(params);

    return await apiGet<TrainerWorktimesListResponse>(
        `/trainer/me/worktime/${queryString ? `?${queryString}` : ""}`,
    );
};

export const createCurrentTrainerWorktime = async (
    payload: TrainerWorktimeCreatePayload,
): Promise<WorktimeData> => {
    const requestPayload: TrainerWorktimeCreatePayload = {
        ...payload,
        startTime: toBackendTime(payload.startTime),
        endTime: toBackendTime(payload.endTime),
    };
    const response = await apiPost<
        ApiItemResponse<WorktimeData>,
        TrainerWorktimeCreatePayload
    >("/trainer/me/worktime/", requestPayload);

    return response.data;
};

export const updateCurrentTrainerWorktime = async (
    id: number,
    payload: TrainerWorktimeUpdatePayload,
): Promise<WorktimeData> => {
    const requestPayload: TrainerWorktimeUpdatePayload = {
        ...(payload.startTime !== undefined
            ? { startTime: toBackendTime(payload.startTime) }
            : {}),
        ...(payload.endTime !== undefined
            ? { endTime: toBackendTime(payload.endTime) }
            : {}),
    };
    const response = await apiPatch<
        ApiItemResponse<WorktimeData>,
        TrainerWorktimeUpdatePayload
    >(`/worktime/${id}/`, requestPayload);

    return response.data;
};

export const deleteCurrentTrainerWorktime = async (id: number): Promise<void> => {
    await apiDelete<null>(`/worktime/${id}/`);
};
