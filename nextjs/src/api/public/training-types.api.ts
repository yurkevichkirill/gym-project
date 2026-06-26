import TrainingTypeData from "@/types/training-type.type";
import { ApiCollectionResponse } from "@/types/api-collection-response";
import { ApiItemResponse } from "@/types/api-item-response.type";
import { publicApiGet } from "@/lib/publicApiClient";

export const DEFAULT_TRAINING_TYPES_SORT = "name:ASC";

const TRAINING_TYPE_SORT_FIELDS = new Set(["name"]);
const SORT_ORDERS = new Set(["ASC", "DESC"]);

export type TrainingTypesListParams = {
    sort?: string;
    page?: number;
    limit?: number;
};

export type TrainingTypesListResponse = ApiCollectionResponse<TrainingTypeData[]>;

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

    if (!Number.isSafeInteger(parsed) || parsed <= 0 || (maximum !== undefined && parsed > maximum)) {
        return undefined;
    }

    return parsed;
};

const isValidSort = (value: string): boolean => {
    return value.split(",").every((item) => {
        const parts = item.split(":");

        if (parts.length > 2) {
            return false;
        }

        const field = parts[0];
        const order = (parts[1] ?? "ASC").trim().toUpperCase();

        return TRAINING_TYPE_SORT_FIELDS.has(field) && SORT_ORDERS.has(order);
    });
};

export const parseTrainingTypesListParams = (
    searchParams: SearchParamsReader,
): TrainingTypesListParams => {
    const sort = searchParams.get("sort");

    return {
        sort: sort && isValidSort(sort) ? sort : undefined,
        page: readPositiveInteger(searchParams.get("page")),
        limit: readPositiveInteger(searchParams.get("limit"), 100),
    };
};

const createTrainingTypesSearchParams = (
    params: TrainingTypesListParams,
): URLSearchParams => {
    const searchParams = new URLSearchParams();

    Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined) {
            searchParams.set(key, value.toString());
        }
    });

    return searchParams;
};

export const getTrainingTypesRequestKey = (
    params: TrainingTypesListParams,
): string => {
    return createTrainingTypesSearchParams(params).toString();
};

export const getTrainingTypesPage = async (
    params: TrainingTypesListParams = {},
    options: PublicRequestOptions = {},
): Promise<TrainingTypesListResponse> => {
    const queryString = getTrainingTypesRequestKey(params);

    return await publicApiGet<TrainingTypesListResponse>(
        `/training/types/${queryString ? `?${queryString}` : ""}`,
        { signal: options.signal },
    );
};

export const getTrainingTypes = async (): Promise<TrainingTypeData[]> => {
    const response = await getTrainingTypesPage();

    return response.data;
};

export const getTrainingType = async (
    id: string,
    options: PublicRequestOptions = {},
): Promise<TrainingTypeData> => {
    const response = await publicApiGet<ApiItemResponse<TrainingTypeData>>(
        `/training/types/${id}/`,
        { signal: options.signal },
    );

    return response.data;
};
