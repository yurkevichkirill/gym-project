'use client'

import { motion } from "framer-motion";
import type FreeSlotData from "@/types/trainer/public/free-slot.type";
import type WorktimeData from "@/types/trainer/public/worktime.type";
import FreeSlot from "@/scenes/worktime/FreeSlot";
import {useState} from "react";
import {useBooking} from "@/context/booking.context";

type Props = {
    worktime: WorktimeData,
    pricePerHour: number,
}

const Worktime = ({ worktime, pricePerHour}: Props) => {
    const {
        date,
        setDate,
        reset,
    } = useBooking();
    const [activeSlot, setActiveSlot] = useState<FreeSlotData | null>(null)

    return (
        <motion.div className="border rounded-xl p-4 flex flex-col gap-3">
            <button
                className="text-xl font-bold"
                onClick={() => {
                    if (date === worktime.date) {
                        setDate(null);
                    } else {
                        setDate(worktime.date);
                    }

                    reset();
                }}
            >
                {worktime.date}
            </button>

            { date === worktime.date &&
            <ul className="flex flex-col gap-3">
                {worktime.freeSlots.map((freeSlot: FreeSlotData, index) => (
                    <FreeSlot
                        freeSlot = { freeSlot }
                        pricePerHour = { pricePerHour }
                        activeSlot = {activeSlot}
                        setActiveSlot = {setActiveSlot}
                        key = { index }
                    />
                ))}
            </ul>
            }
        </motion.div>
    );
}

export default Worktime;