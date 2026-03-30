import {notify} from "@/lib/notify";
import {bookTraining} from "@/api/bookings.api";
import {ApiError} from "@/types/auth.type";

export const handleBooking = async (id: string | null, date: string | null, durationMinutes: number | null, startTime: string | null) => {
    if (!id || !date || !durationMinutes || !startTime) {
        notify.error("Missing data", "Please select date and time");
        return;
    }

    const toastId = notify.loading("Booking training...");

    try {
        const res = await bookTraining({
            trainerId: Number(id),
            date,
            durationMinutes,
            startTime: startTime + ":00",
        });

        notify.success(
            "Training booked",
            `${res.durationMinutes} min on ${res.date} at ${res.startTime}`,
            toastId,
        );

    } catch (error: any) {
        notify.error(
            "Booking failed",
            error?.message || "Something went wrong",
            toastId,
        );
    }
}