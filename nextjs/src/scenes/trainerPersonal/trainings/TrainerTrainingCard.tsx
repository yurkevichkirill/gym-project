import {
    formatDateTime,
    formatMoney,
    getBookingStatusClassName,
    getBookingStatusLabel,
} from "@/scenes/clientPersonal/bookings/booking-display";
import TrainerTrainingActions from "@/scenes/trainerPersonal/trainings/TrainerTrainingActions";
import { getTrainingBusyLabel } from "@/scenes/trainerPersonal/trainings/training-display";
import { TrainerTrainingType } from "@/types/trainer/private/trainer-training.type";
import Link from "next/link";

const TrainerTrainingCard = ({
    training,
}: {
    training: TrainerTrainingType;
}) => {
    return (
        <article className="flex h-full flex-col rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p className="text-sm font-semibold uppercase tracking-wide text-secondary-500">
                        Training #{training.id}
                    </p>
                    <h2 className="mt-1 text-xl font-bold">{training.date}</h2>
                    <p className="mt-1 text-gray-600">
                        {training.startTime.slice(0, 5)} · {training.durationMinutes} min
                    </p>
                </div>
                <span className={`rounded-full px-3 py-1 text-sm font-semibold ${getBookingStatusClassName(training.status)}`}>
                    {getBookingStatusLabel(training.status)}
                </span>
            </div>

            <dl className="mt-5 grid gap-3 text-sm">
                <div className="flex justify-between gap-4">
                    <dt className="text-gray-500">Client ID</dt>
                    <dd className="text-right font-semibold">{training.clientId}</dd>
                </div>
                <div className="flex justify-between gap-4">
                    <dt className="text-gray-500">Busy state</dt>
                    <dd className="text-right font-semibold">
                        {getTrainingBusyLabel(training.isBusy)}
                    </dd>
                </div>
                <div className="flex justify-between gap-4">
                    <dt className="text-gray-500">Payment</dt>
                    <dd className="text-right font-semibold">
                        {formatMoney(training.payment.amount, training.payment.currency)}
                    </dd>
                </div>
                <div className="flex justify-between gap-4">
                    <dt className="text-gray-500">Payment status</dt>
                    <dd className="text-right font-semibold capitalize">
                        {training.payment.status.replace(/_/g, " ")}
                    </dd>
                </div>
                <div className="flex justify-between gap-4">
                    <dt className="text-gray-500">Booked at</dt>
                    <dd className="text-right font-semibold">
                        {formatDateTime(training.bookedAt)}
                    </dd>
                </div>
            </dl>

            <div className="mt-auto grid gap-3 pt-6">
                <Link
                    href={`/me/trainings/${training.id}`}
                    className="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 font-semibold transition hover:border-secondary-500"
                >
                    View details and update
                </Link>
                <TrainerTrainingActions training={training} />
            </div>
        </article>
    );
};

export default TrainerTrainingCard;
