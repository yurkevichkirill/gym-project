import BookingType from "@/types/booking.type";
import {ApiResponse} from "@/types/api-response.type";
import {apiGet} from "@/lib/apiClient";

export const getMyBookings = async (): Promise<BookingType[]> => {
    const data = await apiGet<ApiResponse<BookingType[]>>('/me/bookings');

    return data.data;
}