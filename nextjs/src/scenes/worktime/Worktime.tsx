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
        booking,
        selectDate,
    } = useBooking();

    const isOpen = booking.date === worktime.date;

    return (
        <motion.div className="border rounded-xl p-4 flex flex-col gap-3">
            <button
                className="text-xl font-bold"
                onClick={() => selectDate(worktime.date)}
            >
                {worktime.date}
            </button>

            {isOpen &&
            <ul className="flex flex-col gap-3">
                {worktime.freeSlots.map((freeSlot: FreeSlotData, index) => {
                    const slotId = `${worktime.date}_${freeSlot.start}_${freeSlot.end}`;

                    return (
                        <FreeSlot
                            key = {index}
                            slotId = {slotId}
                            freeSlot = {freeSlot}
                            pricePerHour = {pricePerHour}
                        />
                    );
                })}
            </ul>
            }
        </motion.div>
    );
}

export default Worktime;