import {notify} from "@/lib/notify";
import {deleteBooking} from "@/api/bookings.api";

export const handleBookingDelete = async (id: number) => {
    if (!confirm("Cancel this training?")) return;

    const toastId = notify.loading("Cancelling booking...");

    try {
        await deleteBooking(id);

        notify.success(
            "Booking cancelled",
            "Your training has been removed",
            toastId
        );

    } catch (error: any) {
        notify.error(
            "Cancellation failed",
            error?.message || "Something went wrong",
            toastId,
        );
    }
}