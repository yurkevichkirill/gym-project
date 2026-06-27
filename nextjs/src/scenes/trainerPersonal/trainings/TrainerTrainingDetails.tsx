'use client';

import {
    formatDateTime,
    formatMoney,
    getBookingStatusClassName,
    getBookingStatusLabel,
} from "@/scenes/clientPersonal/bookings/booking-display";
import TrainerTrainingActions from "@/scenes/trainerPersonal/trainings/TrainerTrainingActions";
import TrainerTrainingUpdateForm from "@/scenes/trainerPersonal/trainings/TrainerTrainingUpdateForm";
import { getTrainingBusyLabel } from "@/scenes/trainerPersonal/trainings/training-display";
import EmptyState from "@/shared/ui/EmptyState";
import ErrorState from "@/shared/ui/ErrorState";
import LoadingState from "@/shared/ui/LoadingState";
import { useStore } from "@/store/StoreProvider";
import { observer } from "mobx-react-lite";
import Link from "next/link";
import { useEffect } from "react";

const DetailRow = ({
    label,
    value,
}: {
    label: string;
    value: string;
}) => (
    <div className="flex flex-col gap-1 border-b border-gray-100 py-3 last:border-b-0 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
        <dt className="text-sm text-gray-500">{label}</dt>
        <dd className="font-semibold capitalize sm:text-right">{value}</dd>
    </div>
);

const TrainerTrainingDetails = observer(({
    trainingId,
}: {
    trainingId: number;
}) => {
    const { trainerTrainingStore } = useStore();
    const training = trainerTrainingStore.selectedTraining?.id === trainingId
        ? trainerTrainingStore.selectedTraining
        : null;

    useEffect(() => {
        void trainerTrainingStore.loadTraining(trainingId);
    }, [trainerTrainingStore, trainingId]);

    if (training === null && trainerTrainingStore.isDetailLoading) {
        return (
            <LoadingState
                title="Loading training..."
                description="We are fetching the latest trainer-owned training details."
            />
        );
    }

    if (trainerTrainingStore.detailErrorStatus === 404) {
        return (
            <EmptyState
                title="Training not found"
                description="This training does not exist or is no longer available."
                action={(
                    <Link
                        href="/me/trainings"
                        className="rounded-md bg-secondary-500 px-5 py-2 font-semibold"
                    >
                        Back to trainings
                    </Link>
                )}
            />
        );
    }

    if (trainerTrainingStore.detailErrorStatus === 403) {
        return (
            <EmptyState
                title="Access denied"
                description="You cannot view a training owned by another trainer."
                action={(
                    <Link
                        href="/me/trainings"
                        className="rounded-md bg-secondary-500 px-5 py-2 font-semibold"
                    >
                        Back to trainings
                    </Link>
                )}
            />
        );
    }

    if (training === null && trainerTrainingStore.detailError) {
        return (
            <ErrorState
                title="Unable to load training"
                message={trainerTrainingStore.detailError}
                isRetrying={trainerTrainingStore.isDetailLoading}
                onRetry={() => void trainerTrainingStore.loadTraining(trainingId)}
            />
        );
    }

    if (training === null) {
        return <LoadingState title="Loading training..." />;
    }

    const payment = training.payment;

    return (
        <section
            className="mx-auto w-full max-w-5xl"
            aria-busy={trainerTrainingStore.isDetailLoading}
        >
            <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
                <Link
                    href="/me/trainings"
                    className="rounded-md border border-gray-300 bg-white px-4 py-2 font-semibold transition hover:border-secondary-500"
                >
                    Back to trainings
                </Link>
                {trainerTrainingStore.isDetailLoading ? (
                    <p
                        role="status"
                        aria-live="polite"
                        className="text-sm font-semibold text-secondary-500"
                    >
                        Refreshing training...
                    </p>
                ) : null}
            </div>

            {trainerTrainingStore.detailError ? (
                <div
                    role="alert"
                    className="mb-6 flex flex-col gap-3 rounded-xl bg-red-50 p-4 text-red-700 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p>{trainerTrainingStore.detailError}</p>
                    <button
                        type="button"
                        className="self-start rounded-md border border-red-300 bg-white px-4 py-2 font-semibold disabled:cursor-not-allowed disabled:opacity-50 sm:self-auto"
                        disabled={trainerTrainingStore.isDetailLoading}
                        onClick={() => void trainerTrainingStore.loadTraining(trainingId)}
                    >
                        {trainerTrainingStore.isDetailLoading ? "Retrying..." : "Retry"}
                    </button>
                </div>
            ) : null}

            <article className="rounded-2xl bg-white p-6 shadow-sm sm:p-8">
                <div className="flex flex-wrap items-start justify-between gap-5">
                    <div>
                        <p className="text-sm font-semibold uppercase tracking-wider text-secondary-500">
                            Training #{training.id}
                        </p>
                        <h1 className="mt-2 text-3xl font-bold">{training.date}</h1>
                        <p className="mt-2 text-lg text-gray-600">
                            {training.startTime.slice(0, 5)} · {training.durationMinutes} minutes
                        </p>
                    </div>
                    <span className={`rounded-full px-4 py-2 text-sm font-semibold ${getBookingStatusClassName(training.status)}`}>
                        {getBookingStatusLabel(training.status)}
                    </span>
                </div>

                <div className="mt-6">
                    <TrainerTrainingActions training={training} />
                </div>

                <div className="mt-8 grid gap-6 lg:grid-cols-2">
                    <section className="rounded-xl border border-gray-100 p-5">
                        <h2 className="text-xl font-bold">Training</h2>
                        <dl className="mt-3">
                            <DetailRow label="Training ID" value={training.id.toString()} />
                            <DetailRow label="Client ID" value={training.clientId.toString()} />
                            <DetailRow label="Date" value={training.date} />
                            <DetailRow label="Start time" value={training.startTime.slice(0, 5)} />
                            <DetailRow label="Duration" value={`${training.durationMinutes} minutes`} />
                            <DetailRow label="Busy state" value={getTrainingBusyLabel(training.isBusy)} />
                            <DetailRow label="Booked at" value={formatDateTime(training.bookedAt)} />
                        </dl>
                    </section>

                    <section className="rounded-xl border border-gray-100 p-5">
                        <h2 className="text-xl font-bold">Payment</h2>
                        <dl className="mt-3">
                            <DetailRow label="Payment ID" value={payment.id.toString()} />
                            <DetailRow label="Amount" value={formatMoney(payment.amount, payment.currency)} />
                            <DetailRow label="Method" value={payment.method.replace(/_/g, " ")} />
                            <DetailRow label="Category" value={payment.category.replace(/_/g, " ")} />
                            <DetailRow label="Status" value={payment.status.replace(/_/g, " ")} />
                            <DetailRow label="Refund payment" value={payment.isRefund ? "Yes" : "No"} />
                            <DetailRow label="Created at" value={formatDateTime(payment.createdAt)} />
                            <DetailRow label="Paid at" value={formatDateTime(payment.paidAt)} />
                            <DetailRow label="Expires at" value={formatDateTime(payment.expiresAt)} />
                            {payment.originalPayment ? (
                                <DetailRow
                                    label="Original payment ID"
                                    value={payment.originalPayment.id.toString()}
                                />
                            ) : null}
                        </dl>
                    </section>
                </div>

                <div className="mt-8">
                    <TrainerTrainingUpdateForm training={training} />
                </div>
            </article>
        </section>
    );
});

export default TrainerTrainingDetails;
