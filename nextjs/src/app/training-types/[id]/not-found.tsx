import Link from "next/link";
import EmptyState from "@/shared/ui/EmptyState";

const TrainingTypeNotFound = () => {
    return (
        <main className="px-6 pb-20 pt-32">
            <EmptyState
                title="Training type not found"
                description="The requested training type does not exist or is no longer available."
                action={(
                    <Link
                        href="/training-types"
                        className="rounded-md bg-secondary-500 px-5 py-2 font-semibold transition hover:bg-primary-500 hover:text-white"
                    >
                        Back to training types
                    </Link>
                )}
            />
        </main>
    );
};

export default TrainingTypeNotFound;
