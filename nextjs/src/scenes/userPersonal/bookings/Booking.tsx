import { motion } from "framer-motion";

type Props = {
    id: number,
    trainerId: number,
    bookedAt: string,
    date: string,
    durationMinutes: number,
    startTime: string,
    status: string,
}

const Booking = ({ id, trainerId, bookedAt, date, durationMinutes, startTime, status }: Props) => {
    return (
        <motion.div
            whileHover={{ scale: 1.02 }}
            className="border border-gray-50 rounded-xl p-4 flex justify-between items-center"
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
    );
}

export default Booking;