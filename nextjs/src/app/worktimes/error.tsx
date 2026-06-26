'use client'

import ErrorState from "@/shared/ui/ErrorState";

type Props = {
    error: Error & { digest?: string };
    reset: () => void;
};

const WorktimesError = ({ reset }: Props) => {
    return (
        <main className="px-6 pb-20 pt-32">
            <ErrorState
                title="Unable to open worktimes"
                message="The public worktime catalog could not be rendered."
                onRetry={reset}
            />
        </main>
    );
};

export default WorktimesError;
