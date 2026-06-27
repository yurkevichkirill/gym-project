import { motion } from "framer-motion";
import Link from "next/link";
import { BookingStatusEnum } from "@/types/booking/bookings-status.enum";
import {
    getBookingStatusClassName,
    getBookingStatusLabel,
} from "@/scenes/clientPersonal/bookings/booking-display";
import CancelBookingButton from "@/scenes/clientPersonal/bookings/CancelBookingButton";

type Props = {
    id: number;
    trainerId: number;
    bookedAt: string;
    date: string;
    durationMinutes: number;
    startTime: string;
    status: BookingStatusEnum;
};

const Booking = ({ id, date, durationMinutes, startTime, status }: Props) => {
    return (
        <motion.article
            whileHover={{ scale: 1.01 }}
            className="rounded-xl border border-gray-100 bg-white p-4"
        >
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p className="font-semibold">{date}</p>
                    <p className="text-sm text-gray-600">
                        {startTime.slice(0, 5)} · {durationMinutes} min
                    </p>
                </div>

                <span className={`rounded-full px-3 py-1 text-sm ${getBookingStatusClassName(status)}`}>
                    {getBookingStatusLabel(status)}
                </span>
            </div>

            <div className="mt-4 flex flex-wrap gap-3">
                <Link
                    href={`/me/bookings/${id}`}
                    className="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold transition hover:border-secondary-500"
                >
                    View details
                </Link>
                <CancelBookingButton
                    bookingId={id}
                    status={status}
                    className="text-sm"
                />
            </div>
        </motion.article>
    );
};

export default Booking;
