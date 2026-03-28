'use client'

import { motion } from "framer-motion";
import type FreeSlotData from "@/types/free-slot.type";
import type WorktimeData from "@/types/worktime.type";
import FreeSlot from "@/scenes/worktime/FreeSlot";

type Props = {
    curWorktime: WorktimeData | null,
    setCurWorktime: (value: WorktimeData | null) => void,
    worktime: WorktimeData,
    date: string | null,
    setDate: (value: string | null) => void,
    startTime: string | null,
    setStartTime: (value: string | null) => void,
    endTime: string | null,
    setEndTime: (value: string | null) => void,
}

const Worktime = ({ curWorktime, setCurWorktime, worktime, date, setDate , startTime, setStartTime, endTime, setEndTime}: Props) => {
    return (
        <motion.div className="border rounded-xl p-4 flex flex-col gap-3">
            <button
                className="text-xl font-bold"
                onClick={ () => {
                    setDate(date === worktime.date ? null : worktime.date);
                    setCurWorktime(worktime);
                } }
            >
                {worktime.date}
            </button>

            { date === worktime.date &&
            <ul className="flex flex-col gap-3">
                {worktime.freeSlots.map((freeSlot: FreeSlotData, index) => (
                    <FreeSlot
                        freeSlot = { freeSlot }
                        startTime = { startTime }
                        setStartTime = { setStartTime }
                        endTime = { endTime }
                        setEndTime = { setEndTime }
                        key = { index }
                    />
                ))}
            </ul>
            }
        </motion.div>
    );
}

export default Worktime;