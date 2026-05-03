import BookingType from "@/types/booking/booking.type";
import {apiDelete, apiGet, apiPost} from "@/lib/apiClient";
import BookingCreateType from "@/types/booking/booking-create.type";
import {ApiCollectionResponse} from "@/types/api-collection-response";
import {ApiItemResponse} from "@/types/api-item-response.type";

export const getMyBookingsApi = async (): Promise<BookingType[]> => {
    const data = await apiGet<ApiCollectionResponse<BookingType[]>>('/me/bookings/');

    return data.data;
}

export const bookTrainingApi = async ({ trainerId, date, durationMinutes, startTime }: BookingCreateType): Promise<BookingType> => {
    const data = await apiPost<ApiItemResponse<BookingType>, BookingCreateType>('/me/bookings/', {
        trainerId,
        date,
        durationMinutes,
        startTime,
    });

    return data.data;
}

export const deleteBookingApi = async (id: number) => {
    return apiDelete<null>(`/me/bookings/${id}/`);
}