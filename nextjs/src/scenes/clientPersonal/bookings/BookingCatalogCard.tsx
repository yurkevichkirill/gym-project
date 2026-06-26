import Link from "next/link";
import BookingType from "@/types/booking/booking.type";
import {
    formatMoney,
    getBookingStatusClassName,
    getBookingStatusLabel,
} from "@/scenes/clientPersonal/bookings/booking-display";

type BookingCatalogCardProps = {
    booking: BookingType;
};

const BookingCatalogCard = ({ booking }: BookingCatalogCardProps) => {
    const trainer = booking.payment.trainer;
    const trainerName = trainer
        ? `${trainer.firstName} ${trainer.lastName}`
        : `Trainer #${booking.trainerId}`;

    return (
        <article className="flex h-full flex-col rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p className="text-sm font-semibold uppercase tracking-wide text-secondary-500">
                        Booking #{booking.id}
                    </p>
                    <h2 className="mt-1 text-xl font-bold">{booking.date}</h2>
                    <p className="mt-1 text-gray-600">
                        {booking.startTime.slice(0, 5)} · {booking.durationMinutes} min
                    </p>
                </div>
                <span className={`rounded-full px-3 py-1 text-sm font-semibold ${getBookingStatusClassName(booking.status)}`}>
                    {getBookingStatusLabel(booking.status)}
                </span>
            </div>

            <dl className="mt-5 grid gap-3 text-sm">
                <div className="flex justify-between gap-4">
                    <dt className="text-gray-500">Trainer</dt>
                    <dd className="text-right font-semibold">{trainerName}</dd>
                </div>
                <div className="flex justify-between gap-4">
                    <dt className="text-gray-500">Payment</dt>
                    <dd className="text-right font-semibold">
                        {formatMoney(booking.payment.amount, booking.payment.currency)}
                    </dd>
                </div>
                <div className="flex justify-between gap-4">
                    <dt className="text-gray-500">Payment status</dt>
                    <dd className="text-right font-semibold capitalize">
                        {booking.payment.status.replace(/_/g, " ")}
                    </dd>
                </div>
            </dl>

            <Link
                href={`/me/bookings/${booking.id}`}
                className="mt-6 inline-flex justify-center rounded-md bg-secondary-500 px-4 py-2 font-semibold transition hover:bg-primary-500 hover:text-white"
            >
                View details
            </Link>
        </article>
    );
};

export default BookingCatalogCard;
