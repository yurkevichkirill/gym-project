'use client'

import { BookingStateType } from "@/types/booking/booking-state.type";
import { createContext, useContext, useState } from "react";

type BookingContextType = {
    booking: BookingStateType;
    selectDate: (date: string) => void;
    selectSlot: (slotId: string) => void;
    selectStartTime: (time: string) => void;
    selectDuration: (duration: number) => void;
    reset: () => void;
};

const BookingContext = createContext<BookingContextType | null>(null);

export const BookingProvider = ({ children }: { children: React.ReactNode }) => {
    const [booking, setBooking] = useState<BookingStateType>({
        date: null,
        slotId: null,
        startTime: null,
        durationMinutes: null,
    });

    const selectDate = (date: string) => {
        setBooking({
            date,
            slotId: null,
            startTime: null,
            durationMinutes: null,
        });
    };

    const selectSlot = (slotId: string) => {
        setBooking(prev => ({
            ...prev,
            slotId,
            startTime: null,
            durationMinutes: null,
        }));
    };

    const selectStartTime = (time: string) => {
        setBooking(prev => ({
            ...prev,
            startTime: time,
            durationMinutes: null,
        }));
    };

    const selectDuration = (duration: number) => {
        setBooking(prev => ({
            ...prev,
            durationMinutes: duration,
        }));
    };

    const reset = () => {
        setBooking({
            date: null,
            slotId: null,
            startTime: null,
            durationMinutes: null,
        });
    };

    return (
        <BookingContext.Provider
            value={{
                booking,
                selectDate,
                selectSlot,
                selectStartTime,
                selectDuration,
                reset,
            }}
        >
            {children}
        </BookingContext.Provider>
    );
};

export const useBooking = () => {
    const ctx = useContext(BookingContext);
    if (!ctx) throw new Error("useBooking must be used within BookingProvider");
    return ctx;
};