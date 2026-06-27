import TrainerTrainingsCatalog from "@/scenes/trainerPersonal/trainings/TrainerTrainingsCatalog";
import LoadingState from "@/shared/ui/LoadingState";
import { Suspense } from "react";

const TrainerTrainingsPage = () => {
    return (
        <main className="px-6 pb-20 pt-32">
            <Suspense
                fallback={(
                    <LoadingState
                        title="Loading trainer trainings..."
                        description="We are preparing the trainer-owned training catalog."
                    />
                )}
            >
                <TrainerTrainingsCatalog />
            </Suspense>
        </main>
    );
};

export default TrainerTrainingsPage;
