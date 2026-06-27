import {
    apiGet,
    apiPatch,
    apiPost,
} from "@/lib/apiClient";
import { ApiItemResponse } from "@/types/api-item-response.type";
import { BookingStatusEnum } from "@/types/booking/bookings-status.enum";
import {
    TrainerTrainingType,
    TrainerTrainingsGetParams,
    TrainerTrainingsListResponse,
    TrainerTrainingUpdatePayload,
} from "@/types/trainer/private/trainer-training.type";

export const DEFAULT_TRAINER_TRAININGS_SORT = "bookedAt:ASC";

export const TRAINER_TRAINING_QUERY_KEYS = [
    "clientId",
    "status",
    "date",
    "startTime",
    "durationMinutes",
    "isBusy",
    "sort",
    "page",
    "limit",
] as const;

const TRAINING_SORT_FIELDS = new Set([
    "startTime",
    "durationMinutes",
    "clientId",
    "date",
    "status",
    "bookedAt",
    "isBusy",
]);
const SORT_ORDERS = new Set(["ASC", "DESC"]);
const BOOKING_STATUSES = new Set<BookingStatusEnum>(Object.values(BookingStatusEnum));

type SearchParamsReader = {
    get: (name: string) => string | null;
};

const readPositiveInteger = (
    value: string | null,
    options: {
        minimum?: number;
        maximum?: number;
        multipleOf?: number;
    } = {},
): number | undefined => {
    if (value === null || !/^\d+$/.test(value)) {
        return undefined;
    }

    const parsed = Number(value);
    const minimum = options.minimum ?? 1;

    if (
        !Number.isSafeInteger(parsed)
        || parsed < minimum
        || (options.maximum !== undefined && parsed > options.maximum)
        || (options.multipleOf !== undefined && parsed % options.multipleOf !== 0)
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

const isBackendTime = (value: string): boolean => {
    return /^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/.test(value);
};

const normalizeBackendTime = (value: string): string => {
    return /^\d{2}:\d{2}$/.test(value) ? `${value}:00` : value;
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
            && TRAINING_SORT_FIELDS.has(field)
            && SORT_ORDERS.has(order);
    });
};

const isBookingStatus = (value: string): value is BookingStatusEnum => {
    return BOOKING_STATUSES.has(value as BookingStatusEnum);
};

export const parseTrainerTrainingsListParams = (
    searchParams: SearchParamsReader,
): TrainerTrainingsGetParams => {
    const status = searchParams.get("status");
    const date = searchParams.get("date");
    const startTime = searchParams.get("startTime");
    const isBusy = searchParams.get("isBusy");
    const sort = searchParams.get("sort");

    return {
        clientId: readPositiveInteger(searchParams.get("clientId")),
        status: status && isBookingStatus(status) ? status : undefined,
        date: date && isBackendDate(date) ? date : undefined,
        startTime: startTime && isBackendTime(startTime)
            ? normalizeBackendTime(startTime)
            : undefined,
        durationMinutes: readPositiveInteger(
            searchParams.get("durationMinutes"),
            { minimum: 30, multipleOf: 30 },
        ),
        isBusy: isBusy === "true"
            ? true
            : isBusy === "false"
                ? false
                : undefined,
        sort: sort && isValidSort(sort) ? sort : undefined,
        page: readPositiveInteger(searchParams.get("page")),
        limit: readPositiveInteger(searchParams.get("limit"), { maximum: 100 }),
    };
};

const createSearchParams = (
    params: TrainerTrainingsGetParams,
): URLSearchParams => {
    const searchParams = new URLSearchParams();

    Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined) {
            searchParams.set(key, value.toString());
        }
    });

    return searchParams;
};

export const getTrainerTrainingsRequestKey = (
    params: TrainerTrainingsGetParams,
): string => {
    return createSearchParams(params).toString();
};

export const getCurrentTrainerTrainings = async (
    params: TrainerTrainingsGetParams = {},
): Promise<TrainerTrainingsListResponse> => {
    const queryString = getTrainerTrainingsRequestKey(params);

    return await apiGet<TrainerTrainingsListResponse>(
        `/me/trainings/${queryString ? `?${queryString}` : ""}`,
    );
};

export const getCurrentTrainerTraining = async (
    id: number,
): Promise<TrainerTrainingType> => {
    const response = await apiGet<ApiItemResponse<TrainerTrainingType>>(
        `/trainings/${id}/`,
    );

    return response.data;
};

export const updateCurrentTrainerTraining = async (
    id: number,
    payload: TrainerTrainingUpdatePayload,
): Promise<TrainerTrainingType> => {
    const requestPayload: TrainerTrainingUpdatePayload = {
        ...(payload.startTime !== undefined
            ? { startTime: normalizeBackendTime(payload.startTime) }
            : {}),
        ...(payload.date !== undefined ? { date: payload.date } : {}),
    };
    const response = await apiPatch<
        ApiItemResponse<TrainerTrainingType>,
        TrainerTrainingUpdatePayload
    >(`/trainings/${id}/`, requestPayload);

    return response.data;
};

export const cancelCurrentTrainerTraining = async (id: number): Promise<void> => {
    await apiPost<null>(`/trainings/${id}/cancel/`);
};

export const completeCurrentTrainerTraining = async (
    id: number,
): Promise<TrainerTrainingType> => {
    const response = await apiPost<ApiItemResponse<TrainerTrainingType>>(
        `/trainings/${id}/complete/`,
    );

    return response.data;
};
