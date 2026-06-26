'use client'

import ErrorState from "@/shared/ui/ErrorState";

type Props = {
    error: Error & { digest?: string };
    reset: () => void;
};

const WorktimeError = ({ reset }: Props) => {
    return (
        <main className="px-6 pb-20 pt-32">
            <ErrorState
                title="Unable to load worktime"
                message="The worktime details could not be loaded."
                onRetry={reset}
            />
        </main>
    );
};

export default WorktimeError;
