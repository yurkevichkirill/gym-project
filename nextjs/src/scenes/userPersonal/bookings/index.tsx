'use client'

import {useEffect, useState} from "react";
import {ApiResponse} from "@/types/api-response.type";
import BookingType from "@/types/booking.type";
import Booking from "@/scenes/userPersonal/bookings/Booking";
import Section from "@/shared/Section";

export const Bookings = () => {
    const [bookings, setBookings] = useState<BookingType[]>([]);

    useEffect(() => {
        const fetchData = async () => {
            const response = await fetch(
                `${process.env.NEXT_PUBLIC_API_URL}/me/bookings/`,{
                    credentials: 'include'
                });
            if (!response.ok) {
                console.error("Failed to fetch bookings, status:  ", response.status);
            }
            const data: ApiResponse<BookingType[]> = await response.json();

            setBookings(data.data);
        }

        void fetchData();
    }, []);

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