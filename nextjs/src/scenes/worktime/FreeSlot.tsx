'use client'

import type FreeSlotData from "@/types/trainer/public/free-slot.type";
import {generateDurationMinutes, generateStartTimes} from "@/lib/utils/time.utils";
import {useBooking} from "@/context/booking.context";

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

    const startTimes = generateStartTimes(freeSlot.start, freeSlot.end);

    const endTimes = booking.startTime && isActive
        ? generateDurationMinutes(freeSlot.end,booking.startTime)
        : new Map<number, string>();

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
                        className={`px-3 py-1 rounded w-16
                        ${booking.startTime === time && isActive
                            ? "bg-primary-500 text-white"
                            : "bg-primary-100"}`}
                    >
                        {time}
                    </button>
                ))}
            </div>

            {/* DURATION */}
            {booking.startTime && isActive && (
                <>
                    <p>Select duration (minutes)</p>

                    <div className="flex flex-wrap gap-2">
                        {Array.from(endTimes.keys()).map(time => (
                            <button
                                key={time}
                                onClick={() => selectDuration(time)}
                                className={`px-3 py-1 rounded w-[50px]
                                ${booking.durationMinutes === time
                                    ? "bg-primary-500 text-white"
                                    : "bg-primary-100"}`}
                            >
                                {time}
                            </button>
                        ))}
                    </div>
                </>
            )}

            {/* RESULT */}
            {booking.startTime && booking.durationMinutes && isActive && (
                <p className="text-primary-500 font-bold">
                    Selected: {booking.startTime} - {endTimes.get(booking.durationMinutes)}<br />
                    Price: {booking.durationMinutes / 60 * pricePerHour}$
                </p>
            )}

        </div>
    );
}

export default FreeSlot;