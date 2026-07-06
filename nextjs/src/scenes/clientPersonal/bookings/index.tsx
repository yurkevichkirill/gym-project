'use client'

import Link from "next/link";
import BookingType from "@/types/booking/booking.type";
import Booking from "@/scenes/clientPersonal/bookings/Booking";
import Section, {
    emptyStateClassName,
    errorStateClassName,
    loadingStateClassName,
    primaryActionClassName,
    secondaryActionClassName,
} from "@/shared/Section";
import { useStore } from "@/store/StoreProvider";
import { observer } from "mobx-react-lite";
import { useEffect, useMemo } from "react";
import { BookingStatusEnum } from "@/types/booking/bookings-status.enum";

const statusWeights: Record<string, number> = {
    [BookingStatusEnum.SCHEDULED]: 1,
    [BookingStatusEnum.PENDING]: 2,
    [BookingStatusEnum.CANCELED_BY_CLIENT]: 3,
    [BookingStatusEnum.CANCELED_BY_TRAINER]: 4,
    [BookingStatusEnum.CANCELED_BY_SYSTEM]: 5,
    [BookingStatusEnum.CANCELED_PAYMENT_FAILED]: 6,
    [BookingStatusEnum.COMPLETED]: 7,
};

export const Bookings = observer(() => {
    const { bookingStore } = useStore();

    useEffect(() => {
        void bookingStore.init();
    }, [bookingStore]);

    const sortedBookings = useMemo(() => {
        return [...bookingStore.bookings].sort((a, b) => {
            const weightA = statusWeights[a.status] || 8;
            const weightB = statusWeights[b.status] || 8;

            return weightA - weightB;
        });
    }, [bookingStore.bookings]);

    const hasBookings = sortedBookings.length > 0;
    const visibleBookings = sortedBookings.slice(0, 3);
    const isBusy = bookingStore.isLoading || bookingStore.isRefreshing;

    return (
        <Section
            title="My Bookings"
            titleId="my-bookings-title"
            action={(
                <Link
                    href="/me/bookings"
                    className={secondaryActionClassName}
                    aria-label="View all bookings"
                >
                    View all bookings
                    {bookingStore.pagination ? ` (${bookingStore.pagination.total})` : ""}
                </Link>
            )}
        >
            <div className="flex flex-col gap-4">
                {bookingStore.isLoading && !hasBookings && (
                    <div role="status" aria-live="polite" className={loadingStateClassName}>Loading bookings...</div>
                )}

                {bookingStore.error && (
                    <div className={errorStateClassName} role="alert">
                        <p className="font-semibold">Unable to load bookings.</p>
                        <p className="mt-1 text-sm">{bookingStore.error}</p>
                        <button
                            type="button"
                            onClick={() => void bookingStore.init()}
                            disabled={isBusy}
                            className={`${primaryActionClassName} mt-3`}
                        >
                            {isBusy ? "Retrying..." : "Retry"}
                        </button>
                    </div>
                )}

                {!isBusy && !bookingStore.error && !hasBookings && (
                    <div className={emptyStateClassName}>You have no bookings yet.</div>
                )}

                {visibleBookings.map((booking: BookingType) => (
                    <Booking
                        key={booking.id}
                        id={booking.id}
                        trainerId={booking.trainerId}
                        bookedAt={booking.bookedAt}
                        date={booking.date}
                        durationMinutes={booking.durationMinutes}
                        startTime={booking.startTime}
                        status={booking.status}
                    />
                ))}

                {bookingStore.isRefreshing && hasBookings && (
                    <p className="text-sm text-gray-600" role="status" aria-live="polite">Refreshing bookings...</p>
                )}

            </div>
        </Section>
    );
});

export default Bookings;
