import TrainerTrainingDetails from "@/scenes/trainerPersonal/trainings/TrainerTrainingDetails";
import { notFound } from "next/navigation";

const TrainerTrainingPage = async ({
    params,
}: {
    params: Promise<{ id: string }>;
}) => {
    const { id } = await params;

    if (!/^\d+$/.test(id)) {
        notFound();
    }

    const trainingId = Number(id);

    if (!Number.isSafeInteger(trainingId) || trainingId <= 0) {
        notFound();
    }

    return (
        <main className="px-6 pb-20 pt-32">
            <TrainerTrainingDetails trainingId={trainingId} />
        </main>
    );
};

export default TrainerTrainingPage;
