import type { Metadata } from "next";
import { Suspense } from "react";
import TrainingTypesCatalog from "@/scenes/trainingTypes/TrainingTypesCatalog";
import LoadingState from "@/shared/ui/LoadingState";

export const dynamic = "force-dynamic";

export const metadata: Metadata = {
    title: "Training Types",
};

const TrainingTypesPage = () => {
    return (
        <main className="px-6 pb-20 pt-32">
            <Suspense
                fallback={(
                    <LoadingState
                        title="Loading training types..."
                        description="We are preparing the training catalog."
                    />
                )}
            >
                <TrainingTypesCatalog />
            </Suspense>
        </main>
    );
};

export default TrainingTypesPage;
