import WorktimeData from "@/types/trainer/public/worktime.type";
import { ApiCollectionResponse } from "@/types/api-collection-response";
import { ApiItemResponse } from "@/types/api-item-response.type";
import { GetWorktimesType } from "@/types/worktime/worktimes-get.type";
import { publicApiGet } from "@/lib/publicApiClient";

export const DEFAULT_WORKTIMES_SORT = "date:ASC";

const WORKTIME_SORT_FIELDS = new Set(["date", "startTime", "endTime"]);
const SORT_ORDERS = new Set(["ASC", "DESC"]);

export type WorktimesListResponse = ApiCollectionResponse<WorktimeData[]>;

type SearchParamsReader = {
    get: (name: string) => string | null;
};

type PublicRequestOptions = {
    signal?: AbortSignal;
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

export const parseWorktimesListParams = (
    searchParams: SearchParamsReader,
): GetWorktimesType => {
    const date = searchParams.get("date");
    const sort = searchParams.get("sort");

    return {
        trainerId: readPositiveInteger(searchParams.get("trainerId")),
        date: date && isBackendDate(date) ? date : undefined,
        sort: sort && isValidSort(sort) ? sort : undefined,
        page: readPositiveInteger(searchParams.get("page")),
        limit: readPositiveInteger(searchParams.get("limit"), 100),
    };
};

const createWorktimesSearchParams = (params: GetWorktimesType): URLSearchParams => {
    const searchParams = new URLSearchParams();

    Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined) {
            searchParams.set(key, value.toString());
        }
    });

    return searchParams;
};

export const getWorktimesRequestKey = (params: GetWorktimesType): string => {
    return createWorktimesSearchParams(params).toString();
};

export const getWorktimesPage = async (
    params: GetWorktimesType = {},
    options: PublicRequestOptions = {},
): Promise<WorktimesListResponse> => {
    const queryString = getWorktimesRequestKey(params);

    return await publicApiGet<WorktimesListResponse>(
        `/worktime/${queryString ? `?${queryString}` : ""}`,
        { signal: options.signal },
    );
};

export const getWorktimes = async (
    params: GetWorktimesType = {},
    options: PublicRequestOptions = {},
): Promise<WorktimeData[]> => {
    const response = await getWorktimesPage(params, options);

    return response.data;
};

export const getWorktime = async (
    id: string,
    options: PublicRequestOptions = {},
): Promise<WorktimeData> => {
    const response = await publicApiGet<ApiItemResponse<WorktimeData>>(
        `/worktime/${id}/`,
        { signal: options.signal },
    );

    return response.data;
};
