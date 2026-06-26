'use client'

import { useEffect } from "react";
import Link from "next/link";
import { observer } from "mobx-react-lite";
import { useStore } from "@/store/StoreProvider";
import EmptyState from "@/shared/ui/EmptyState";
import ErrorState from "@/shared/ui/ErrorState";
import LoadingState from "@/shared/ui/LoadingState";
import {
    formatDateTime,
    formatMoney,
    getBookingStatusClassName,
    getBookingStatusLabel,
} from "@/scenes/clientPersonal/bookings/booking-display";

type BookingDetailsProps = {
    bookingId: number;
};

const DetailRow = ({ label, value }: { label: string; value: string }) => (
    <div className="flex flex-col gap-1 border-b border-gray-100 py-3 last:border-b-0 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
        <dt className="text-sm text-gray-500">{label}</dt>
        <dd className="font-semibold sm:text-right">{value}</dd>
    </div>
);

const BookingDetails = observer(({ bookingId }: BookingDetailsProps) => {
    const { bookingStore } = useStore();
    const booking = bookingStore.selectedBooking?.id === bookingId
        ? bookingStore.selectedBooking
        : null;

    useEffect(() => {
        void bookingStore.loadBooking(bookingId);
    }, [bookingId, bookingStore]);

    if (booking === null && bookingStore.isDetailLoading) {
        return (
            <LoadingState
                title="Loading booking..."
                description="We are fetching the latest booking details."
            />
        );
    }

    if (booking === null && bookingStore.detailErrorStatus === 404) {
        return (
            <EmptyState
                title="Booking not found"
                description="This booking does not exist or is no longer available."
                action={<Link href="/me/bookings" className="rounded-md bg-secondary-500 px-5 py-2 font-semibold">Back to bookings</Link>}
            />
        );
    }

    if (booking === null && bookingStore.detailErrorStatus === 403) {
        return (
            <EmptyState
                title="Access denied"
                description="You cannot view a booking that belongs to another client."
                action={<Link href="/me/bookings" className="rounded-md bg-secondary-500 px-5 py-2 font-semibold">Back to bookings</Link>}
            />
        );
    }

    if (booking === null && bookingStore.detailError) {
        return (
            <ErrorState
                title="Unable to load booking"
                message={bookingStore.detailError}
                isRetrying={bookingStore.isDetailLoading}
                onRetry={() => void bookingStore.loadBooking(bookingId)}
            />
        );
    }

    if (booking === null) {
        return <LoadingState title="Loading booking..." />;
    }

    const payment = booking.payment;
    const trainer = payment.trainer;
    const trainerName = trainer
        ? `${trainer.firstName} ${trainer.lastName}`
        : `Trainer #${booking.trainerId}`;

    return (
        <section className="mx-auto w-full max-w-4xl" aria-busy={bookingStore.isDetailLoading}>
            <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
                <Link
                    href="/me/bookings"
                    className="rounded-md border border-gray-300 bg-white px-4 py-2 font-semibold transition hover:border-secondary-500"
                >
                    Back to bookings
                </Link>
                {bookingStore.isDetailLoading ? (
                    <p role="status" aria-live="polite" className="text-sm font-semibold text-secondary-500">
                        Refreshing booking...
                    </p>
                ) : null}
            </div>

            {bookingStore.detailError ? (
                <div role="alert" className="mb-6 flex flex-col gap-3 rounded-xl bg-red-50 p-4 text-red-700 sm:flex-row sm:items-center sm:justify-between">
                    <p>{bookingStore.detailError}</p>
                    <button
                        type="button"
                        className="self-start rounded-md border border-red-300 bg-white px-4 py-2 font-semibold disabled:cursor-not-allowed disabled:opacity-50 sm:self-auto"
                        disabled={bookingStore.isDetailLoading}
                        onClick={() => void bookingStore.loadBooking(bookingId)}
                    >
                        {bookingStore.isDetailLoading ? "Retrying..." : "Retry"}
                    </button>
                </div>
            ) : null}

            <article className="rounded-2xl bg-white p-6 shadow-sm sm:p-8">
                <div className="flex flex-wrap items-start justify-between gap-5">
                    <div>
                        <p className="text-sm font-semibold uppercase tracking-wider text-secondary-500">
                            Booking #{booking.id}
                        </p>
                        <h1 className="mt-2 text-3xl font-bold">{booking.date}</h1>
                        <p className="mt-2 text-lg text-gray-600">
                            {booking.startTime.slice(0, 5)} · {booking.durationMinutes} minutes
                        </p>
                    </div>
                    <span className={`rounded-full px-4 py-2 text-sm font-semibold ${getBookingStatusClassName(booking.status)}`}>
                        {getBookingStatusLabel(booking.status)}
                    </span>
                </div>

                <div className="mt-8 grid gap-6 lg:grid-cols-2">
                    <section className="rounded-xl border border-gray-100 p-5">
                        <h2 className="text-xl font-bold">Training</h2>
                        <dl className="mt-3">
                            <DetailRow label="Trainer" value={trainerName} />
                            <DetailRow label="Trainer ID" value={booking.trainerId.toString()} />
                            <DetailRow label="Date" value={booking.date} />
                            <DetailRow label="Start time" value={booking.startTime.slice(0, 5)} />
                            <DetailRow label="Duration" value={`${booking.durationMinutes} minutes`} />
                            <DetailRow label="Booked at" value={formatDateTime(booking.bookedAt)} />
                        </dl>
                        <Link
                            href={`/trainers/${booking.trainerId}`}
                            className="mt-5 inline-flex rounded-md border border-gray-300 px-4 py-2 font-semibold transition hover:border-secondary-500"
                        >
                            View trainer
                        </Link>
                    </section>

                    <section className="rounded-xl border border-gray-100 p-5">
                        <h2 className="text-xl font-bold">Payment</h2>
                        <dl className="mt-3">
                            <DetailRow label="Payment ID" value={payment.id.toString()} />
                            <DetailRow label="Amount" value={formatMoney(payment.amount, payment.currency)} />
                            <DetailRow label="Method" value={payment.method.replace(/_/g, " ")} />
                            <DetailRow label="Category" value={payment.category.replace(/_/g, " ")} />
                            <DetailRow label="Status" value={payment.status.replace(/_/g, " ")} />
                            <DetailRow label="Created at" value={formatDateTime(payment.createdAt)} />
                            <DetailRow label="Paid at" value={formatDateTime(payment.paidAt)} />
                            <DetailRow label="Expires at" value={formatDateTime(payment.expiresAt)} />
                        </dl>
                    </section>
                </div>
            </article>
        </section>
    );
});

export default BookingDetails;
