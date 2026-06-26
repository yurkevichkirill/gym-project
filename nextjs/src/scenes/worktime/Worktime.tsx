'use client'

import Link from "next/link";
import { motion } from "framer-motion";
import type FreeSlotData from "@/types/trainer/public/free-slot.type";
import type WorktimeData from "@/types/trainer/public/worktime.type";
import FreeSlot from "@/scenes/worktime/FreeSlot";
import { useBooking } from "@/context/booking.context";

type Props = {
    worktime: WorktimeData;
    pricePerHour: number;
};

const Worktime = ({ worktime, pricePerHour }: Props) => {
    const {
        booking,
        selectDate,
    } = useBooking();

    const isOpen = booking.date === worktime.date;

    return (
        <motion.li className="flex flex-col gap-3 rounded-xl border p-4">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <button
                    type="button"
                    className="text-xl font-bold"
                    onClick={() => selectDate(worktime.date)}
                >
                    <time dateTime={worktime.date}>{worktime.date}</time>
                </button>
                <Link
                    href={`/worktimes/${worktime.id}`}
                    className="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold transition hover:border-secondary-500"
                >
                    View details
                </Link>
            </div>

            {isOpen ? (
                <>
                    {worktime.freeSlots.length === 0 ? (
                        <p className="text-sm text-gray-600">
                            No free intervals were returned in the current server response.
                        </p>
                    ) : (
                        <ul className="flex flex-col gap-3">
                            {worktime.freeSlots.map((freeSlot: FreeSlotData) => {
                                const slotId = `${worktime.date}_${freeSlot.start}_${freeSlot.end}`;

                                return (
                                    <FreeSlot
                                        key={slotId}
                                        slotId={slotId}
                                        freeSlot={freeSlot}
                                        pricePerHour={pricePerHour}
                                    />
                                );
                            })}
                        </ul>
                    )}
                    <p className="text-xs text-gray-500">
                        Final slot availability is validated by the backend when booking.
                    </p>
                </>
            ) : null}
        </motion.li>
    );
};

export default Worktime;
