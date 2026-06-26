'use client'

import ErrorState from "@/shared/ui/ErrorState";

type Props = {
    error: Error & { digest?: string };
    reset: () => void;
};

const MembershipPlanError = ({ reset }: Props) => {
    return (
        <main className="px-6 pb-20 pt-32">
            <ErrorState
                title="Unable to load membership plan"
                message="The membership plan details could not be loaded."
                onRetry={reset}
            />
        </main>
    );
};

export default MembershipPlanError;
