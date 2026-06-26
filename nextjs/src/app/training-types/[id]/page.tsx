import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { getTrainingType } from "@/api/public/training-types.api";
import { ApiClientError } from "@/lib/apiClient";
import TrainingTypeDetails from "@/scenes/trainingTypes/TrainingTypeDetails";

type Props = {
    params: Promise<{ id: string }>;
};

export const dynamic = "force-dynamic";

export const metadata: Metadata = {
    title: "Training Type Details",
};

const TrainingTypePage = async ({ params }: Props) => {
    const { id } = await params;
    const parsedId = Number(id);

    if (!/^\d+$/.test(id) || !Number.isSafeInteger(parsedId) || parsedId <= 0) {
        notFound();
    }

    try {
        const trainingType = await getTrainingType(id);

        return (
            <main className="px-6 pb-20 pt-32">
                <TrainingTypeDetails trainingType={trainingType} />
            </main>
        );
    } catch (error: unknown) {
        if (error instanceof ApiClientError && error.status === 404) {
            notFound();
        }

        throw error;
    }
};

export default TrainingTypePage;
