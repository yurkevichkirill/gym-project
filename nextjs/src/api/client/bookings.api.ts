import BookingType from "@/types/booking/booking.type";
import {apiDelete, apiGet, apiPost} from "@/lib/apiClient";
import BookingCreateType from "@/types/booking/booking-create.type";
import {ApiCollectionResponse} from "@/types/api-collection-response";
import {ApiItemResponse} from "@/types/api-item-response.type";

export const getMyBookings = async (): Promise<BookingType[]> => {
    const data = await apiGet<ApiCollectionResponse<BookingType[]>>('/me/bookings/');

    return data.data;
}

export const getBooking = async (id: number): Promise<BookingType> => {
    const data = await apiGet<ApiCollectionResponse<BookingType>>(`/me/bookings/${id}/`);

    return data.data;
}

export const createBooking = async ({ trainerId, date, durationMinutes, startTime }: BookingCreateType): Promise<BookingType> => {
    const data = await apiPost<ApiItemResponse<BookingType>, BookingCreateType>('/me/bookings/', {
        trainerId,
        date,
        durationMinutes,
        startTime,
    });

    return data.data;
}

export const cancelBooking = async (id: number) => {
    return apiDelete<null>(`/me/bookings/${id}/`);
}