import { apiGet, apiPost } from "@/lib/apiClient";
import { ApiCollectionResponse } from "@/types/api-collection-response";
import { ApiItemResponse } from "@/types/api-item-response.type";
import { BookingStatusEnum } from "@/types/booking/bookings-status.enum";
import {
    AdminBooking,
    AdminBookingCreateRequest,
    AdminBookingsGetQueryParams,
} from "@/types/admin/admin-booking.type";
import {
    createAdminSearchParams,
    getAdminRequestKey,
    readDate,
    readEnum,
    readPositiveInteger,
    readSort,
    readTime,
} from "@/api/admin/admin-api-utils";
import type { SearchParamsReader } from "@/types/admin/admin-common.type";

export const DEFAULT_ADMIN_BOOKINGS_SORT = "date:ASC";
const SORT_FIELDS = ["bookedAt", "status", "trainingId", "date", "startTime", "durationMinutes"] as const;
const STATUS_VALUES = Object.values(BookingStatusEnum);

export type AdminBookingsListResponse = ApiCollectionResponse<AdminBooking[]>;

export const parseAdminBookingsListParams = (searchParams: SearchParamsReader): AdminBookingsGetQueryParams => ({
    trainerId: readPositiveInteger(searchParams.get("trainerId")),
    clientId: readPositiveInteger(searchParams.get("clientId")),
    status: readEnum(searchParams.get("status"), STATUS_VALUES),
    date: readDate(searchParams.get("date")),
    startTime: readTime(searchParams.get("startTime")),
    durationMinutes: readPositiveInteger(searchParams.get("durationMinutes")),
    sort: readSort(searchParams, SORT_FIELDS),
    page: readPositiveInteger(searchParams.get("page")),
    limit: readPositiveInteger(searchParams.get("limit"), 100),
});

export const getAdminBookingsRequestKey = (params: AdminBookingsGetQueryParams = {}): string => (
    getAdminRequestKey(params)
);

export const getAdminBookings = async (
    params: AdminBookingsGetQueryParams = {},
): Promise<AdminBookingsListResponse> => {
    const queryString = createAdminSearchParams(params).toString();

    return await apiGet<AdminBookingsListResponse>(`/bookings/${queryString ? `?${queryString}` : ""}`);
};

export const getAdminBooking = async (id: number): Promise<AdminBooking> => {
    const response = await apiGet<ApiItemResponse<AdminBooking>>(`/bookings/${id}/`);

    return response.data;
};

export const createAdminClientBooking = async (
    clientId: number,
    payload: AdminBookingCreateRequest,
): Promise<AdminBooking> => {
    const response = await apiPost<ApiItemResponse<AdminBooking>, AdminBookingCreateRequest>(
        `/clients/${clientId}/bookings/`,
        payload,
    );

    return response.data;
};

export const cancelAdminBooking = async (id: number): Promise<AdminBooking> => {
    const response = await apiPost<ApiItemResponse<AdminBooking>>(`/bookings/${id}/cancel/`);

    return response.data;
};

