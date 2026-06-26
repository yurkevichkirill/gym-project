import TrainerData from "@/types/trainer/public/trainer.type";
import { ApiCollectionResponse } from "@/types/api-collection-response";
import { ApiItemResponse } from "@/types/api-item-response.type";
import { publicApiGet } from "@/lib/publicApiClient";

export const DEFAULT_TRAINERS_SORT = "lastName:ASC";

const TRAINER_SORT_FIELDS = new Set([
    "pricePerHour",
    "firstName",
    "lastName",
    "trainingTypeId",
]);
const TRAINER_SORT_ORDERS = new Set(["ASC", "DESC"]);

export type TrainersListParams = {
    minPricePerHour?: number;
    maxPricePerHour?: number;
    trainingTypeId?: number;
    sort?: string;
    page?: number;
    limit?: number;
};

export type TrainersListResponse = ApiCollectionResponse<TrainerData[]>;

type SearchParamsReader = {
    get: (name: string) => string | null;
};

type GetTrainersOptions = {
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

        return TRAINER_SORT_FIELDS.has(field) && TRAINER_SORT_ORDERS.has(order);
    });
};

export const parseTrainersListParams = (
    searchParams: SearchParamsReader,
): TrainersListParams => {
    const sort = searchParams.get("sort");

    return {
        minPricePerHour: readPositiveInteger(searchParams.get("minPricePerHour")),
        maxPricePerHour: readPositiveInteger(searchParams.get("maxPricePerHour")),
        trainingTypeId: readPositiveInteger(searchParams.get("trainingTypeId")),
        sort: sort && isValidSort(sort) ? sort : undefined,
        page: readPositiveInteger(searchParams.get("page")),
        limit: readPositiveInteger(searchParams.get("limit"), 100),
    };
};

const createTrainersSearchParams = (params: TrainersListParams): URLSearchParams => {
    const searchParams = new URLSearchParams();

    Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined) {
            searchParams.set(key, value.toString());
        }
    });

    return searchParams;
};

export const getTrainersRequestKey = (params: TrainersListParams): string => {
    return createTrainersSearchParams(params).toString();
};

export const getTrainers = async (
    params: TrainersListParams = {},
    options: GetTrainersOptions = {},
): Promise<TrainersListResponse> => {
    const queryString = getTrainersRequestKey(params);
    const response = await publicApiGet<TrainersListResponse>(
        `/trainers/${queryString ? `?${queryString}` : ""}`,
        { signal: options.signal },
    );

    return response;
};

export const getTrainer = async (id: string): Promise<TrainerData> => {
    const response = await publicApiGet<ApiItemResponse<TrainerData>>(`/trainers/${id}/`);

    return response.data;
};
