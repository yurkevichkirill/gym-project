import LoadingState from "@/shared/ui/LoadingState";

const WorktimesLoading = () => {
    return (
        <main className="px-6 pb-20 pt-32">
            <LoadingState
                title="Loading worktimes..."
                description="We are preparing the public trainer schedule."
            />
        </main>
    );
};

export default WorktimesLoading;
