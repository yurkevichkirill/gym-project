import BookingType from "@/types/booking/booking.type";
import { apiGet, apiPost } from "@/lib/apiClient";
import BookingCreateType from "@/types/booking/booking-create.type";
import { ApiCollectionResponse } from "@/types/api-collection-response";
import { ApiItemResponse } from "@/types/api-item-response.type";
import { BookingsGetQueryParams } from "@/types/booking/bookings-get.type";

export const getMyBookings = async (
    params?: BookingsGetQueryParams,
): Promise<BookingType[]> => {
    let url = "/me/bookings/";

    if (params) {
        const cleanParams = Object.fromEntries(
            Object.entries(params).filter(([, value]) => value !== undefined && value !== null),
        );

        const stringParams = Object.fromEntries(
            Object.entries(cleanParams).map(([key, value]) => [key, String(value)]),
        );

        const searchParams = new URLSearchParams(stringParams);
        url += `?${searchParams.toString()}`;
    }

    const response = await apiGet<ApiCollectionResponse<BookingType[]>>(url);

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
