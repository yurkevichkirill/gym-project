import BookingType from "@/types/booking/booking.type";
import { apiGet, apiPost } from "@/lib/apiClient";
import BookingCreateType from "@/types/booking/booking-create.type";
import { ApiCollectionResponse } from "@/types/api-collection-response";
import { ApiItemResponse } from "@/types/api-item-response.type";
import { BookingsGetQueryParams } from "@/types/booking/bookings-get.type";
import { BookingStatusEnum } from "@/types/booking/bookings-status.enum";

export const DEFAULT_BOOKINGS_SORT = "date:ASC";

export const BOOKING_QUERY_KEYS = [
    "trainerId",
    "status",
    "date",
    "startTime",
    "durationMinutes",
    "sort",
    "page",
    "limit",
] as const satisfies readonly (keyof BookingsGetQueryParams)[];

const BOOKING_SORT_FIELDS = new Set([
    "bookedAt",
    "status",
    "trainingId",
    "date",
    "startTime",
    "durationMinutes",
]);
const BOOKING_SORT_ORDERS = new Set(["ASC", "DESC"]);
const BOOKING_STATUSES = new Set<string>(Object.values(BookingStatusEnum));

export type BookingsListResponse = ApiCollectionResponse<BookingType[]>;

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

    if (!Number.isSafeInteger(parsed) || parsed <= 0 || (maximum !== undefined && parsed > maximum)) {
        return undefined;
    }

    return parsed;
};

const readDurationMinutes = (value: string | null): number | undefined => {
    const parsed = readPositiveInteger(value, 1440);

    return parsed !== undefined && parsed >= 30 && parsed % 30 === 0
        ? parsed
        : undefined;
};

const readDate = (value: string | null): string | undefined => {
    if (value === null || !/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        return undefined;
    }

    const [year, month, day] = value.split("-").map(Number);
    const candidate = new Date(Date.UTC(year, month - 1, day));

    return candidate.getUTCFullYear() === year
        && candidate.getUTCMonth() === month - 1
        && candidate.getUTCDate() === day
        ? value
        : undefined;
};

const readStartTime = (value: string | null): string | undefined => {
    if (value === null) {
        return undefined;
    }

    const match = /^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/.exec(value);

    if (match === null) {
        return undefined;
    }

    return value.length === 5 ? `${value}:00` : value;
};

const isValidSort = (value: string): boolean => {
    return value.split(",").every((item) => {
        const parts = item.trim().split(":");

        if (parts.length > 2) {
            return false;
        }

        const field = parts[0]?.trim();
        const order = (parts[1] ?? "ASC").trim().toUpperCase();

        return field !== undefined
            && field.length > 0
            && BOOKING_SORT_FIELDS.has(field)
            && BOOKING_SORT_ORDERS.has(order);
    });
};

export const parseBookingsListParams = (
    searchParams: SearchParamsReader,
): BookingsGetQueryParams => {
    const status = searchParams.get("status");
    const sort = searchParams.get("sort");

    return {
        trainerId: readPositiveInteger(searchParams.get("trainerId")),
        status: status !== null && BOOKING_STATUSES.has(status)
            ? status as BookingStatusEnum
            : undefined,
        date: readDate(searchParams.get("date")),
        startTime: readStartTime(searchParams.get("startTime")),
        durationMinutes: readDurationMinutes(searchParams.get("durationMinutes")),
        sort: sort !== null && isValidSort(sort) ? sort : undefined,
        page: readPositiveInteger(searchParams.get("page")),
        limit: readPositiveInteger(searchParams.get("limit"), 100),
    };
};

const createBookingsSearchParams = (
    params: BookingsGetQueryParams,
): URLSearchParams => {
    const searchParams = new URLSearchParams();

    BOOKING_QUERY_KEYS.forEach((key) => {
        const value = params[key];

        if (value !== undefined && value !== null && value !== "") {
            searchParams.set(key, value.toString());
        }
    });

    return searchParams;
};

export const getBookingsRequestKey = (
    params: BookingsGetQueryParams,
): string => {
    return createBookingsSearchParams(params).toString();
};

export const getMyBookings = async (
    params: BookingsGetQueryParams = {},
): Promise<BookingsListResponse> => {
    const queryString = getBookingsRequestKey(params);

    return await apiGet<BookingsListResponse>(
        `/me/bookings/${queryString ? `?${queryString}` : ""}`,
    );
};

export const getMyBooking = async (id: number): Promise<BookingType> => {
    const response = await apiGet<ApiItemResponse<BookingType>>(`/me/bookings/${id}/`);

    return response.data;
};

export const createBooking = async ({
    trainerId,
    date,
    durationMinutes,
    startTime,
}: BookingCreateType): Promise<BookingType> => {
    const response = await apiPost<ApiItemResponse<BookingType>, BookingCreateType>("/me/bookings/", {
        trainerId,
        date,
        durationMinutes,
        startTime,
    });

    return response.data;
};

export const cancelBooking = async (id: number) => {
    return apiPost<null>(`/me/bookings/${id}/cancel/`);
};
