import type { Metadata } from "next";
import { Suspense } from "react";
import WorktimesCatalog from "@/scenes/worktime/WorktimesCatalog";
import LoadingState from "@/shared/ui/LoadingState";

export const dynamic = "force-dynamic";

export const metadata: Metadata = {
    title: "Trainer Worktimes",
};

const WorktimesPage = () => {
    return (
        <main className="px-6 pb-20 pt-32">
            <Suspense
                fallback={(
                    <LoadingState
                        title="Loading worktimes..."
                        description="We are preparing the public trainer schedule."
                    />
                )}
            >
                <WorktimesCatalog />
            </Suspense>
        </main>
    );
};

export default WorktimesPage;
