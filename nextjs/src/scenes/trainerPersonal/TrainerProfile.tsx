import DeleteTrainerAccount from "@/scenes/trainerPersonal/DeleteTrainerAccount";
import TrainerDetailsForm from "@/scenes/trainerPersonal/TrainerDetailsForm";
import TrainerPhotoForm from "@/scenes/trainerPersonal/TrainerPhotoForm";
import TrainerProfileOverview from "@/scenes/trainerPersonal/TrainerProfileOverview";
import { TrainerPersonalType } from "@/types/trainer/private/trainer.personal.type";

const TrainerProfile = ({
    trainer,
}: {
    trainer: TrainerPersonalType;
}) => {
    return (
        <div className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(320px,0.8fr)]">
            <TrainerProfileOverview trainer={trainer} />

            <div className="flex flex-col gap-8">
                <TrainerDetailsForm trainer={trainer} />
                <TrainerPhotoForm />
                <DeleteTrainerAccount />
            </div>
        </div>
    );
};

export default TrainerProfile;
