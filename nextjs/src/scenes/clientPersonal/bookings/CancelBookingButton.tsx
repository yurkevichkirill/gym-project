'use client'

import { useState } from "react";
import { observer } from "mobx-react-lite";
import { BookingStatusEnum } from "@/types/booking/bookings-status.enum";
import { useStore } from "@/store/StoreProvider";
import ConfirmDialog from "@/shared/ui/ConfirmDialog";
import { notify } from "@/lib/notify";
import { getBookingMutationErrorMessage } from "@/scenes/clientPersonal/bookings/booking-mutation-error";

type CancelBookingButtonProps = {
    bookingId: number;
    status: BookingStatusEnum;
    className?: string;
};

const CANCELABLE_STATUSES = new Set<BookingStatusEnum>([
    BookingStatusEnum.PENDING,
    BookingStatusEnum.SCHEDULED,
]);

const CancelBookingButton = observer(({
    bookingId,
    status,
    className = "",
}: CancelBookingButtonProps) => {
    const { bookingStore } = useStore();
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const isCanceling = bookingStore.isCanceling(bookingId);

    if (!CANCELABLE_STATUSES.has(status)) {
        return null;
    }

    const handleConfirm = async () => {
        setIsDialogOpen(false);
        const toastId = notify.loading("Cancelling booking...");

        try {
            await bookingStore.cancel(bookingId);
            notify.success(
                "Booking cancelled",
                "The updated booking remains available in your history.",
                toastId,
            );
        } catch (error: unknown) {
            notify.error(
                "Cancellation failed",
                getBookingMutationErrorMessage(error, "Unable to cancel this booking."),
                toastId,
            );
        }
    };

    return (
        <>
            <button
                type="button"
                className={`rounded-md border border-red-300 bg-white px-4 py-2 font-semibold text-red-700 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50 ${className}`}
                disabled={isCanceling}
                onClick={() => setIsDialogOpen(true)}
            >
                {isCanceling ? "Cancelling..." : "Cancel booking"}
            </button>
            <ConfirmDialog
                open={isDialogOpen}
                title="Cancel this booking?"
                description="The backend will verify the current booking status, cancellation window, and refund state. The booking will remain in your history."
                confirmLabel="Cancel booking"
                cancelLabel="Keep booking"
                isConfirming={isCanceling}
                tone="danger"
                onConfirm={() => {
                    void handleConfirm();
                }}
                onCancel={() => setIsDialogOpen(false)}
            />
        </>
    );
});

export default CancelBookingButton;
