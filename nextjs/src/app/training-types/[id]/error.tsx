'use client'

import ErrorState from "@/shared/ui/ErrorState";

type Props = {
    error: Error & { digest?: string };
    reset: () => void;
};

const TrainingTypeError = ({ reset }: Props) => {
    return (
        <main className="px-6 pb-20 pt-32">
            <ErrorState
                title="Unable to load training type"
                message="The training type details could not be loaded."
                onRetry={reset}
            />
        </main>
    );
};

export default TrainingTypeError;
