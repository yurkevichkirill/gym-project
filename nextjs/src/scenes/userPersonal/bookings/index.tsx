'use client'

import {useEffect, useState} from "react";
import BookingType from "@/types/booking.type";
import Booking from "@/scenes/userPersonal/bookings/Booking";
import Section from "@/shared/Section";
import {getMyBookings} from "@/api/bookings.api";

export const Bookings = () => {
    const [bookings, setBookings] = useState<BookingType[]>([]);
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchData = async () => {
            try {
                const data = await getMyBookings();
                setBookings(data);
            } catch (e) {
                console.error(e);

                if (e instanceof Error) {
                    setError(e.message);
                } else {
                    setError("Something went wrong");
                }
            } finally {
                setLoading(false);
            }
        }

        void fetchData();
    }, []);

    if (loading) {
        return <div>Error: {error}</div>;
    }

    if (error) {
        return <div>Error: {error}</div>;
    }

    return (
        <Section title="My Bookings">
            <div className="flex flex-col gap-4">
                {...bookings.map((booking: BookingType) => (
                    <Booking
                        id={booking.id}
                        trainerId={booking.trainerId}
                        bookedAt={booking.bookedAt}
                        date={booking.date}
                        durationMinutes={booking.durationMinutes}
                        startTime={booking.startTime}
                        status={booking.status}
                    />
                ))}
            </div>
        </Section>
    );
}

export default Bookings;