'use client'

import type FreeSlotData from "@/types/trainer/public/free-slot.type";
import { generateDurationMinutes, generateStartTimes } from "@/lib/utils/time.utils";
import { useBooking } from "@/context/booking.context";
import { useMemo } from "react";

type Props = {
    slotId: string,
    freeSlot: FreeSlotData,
    pricePerHour: number,
}

const FreeSlot = ({ slotId, freeSlot, pricePerHour }: Props) => {
    const {
        booking,
        selectSlot,
        selectStartTime,
        selectDuration,
    } = useBooking();

    const isActive = booking.slotId === slotId;

    const startTimes = useMemo(() => {
        return generateStartTimes(freeSlot.start, freeSlot.end);
    }, [freeSlot.start, freeSlot.end]);

    const endTimes = useMemo(() => {
        return booking.startTime && isActive
            ? generateDurationMinutes(freeSlot.end, booking.startTime)
            : new Map<number, string>();
    }, [freeSlot.end, booking.startTime, isActive]);

    const formattedTotalPrice = useMemo(() => {
        if (!booking.durationMinutes || !isActive) return "";

        const rawPrice = (booking.durationMinutes / 60) * pricePerHour;
        
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
        }).format(rawPrice / 100);
    }, [booking.durationMinutes, pricePerHour, isActive]);

    return (
        <div className="flex flex-col gap-3 p-3 border rounded-xl">

            <p className="font-bold">
                Available: {freeSlot.start} - {freeSlot.end}
            </p>

            {/* START TIMES */}
            <div className="flex flex-wrap gap-2">
                {startTimes.map(time => (
                    <button
                        key={time}
                        onClick={() => {
                            selectSlot(slotId);
                            selectStartTime(time);
                        }}
                        className={`px-3 py-1 rounded w-16 cursor-pointer transition-colors
                        ${booking.startTime === time && isActive
                            ? "bg-primary-500 text-white"
                            : "bg-primary-100 hover:bg-primary-200"}`}
                    >
                        {time}
                    </button>
                ))}
            </div>

            {/* DURATION */}
            {booking.startTime && isActive && (
                <>
                    <p className="text-sm font-medium text-gray-600 mt-1">Select duration (minutes)</p>

                    <div className="flex flex-wrap gap-2">
                        {Array.from(endTimes.keys()).map(time => (
                            <button
                                key={time}
                                onClick={() => selectDuration(time)}
                                className={`px-3 py-1 rounded w-[50px] cursor-pointer transition-colors
                                ${booking.durationMinutes === time
                                    ? "bg-primary-500 text-white"
                                    : "bg-primary-100 hover:bg-primary-200"}`}
                            >
                                {time}
                            </button>
                        ))}
                    </div>
                </>
            )}

            {/* RESULT */}
            {booking.startTime && booking.durationMinutes && isActive && (
                <div className="mt-2 pt-2 border-t border-dashed border-gray-200">
                    <p className="text-primary-500 font-bold">
                        Selected: {booking.startTime} - {endTimes.get(booking.durationMinutes)}<br />
                        Price: {formattedTotalPrice}
                    </p>
                </div>
            )}

        </div>
    );
}

export default FreeSlot;