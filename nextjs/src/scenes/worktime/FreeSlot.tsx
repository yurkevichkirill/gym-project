import type FreeSlotData from "@/types/free-slot.type";
import {generateDurationMinutes, generateEndTimes, generateStartTimes} from "@/lib/utils/time.utils";

type Props = {
    freeSlot: FreeSlotData,
    startTime: string | null,
    setStartTime: (value: string | null) => void,
    endTime: string | null,
    setEndTime: (value: string | null) => void,
}

const FreeSlot = ({ freeSlot, startTime, setStartTime, endTime, setEndTime }: Props) => {
    const startTimes = generateStartTimes(freeSlot.start, freeSlot.end)
    const endTimes = startTime
        ? generateDurationMinutes(freeSlot.end, startTime)
        : []

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
                            setStartTime(time === startTime ? null : time)
                            setEndTime(null)
                        }}
                        className={`px-3 py-1 rounded border w-16
                        ${startTime === time ? "bg-primary-500 text-white" : "bg-gray-100"}`}
                    >
                        {time}
                    </button>
                ))}
            </div>

            {/* END TIMES */}
            {startTime && (
                <>
                    <p>Select end time</p>

                    <div className="flex flex-wrap gap-2">
                        {endTimes.map(time => (
                            <button
                                key={time}
                                onClick={() => setEndTime(time === endTime ? null : time)}
                                className={`px-3 py-1 rounded border
                                ${endTime === time ? "bg-secondary-500 text-white" : "bg-gray-100"}`}
                            >
                                {time}
                            </button>
                        ))}
                    </div>
                </>
            )}

            {/* RESULT */}
            {startTime && endTime && (
                <p className="text-green-600 font-bold">
                    Selected: {startTime} - {endTime}
                </p>
            )}

        </div>
    );
}

export default FreeSlot;