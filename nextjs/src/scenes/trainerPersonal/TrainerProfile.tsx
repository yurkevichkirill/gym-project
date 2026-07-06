import DeleteTrainerAccount from "@/scenes/trainerPersonal/DeleteTrainerAccount";
import TrainerDetailsForm from "@/scenes/trainerPersonal/TrainerDetailsForm";
import TrainerPhotoForm from "@/scenes/trainerPersonal/TrainerPhotoForm";
import TrainerProfileOverview from "@/scenes/trainerPersonal/TrainerProfileOverview";
import TrainerWorktimes from "@/scenes/trainerPersonal/worktime/TrainerWorktimes";
import {
    loadingStateClassName,
    previewCardClassName,
    primaryActionClassName,
} from "@/shared/Section";
import { TrainerPersonalType } from "@/types/trainer/private/trainer.personal.type";
import Link from "next/link";
import { Suspense } from "react";

const TrainerProfile = ({
    trainer,
}: {
    trainer: TrainerPersonalType;
}) => {
    return (
        <>
            <TrainerProfileOverview trainer={trainer} />

            <div className="grid gap-6 md:grid-cols-2">
                <div className={previewCardClassName}>
                    <div className="flex min-h-36 flex-col">
                        <p className="text-xs font-semibold uppercase text-gray-500">
                            Trainer-owned bookings
                        </p>
                        <h2 className="mt-2 text-xl font-bold text-gray-500">Trainings</h2>
                        <p className="mt-2 text-sm text-gray-500">
                            Review, reschedule, cancel and complete trainings assigned to your account.
                        </p>
                        <Link
                            href="/me/trainings"
                            className={`mt-auto self-start ${primaryActionClassName}`}
                        >
                            Manage trainings
                        </Link>
                    </div>
                </div>

                <div className={previewCardClassName}>
                    <div className="flex min-h-36 flex-col">
                        <p className="text-xs font-semibold uppercase text-gray-500">
                            Trainer finances
                        </p>
                        <h2 className="mt-2 text-xl font-bold text-gray-500">Payments</h2>
                        <p className="mt-2 text-sm text-gray-500">
                            Review trainer-owned payments with server-side filters, sorting and pagination.
                        </p>
                        <Link
                            href="/me/trainer-payments"
                            className={`mt-auto self-start ${primaryActionClassName}`}
                        >
                            View payments
                        </Link>
                    </div>
                </div>
            </div>

            <div className="grid gap-6 md:grid-cols-2">
                <TrainerDetailsForm trainer={trainer} />
                <TrainerPhotoForm />
            </div>

            <Suspense
                fallback={(
                    <div className={loadingStateClassName} role="status" aria-live="polite">
                        Loading trainer worktimes...
                    </div>
                )}
            >
                <TrainerWorktimes />
            </Suspense>

            <DeleteTrainerAccount />
        </>
    );
};

export default TrainerProfile;
