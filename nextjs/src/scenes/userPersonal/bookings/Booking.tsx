import { motion } from "framer-motion";
import {handleBookingDelete} from "@/handlers/bokingDeleteHandler";

type Props = {
    id: number,
    trainerId: number,
    bookedAt: string,
    date: string,
    durationMinutes: number,
    startTime: string,
    status: string,
    onDelete: (id: number) => void,
}

const Booking = ({ id, trainerId, bookedAt, date, durationMinutes, startTime, status, onDelete }: Props) => {
    return (
        <motion.div
            whileHover={{ scale: 1.02 }}
            className="flex flex-col"
        >
            <motion.div
                className="border border-gray-50 rounded-t-xl p-4 flex justify-between items-center"
            >
                <div>
                    <p className="font-semibold">{date}</p>
                    <p className="text-sm">
                        {startTime} • {durationMinutes} min
                    </p>
                </div>

                <span className="text-sm px-3 py-1 rounded-full bg-primary-100">
                    {status}
                </span>
            </motion.div>
            <button
                className="rounded-b-2xl cursor-pointer bg-primary-300 px-10 hover:bg-primary-500 hover:text-white"
                onClick={async () => {
                    await handleBookingDelete(id);
                    onDelete(id);
                }}
            >
                Delete
            </button>
        </motion.div>
    );
}

export default Booking;