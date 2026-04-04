import BookingType from "@/types/booking/booking.type";
import {ApiResponse} from "@/types/api-response.type";
import {apiDelete, apiGet, apiPost} from "@/lib/apiClient";
import BookingCreateType from "@/types/booking/booking-create.type";

export const getMyBookingsApi = async (): Promise<BookingType[]> => {
    const data = await apiGet<ApiResponse<BookingType[]>>('/me/bookings');

    return data.data;
}

export const bookTrainingApi = async ({ trainerId, date, durationMinutes, startTime }: BookingCreateType): Promise<BookingType> => {
    const data = await apiPost<ApiResponse<BookingType>, BookingCreateType>('/me/bookings/', {
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