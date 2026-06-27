import DeleteTrainerAccount from "@/scenes/trainerPersonal/DeleteTrainerAccount";
import TrainerDetailsForm from "@/scenes/trainerPersonal/TrainerDetailsForm";
import TrainerPhotoForm from "@/scenes/trainerPersonal/TrainerPhotoForm";
import TrainerProfileOverview from "@/scenes/trainerPersonal/TrainerProfileOverview";
import TrainerWorktimes from "@/scenes/trainerPersonal/worktime/TrainerWorktimes";
import LoadingState from "@/shared/ui/LoadingState";
import { TrainerPersonalType } from "@/types/trainer/private/trainer.personal.type";
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
