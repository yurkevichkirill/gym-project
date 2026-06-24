'use client'

import BookingType from "@/types/booking/booking.type";
import Booking from "@/scenes/clientPersonal/bookings/Booking";
import Section from "@/shared/Section";
import {useStore} from "@/store/StoreProvider";
import {observer} from "mobx-react-lite";
import { useEffect, useMemo, useState } from "react";
import { BookingStatusEnum } from "@/types/booking/bookings-status.enum";
import { ChevronDownIcon } from "@heroicons/react/24/outline";

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
    const [isExpanded, setIsExpanded] = useState(false);

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
    const visibleBookings = isExpanded ? sortedBookings : sortedBookings.slice(0, 3);

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
                            disabled={bookingStore.isLoading}
                            className="mt-3 rounded-md bg-secondary-500 px-4 py-2 text-sm cursor-pointer disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {bookingStore.isLoading ? "Retrying..." : "Retry"}
                        </button>
                    </div>
                )}

                {!bookingStore.isLoading && !bookingStore.error && !hasBookings && (
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

                {bookingStore.isLoading && hasBookings && (
                    <p className="text-sm text-gray-500">Refreshing bookings...</p>
                )}

                {sortedBookings.length > 3 && (
                    <button
                        type="button"
                        onClick={() => setIsExpanded(!isExpanded)}
                        className="flex items-center justify-center gap-2 text-sm text-gray-500 hover:text-primary-500 py-2 mt-2 transition-colors cursor-pointer"
                    >
                        {isExpanded ? "Show less" : `Show all (${sortedBookings.length})`}

                        <ChevronDownIcon
                            className={`w-4 h-4 transition-transform duration-300 ${
                                isExpanded ? "rotate-180" : ""
                            }`}
                        />
                    </button>
                )}
            </div>
        </Section>
    );
});

export default Bookings;
