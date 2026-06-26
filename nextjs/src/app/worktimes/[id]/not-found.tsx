import Link from "next/link";
import EmptyState from "@/shared/ui/EmptyState";

const WorktimeNotFound = () => {
    return (
        <main className="px-6 pb-20 pt-32">
            <EmptyState
                title="Worktime not found"
                description="The requested worktime does not exist or is no longer publicly available."
                action={(
                    <Link
                        href="/worktimes"
                        className="rounded-md bg-secondary-500 px-5 py-2 font-semibold transition hover:bg-primary-500 hover:text-white"
                    >
                        Back to worktimes
                    </Link>
                )}
            />
        </main>
    );
};

export default WorktimeNotFound;
