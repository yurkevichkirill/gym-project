'use client'

import type FreeSlotData from "@/types/trainer/public/free-slot.type";
import {generateDurationMinutes, generateStartTimes} from "@/lib/utils/time.utils";
import {useBooking} from "@/context/booking.context";

type Props = {
    freeSlot: FreeSlotData,
    pricePerHour: number,
    activeSlot: FreeSlotData | null,
    setActiveSlot: (value: FreeSlotData | null) => void,
}

const FreeSlot = ({ freeSlot, pricePerHour, activeSlot, setActiveSlot }: Props) => {
    const {
        startTime,
        setStartTime,
        durationMinutes,
        setDurationMinutes,
    } = useBooking();
    const startTimes = generateStartTimes(freeSlot.start, freeSlot.end)
    const endTimes = startTime
        ? generateDurationMinutes(freeSlot.end, startTime)
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
                            setStartTime(time === startTime ? null : time);
                            setDurationMinutes(null);
                            setActiveSlot(freeSlot === activeSlot ? null : freeSlot);
                        }}
                        className={`px-3 py-1 rounded w-16
                        ${startTime === time ? "bg-primary-500 text-white" : "bg-primary-100"}`}
                    >
                        {time}
                    </button>
                ))}
            </div>

            {/* DURATION */}
            {startTime && activeSlot === freeSlot && (
                <>
                    <p>Select duration (minutes)</p>

                    <div className="flex flex-wrap gap-2">
                        {Array.from(endTimes.keys()).map(time => (
                            <button
                                key={time}
                                onClick={() => setDurationMinutes(durationMinutes === time ? null : time)}
                                className={`px-3 py-1 rounded w-[50px]
                                ${durationMinutes === time && activeSlot === freeSlot ? "bg-primary-500 text-white" : "bg-primary-100"}`}
                            >
                                {time}
                            </button>
                        ))}
                    </div>
                </>
            )}

            {/* RESULT */}
            {startTime && durationMinutes && activeSlot === freeSlot && (
                <p className="text-primary-500 font-bold">
                    Selected: {startTime} - {endTimes.get(durationMinutes)}<br />
                    Price: {durationMinutes / 60 * pricePerHour}$
                </p>
            )}

        </div>
    );
}

export default FreeSlot;