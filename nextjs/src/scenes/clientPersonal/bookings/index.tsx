'use client'

import Link from "next/link";
import BookingType from "@/types/booking/booking.type";
import Booking from "@/scenes/clientPersonal/bookings/Booking";
import Section from "@/shared/Section";
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
        <Section title="My Bookings">
            <div className="flex flex-col gap-4">
                {bookingStore.isLoading && !hasBookings && (
                    <p className="text-sm text-gray-500">Loading bookings...</p>
                )}

                {bookingStore.error && (
                    <div className="rounded-md border border-primary-500 bg-red-50 p-4" role="alert">
                        <p className="font-semibold">Unable to load bookings.</p>
                        <p className="mt-1 text-sm text-gray-600">{bookingStore.error}</p>
                        <button
                            type="button"
                            onClick={() => void bookingStore.init()}
                            disabled={isBusy}
                            className="mt-3 rounded-md bg-secondary-500 px-4 py-2 text-sm cursor-pointer disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {isBusy ? "Retrying..." : "Retry"}
                        </button>
                    </div>
                )}

                {!isBusy && !bookingStore.error && !hasBookings && (
                    <p className="text-sm text-gray-500">You have no bookings yet.</p>
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
                    <p className="text-sm text-gray-500" role="status">Refreshing bookings...</p>
                )}

                <Link
                    href="/me/bookings"
                    className="mt-2 inline-flex self-start rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold transition hover:border-secondary-500"
                >
                    View all bookings
                    {bookingStore.pagination ? ` (${bookingStore.pagination.total})` : ""}
                </Link>
            </div>
        </Section>
    );
});

export default Bookings;
