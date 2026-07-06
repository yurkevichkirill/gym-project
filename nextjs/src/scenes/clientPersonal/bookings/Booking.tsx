import { motion } from "framer-motion";
import Link from "next/link";
import { BookingStatusEnum } from "@/types/booking/bookings-status.enum";
import {
    getBookingStatusClassName,
    getBookingStatusLabel,
} from "@/scenes/clientPersonal/bookings/booking-display";
import CancelBookingButton from "@/scenes/clientPersonal/bookings/CancelBookingButton";
import { previewCardClassName, secondaryActionClassName } from "@/shared/Section";

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
            className={previewCardClassName}
        >
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p className="font-semibold text-gray-900">{date}</p>
                    <p className="text-sm text-gray-600">
                        {startTime.slice(0, 5)} · {durationMinutes} min
                    </p>
                </div>

                <span className={getBookingStatusClassName(status)}>
                    {getBookingStatusLabel(status)}
                </span>
            </div>

            <div className="mt-4 flex flex-wrap gap-3 border-t border-gray-50 pt-4">
                <Link
                    href={`/me/bookings/${id}`}
                    className={secondaryActionClassName}
                    aria-label={`View booking ${id} details`}
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
