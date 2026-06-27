import DeleteTrainerAccount from "@/scenes/trainerPersonal/DeleteTrainerAccount";
import TrainerDetailsForm from "@/scenes/trainerPersonal/TrainerDetailsForm";
import TrainerPhotoForm from "@/scenes/trainerPersonal/TrainerPhotoForm";
import TrainerProfileOverview from "@/scenes/trainerPersonal/TrainerProfileOverview";
import TrainerWorktimes from "@/scenes/trainerPersonal/worktime/TrainerWorktimes";
import LoadingState from "@/shared/ui/LoadingState";
import { TrainerPersonalType } from "@/types/trainer/private/trainer.personal.type";
import Link from "next/link";
import { Suspense } from "react";

const TrainerProfile = ({
    trainer,
}: {
    trainer: TrainerPersonalType;
}) => {
    return (
        <div className="flex flex-col gap-8">
            <div className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(320px,0.8fr)]">
                <TrainerProfileOverview trainer={trainer} />

                <div className="flex flex-col gap-8">
                    <TrainerDetailsForm trainer={trainer} />
                    <TrainerPhotoForm />
                    <DeleteTrainerAccount />
                </div>
            </div>

            <div className="grid gap-6 lg:grid-cols-2">
                <section className="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                    <div className="flex-1">
                        <p className="text-sm font-semibold uppercase tracking-wider text-secondary-500">
                            Trainer-owned bookings
                        </p>
                        <h2 className="mt-2 text-2xl font-bold">Trainings</h2>
                        <p className="mt-2 text-gray-600">
                            Review, reschedule, cancel and complete trainings assigned to your account.
                        </p>
                    </div>
                    <Link
                        href="/me/trainings"
                        className="inline-flex self-start justify-center rounded-md bg-secondary-500 px-5 py-2 font-semibold transition hover:bg-primary-500 hover:text-white"
                    >
                        Manage trainings
                    </Link>
                </section>

                <section className="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                    <div className="flex-1">
                        <p className="text-sm font-semibold uppercase tracking-wider text-secondary-500">
                            Trainer finances
                        </p>
                        <h2 className="mt-2 text-2xl font-bold">Payments</h2>
                        <p className="mt-2 text-gray-600">
                            Review trainer-owned payments with server-side filters, sorting and pagination.
                        </p>
                    </div>
                    <Link
                        href="/me/trainer-payments"
                        className="inline-flex self-start justify-center rounded-md bg-secondary-500 px-5 py-2 font-semibold transition hover:bg-primary-500 hover:text-white"
                    >
                        View payments
                    </Link>
                </section>
            </div>

            <Suspense
                fallback={(
                    <LoadingState
                        title="Loading trainer worktimes..."
                        description="We are preparing the trainer-owned schedule."
                        className="rounded-2xl bg-gray-50"
                    />
                )}
            >
                <TrainerWorktimes />
            </Suspense>
        </div>
    );
};

export default TrainerProfile;
