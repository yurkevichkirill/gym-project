import {apiPost} from "@/lib/apiClient";

export interface BookingData {
    durationMinutes: number,
    startTime: string,
    workTimeId: number,
}

export const book = async (payload: BookingData) => {
    const res = apiPost(
        "/me/bookings/",
        payload
    );
}