'use client'

import type FreeSlotData from "@/types/free-slot.type";
import {generateDurationMinutes, generateStartTimes} from "@/lib/utils/time.utils";
import {useState} from "react";

type Props = {
    freeSlot: FreeSlotData,
    startTime: string | null,
    setStartTime: (value: string | null) => void,
    duration: number | null,
    setDuration: (value: number | null) => void,
}

const FreeSlot = ({ freeSlot, startTime, setStartTime, duration, setDuration }: Props) => {
    const [active, setActive] = useState(false);
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
                            setDuration(null);
                            setActive(time !== startTime);
                        }}
                        className={`px-3 py-1 rounded border w-16
                        ${startTime === time ? "bg-primary-500 text-white" : "bg-gray-100"}`}
                    >
                        {time}
                    </button>
                ))}
            </div>

            {/* DURATION */}
            {startTime && (
                <>
                    <p>Select duration (minutes)</p>

                    <div className="flex flex-wrap gap-2">
                        {Array.from(endTimes.keys()).map(time => (
                            <button
                                key={time}
                                onClick={() => setDuration(duration === time ? null : time)}
                                className={`px-3 py-1 rounded border w-[50px]
                                ${duration === time && active ? "bg-secondary-500 text-white" : "bg-gray-100"}`}
                            >
                                {time}
                            </button>
                        ))}
                    </div>
                </>
            )}

            {/* RESULT */}
            {startTime && duration && active && (
                <p className="text-green-600 font-bold">
                    Selected: {startTime} - {endTimes.get(duration)}
                </p>
            )}

        </div>
    );
}

export default FreeSlot;