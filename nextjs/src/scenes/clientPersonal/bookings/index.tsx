'use client'

import BookingType from "@/types/booking/booking.type";
import Booking from "@/scenes/clientPersonal/bookings/Booking";
import Section from "@/shared/Section";
import {useStore} from "@/store/StoreProvider";
import {observer} from "mobx-react-lite";

export const Bookings = observer(() => {
    const { clientStore } = useStore();

    if (clientStore.isLoading) {
        return <div>Loading...</div>;
    }

    return (
        <Section title="My Bookings">
            <div className="flex flex-col gap-4">
                {clientStore.bookings.map((booking: BookingType) => (
                    <Booking
                        key = {booking.id}
                        id = {booking.id}
                        trainerId = {booking.trainerId}
                        bookedAt = {booking.bookedAt}
                        date = {booking.date}
                        durationMinutes = {booking.durationMinutes}
                        startTime = {booking.startTime}
                        status = {booking.status}
                    />
                ))}
            </div>
        </Section>
    );
});

export default Bookings;