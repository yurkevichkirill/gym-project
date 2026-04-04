'use client'

import {createContext, ReactNode, useContext, useState} from "react";

type BookingContextType = {
    date: string | null;
    setDate: (value: string | null) => void;

    startTime: string | null;
    setStartTime: (value: string | null) => void;

    durationMinutes: number | null;
    setDurationMinutes: (value: number | null) => void;

    reset: () => void;
}

const BookingContext = createContext<BookingContextType | null>(null);

export const BookingProvider = ({ children }: { children: ReactNode }) => {
    const [date, setDate] = useState<string | null>(null);
    const [startTime, setStartTime] = useState<string | null>(null);
    const [durationMinutes, setDurationMinutes] = useState<number | null>(null);

    const reset = () => {
        setStartTime(null);
        setDurationMinutes(null);
    };

    return (
        <BookingContext.Provider
            value={{
                date,
                setDate,
                startTime,
                setStartTime,
                durationMinutes,
                setDurationMinutes,
                reset,
            }}
        >
            {children}
        </BookingContext.Provider>
    );
}

export const useBooking = () => {
    const context = useContext(BookingContext);

    if (!context) {
        throw new Error("useBooking must be used within BookingProvider");
    }

    return context;
}