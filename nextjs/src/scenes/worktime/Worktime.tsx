'use client'

import { motion } from "framer-motion";
import type FreeSlotData from "@/types/free-slot.type";
import type WorktimeData from "@/types/worktime.type";
import FreeSlot from "@/scenes/worktime/FreeSlot";

type Props = {
    worktime: WorktimeData,
    date: string | null,
    setDate: (value: string | null) => void,
    startTime: string | null,
    setStartTime: (value: string | null) => void,
    duration: number | null,
    setDuration: (value: number | null) => void,
}

const Worktime = ({ worktime, date, setDate , startTime, setStartTime, duration, setDuration}: Props) => {
    return (
        <motion.div className="border rounded-xl p-4 flex flex-col gap-3">
            <button
                className="text-xl font-bold"
                onClick={ () => {
                    date === worktime.date ?
                        setDate(null) :
                        setDate(worktime.date)
                        setStartTime(null)
                        setDuration(null);
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
                        duration = { duration }
                        setDuration = { setDuration }
                        key = { index }
                    />
                ))}
            </ul>
            }
        </motion.div>
    );
}

export default Worktime;